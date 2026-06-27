<?php

namespace App\Filament\Resources\LessonPlans\Widgets;

use App\Models\AcademicCalendar;
use App\Models\AcademicYear;
use App\Models\LessonPlan;
use App\Models\MainTarget;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Target;
use App\TeachingStatusEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Guava\Calendar\Filament\Actions\CreateAction;
use Guava\Calendar\Filament\Actions\DeleteAction;
use Guava\Calendar\Filament\Actions\EditAction;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\DateClickInfo;
use Guava\Calendar\ValueObjects\EventDropInfo;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class LessonPlanWidget extends CalendarWidget
{
    protected ?string $locale = 'id';

    protected bool $dateClickEnabled = true;

    protected bool $eventClickEnabled = true;

    protected bool $eventDragEnabled = true;

    protected ?string $defaultEventClickAction = null;

    public ?string $selectedDate = null;

    public ?string $filterSubjectId = null;

    public function mount(): void
    {
        $firstSubject = Subject::mySubjects()->first();
        if ($firstSubject) {
            $this->filterSubjectId = $firstSubject->id;
        }
    }

    #[On('activeTabChanged')]
    public function handleTabChanged(?string $subjectId = null): void
    {
        $this->filterSubjectId = $subjectId;
        $this->refreshEvents();
    }

    protected function getEvents(FetchInfo $info): Collection|array|Builder
    {
        $lessonPlans = LessonPlan::query()
            ->myLessonPlans()
            ->when($this->filterSubjectId, fn (Builder $q) => $q->where('subject_id', $this->filterSubjectId))
            ->whereBetween('planned_date', [$info->start, $info->end])
            ->with(['subject', 'grade'])
            ->get();

        $hints = $this->getScheduleHints($info, $lessonPlans);

        $events = $lessonPlans->map(function (LessonPlan $lessonPlan) {
            $color = \App\Helpers\ColorHelper::normalizeColor($lessonPlan->subject?->color);
            $textColor = \App\Helpers\ColorHelper::getContrastColor($color);

            return CalendarEvent::make($lessonPlan)
                ->title($lessonPlan->subject?->code.' - '.$lessonPlan->topic)
                ->key($lessonPlan->id)
                ->start($lessonPlan->planned_date)
                ->end($lessonPlan->planned_date)
                ->backgroundColor($color)
                ->textColor($textColor)
                ->editable(true)
                ->allDay(true);
        });

        $academicCalendars = AcademicCalendar::query()
            ->whereBetween('start_date', [$info->start, $info->end])
            ->orWhereBetween('end_date', [$info->start, $info->end])
            ->get();

        $academicEvents = $academicCalendars->map(function (AcademicCalendar $cal) {
            return $cal->toCalendarEvent()->editable(false);
        });

        return collect($events)->merge($hints)->merge($academicEvents);
    }

    protected function getScheduleHints(FetchInfo $info, Collection $lessonPlans): Collection
    {
        $schedules = Schedule::whereHas('subject', function ($q) {
            $q->mySubjects()
                ->when($this->filterSubjectId, fn (Builder $q) => $q->where('id', $this->filterSubjectId));
        })->with('subject')->get();

        if ($schedules->isEmpty()) {
            return collect();
        }

        $existing = $lessonPlans->mapWithKeys(fn ($lp) => [
            $lp->planned_date->format('Y-m-d').'-'.$lp->subject_id => true,
        ]);

        $hints = collect();
        $start = $info->start;
        $end = $info->end;

        $academicYear = AcademicYear::active()->first();

        if ($academicYear && $academicYear->date_start && $academicYear->date_end) {
            $dateStart = \Carbon\CarbonImmutable::instance($academicYear->date_start);
            $dateEnd = \Carbon\CarbonImmutable::instance($academicYear->date_end);

            if ($end->lt($dateStart) || $start->gt($dateEnd)) {
                return $hints;
            }

            if ($start->lt($dateStart)) {
                $start = $dateStart;
            }
            if ($end->gt($dateEnd)) {
                $end = $dateEnd;
            }
        }

        $period = new \DatePeriod($start, new \DateInterval('P1D'), $end->addDay());

        foreach ($period as $date) {
            $dayOfWeek = strtolower($date->format('l'));

            foreach ($schedules as $schedule) {
                $days = $schedule->days ?? [];

                if (! in_array($dayOfWeek, $days)) {
                    continue;
                }

                $key = $date->format('Y-m-d').'-'.$schedule->subject_id;
                if ($existing->has($key)) {
                    continue;
                }

                $color = \App\Helpers\ColorHelper::normalizeColor($schedule->subject->color);
                $textColor = \App\Helpers\ColorHelper::getContrastColor($color);

                $hints->push(
                    CalendarEvent::make()
                        ->title($schedule->subject->code)
                        ->key('schedule-hint-'.$schedule->subject_id.'-'.$date->format('Y-m-d'))
                        ->start(\Carbon\Carbon::instance($date))
                        ->end(\Carbon\Carbon::instance($date))
                        ->backgroundColor($color)
                        ->textColor($textColor)
                        ->editable(false)
                        ->allDay(true)
                        ->styles([
                            'opacity' => '0.4',
                            'pointer-events' => 'none',
                            'border-style' => 'dashed',
                        ])
                );
            }
        }

        return $hints;
    }

    public function refreshEvents(): void
    {
        $this->refreshRecords();
    }

    protected function onDateClick(DateClickInfo $info): void
    {
        $selectedDate = null;

        try {
            $reflection = new \ReflectionClass($info);
            $properties = $reflection->getProperties();

            foreach ($properties as $property) {
                $property->setAccessible(true);
                $value = $property->getValue($info);

                if (in_array($property->getName(), ['date', 'dateStr', 'start', 'startStr']) && $value) {
                    $selectedDate = $value;
                    break;
                }
            }
        } catch (\Exception $e) {
        }

        if (! $selectedDate) {
            $selectedDate = $info->date ?? $info->dateStr ?? now()->format('Y-m-d');
        }

        $this->selectedDate = $selectedDate;
        $this->mountAction('createLessonPlanAction');
    }

    protected function getEventClickContextMenuActions(): array
    {
        return [
            $this->editAction(),
            $this->deleteAction(),
        ];
    }

    protected function onEventDrop(EventDropInfo $info, Model $event): bool
    {
        if ($event instanceof LessonPlan) {
            try {
                $reflection = new \ReflectionClass($info->event);
                $startProperty = $reflection->getProperty('start');
                $startProperty->setAccessible(true);
                $newDate = $startProperty->getValue($info->event);

                $formattedDate = $newDate->format('Y-m-d');

                $event->update(['planned_date' => $formattedDate]);

                $this->refreshRecords();

                Notification::make()
                    ->title('Rencana Pembelajaran berhasil dipindahkan')
                    ->body("Tanggal rencana pembelajaran '{$event->topic}' telah diubah ke {$newDate->format('d/m/Y')}")
                    ->success()
                    ->send();

                return true;
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Gagal memindahkan')
                    ->body('Terjadi kesalahan saat memindahkan rencana pembelajaran')
                    ->danger()
                    ->send();

                return false;
            }
        }

        return false;
    }

    public function createLessonPlanAction(): CreateAction
    {
        return $this->createAction(LessonPlan::class)
            ->slideOver()
            ->form($this->getLessonPlanForm())
            ->fillForm(function (array $arguments): array {
                $dateToUse = $this->selectedDate ?? now()->format('Y-m-d');

                if ($dateToUse && ! is_string($dateToUse)) {
                    if ($dateToUse instanceof \Carbon\Carbon) {
                        $dateToUse = $dateToUse->format('Y-m-d');
                    } elseif (is_object($dateToUse) && method_exists($dateToUse, 'format')) {
                        $dateToUse = $dateToUse->format('Y-m-d');
                    } else {
                        $dateToUse = (string) $dateToUse;
                    }
                }

                $this->selectedDate = null;

                return [
                    'planned_date' => $dateToUse,
                ];
            })
            ->mutateFormDataUsing(function (array $data): array {
                $data['academic_year_id'] = AcademicYear::active()->first()?->id;
                $data['user_id'] = Auth::id();

                if (isset($data['subject_id'])) {
                    $subject = Subject::find($data['subject_id']);
                    if ($subject) {
                        $data['grade_id'] = $subject->grade_id;
                    }
                }

                return $data;
            })
            ->after(function () {
                $this->refreshRecords();

                Notification::make()
                    ->title('Rencana Pembelajaran berhasil dibuat')
                    ->success()
                    ->send();
            });
    }

    public function editAction(): EditAction
    {
        return EditAction::make()
            ->slideOver()
            ->form($this->getLessonPlanForm())
            ->mutateFormDataUsing(function (array $data): array {
                if (isset($data['subject_id'])) {
                    $subject = Subject::find($data['subject_id']);
                    if ($subject) {
                        $data['grade_id'] = $subject->grade_id;
                    }
                }

                return $data;
            })
            ->after(function () {
                $this->refreshRecords();

                Notification::make()
                    ->title('Rencana Pembelajaran berhasil diupdate')
                    ->success()
                    ->send();
            });
    }

    public function deleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->requiresConfirmation()
            ->modalHeading('Hapus Rencana Pembelajaran')
            ->modalDescription('Apakah Anda yakin ingin menghapus rencana pembelajaran ini? Data yang sudah dihapus tidak dapat dikembalikan.')
            ->modalSubmitActionLabel('Ya, Hapus')
            ->after(function () {
                $this->refreshRecords();

                Notification::make()
                    ->title('Rencana Pembelajaran berhasil dihapus')
                    ->success()
                    ->send();
            });
    }

    protected function getLessonPlanForm(): array
    {
        return [
            Hidden::make('academic_year_id')
                ->default(AcademicYear::active()->first()?->id),
            Hidden::make('user_id')
                ->default(Auth::id()),
            Hidden::make('grade_id')
                ->reactive(),
            DatePicker::make('planned_date')
                ->default(now())
                ->required(),
            Select::make('subject_id')
                ->options(
                    fn () => Subject::mySubjects()
                        ->get()
                        ->map(
                            fn ($subject) => [
                                'label' => $subject->code.' - '.$subject->grade->name,
                                'value' => $subject->id,
                            ]
                        )->pluck('label', 'value')
                )
                ->reactive()
                ->afterStateUpdated(fn ($state, callable $set) => $set('grade_id', Subject::find($state)?->grade_id))
                ->searchable()
                ->preload()
                ->required(),
            ToggleButtons::make('status')
                ->options(TeachingStatusEnum::class)
                ->default(TeachingStatusEnum::PEMBELAJARAN)
                ->columnSpanFull()
                ->inline()
                ->reactive()
                ->required(),
            Select::make('target_id')
                ->hidden(fn ($get) => ! $get('subject_id'))
                ->options(
                    fn ($get) => Target::myTargetsInSubject($get('subject_id'))
                        ->get()
                        ->map(
                            fn ($target) => [
                                'label' => $target->target,
                                'value' => $target->id,
                            ]
                        )->pluck('label', 'value')
                )
                ->searchable()
                ->preload()
                ->nullable()
                ->createOptionForm(function ($get) {
                    $subjectId = $get('subject_id');
                    $gradeId = $get('grade_id');

                    return [
                        Hidden::make('subject_id')
                            ->default($subjectId),
                        Hidden::make('grade_id')
                            ->default($gradeId),
                        Select::make('main_target_id')
                            ->label('Tujuan Utama')
                            ->options(
                                fn () => MainTarget::myMainTargetsInSubject($subjectId)
                                    ->get()
                                    ->pluck('main_target', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('main_target')
                                    ->label('Tujuan Utama Baru')
                                    ->required(),
                            ])
                            ->createOptionUsing(function (array $data) use ($subjectId, $gradeId) {
                                return MainTarget::create([
                                    'subject_id' => $subjectId,
                                    'grade_id' => $gradeId,
                                    'academic_year_id' => AcademicYear::active()->first()?->id,
                                    'user_id' => Auth::id(),
                                    'main_target' => $data['main_target'],
                                ])->id;
                            }),
                        TextInput::make('target')
                            ->label('Target Baru')
                            ->required(),
                    ];
                })
                ->createOptionUsing(function (array $data, callable $get) {
                    return Target::create([
                        'subject_id' => $get('subject_id'),
                        'grade_id' => $get('grade_id'),
                        'academic_year_id' => AcademicYear::active()->first()?->id,
                        'user_id' => Auth::id(),
                        'main_target_id' => $data['main_target_id'],
                        'target' => $data['target'],
                    ])->id;
                }),
            TextInput::make('topic')
                ->columnSpanFull()
                ->required(),
            RichEditor::make('learning_objectives')
                ->label('Tujuan Pembelajaran')
                ->toolbarButtons([
                    ['bold', 'italic', 'underline'],
                    ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                    ['bulletList', 'orderedList'],
                    ['undo', 'redo'],
                ])
                ->columnSpanFull()
                ->required(),
            RichEditor::make('activities')
                ->label('Kegiatan Pembelajaran')
                ->toolbarButtons([
                    ['bold', 'italic', 'underline'],
                    ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                    ['bulletList', 'orderedList'],
                    ['table'],
                    ['undo', 'redo'],
                ])
                ->columnSpanFull()
                ->required(),
            Toggle::make('has_materials_assessment')
                ->label('Tambahkan Materi & Penilaian')
                ->live()
                ->columnSpanFull(),
            RichEditor::make('materials')
                ->hidden(fn ($get) => ! $get('has_materials_assessment'))
                ->label('Materi Ajar')
                ->toolbarButtons([
                    ['bold', 'italic', 'underline'],
                    ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                    ['bulletList', 'orderedList'],
                    ['table'],
                    ['undo', 'redo'],
                ])
                ->columnSpanFull(),
            RichEditor::make('assessment')
                ->hidden(fn ($get) => ! $get('has_materials_assessment'))
                ->label('Penilaian')
                ->toolbarButtons([
                    ['bold', 'italic', 'underline'],
                    ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                    ['bulletList', 'orderedList'],
                    ['table'],
                    ['undo', 'redo'],
                ])
                ->columnSpanFull(),
            SpatieMediaLibraryFileUpload::make('lesson_plan_files')
                ->label('Lampiran')
                ->disk('public')
                ->multiple()
                ->openable()
                ->columnSpanFull()
                ->collection('lesson_plan_files')
                ->panelLayout('grid'),
        ];
    }
}
