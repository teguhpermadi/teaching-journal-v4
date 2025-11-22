<?php

namespace App\Filament\Resources\Journals\Widgets;

use App\Filament\Resources\Journals\JournalResource;
use App\Models\AcademicYear;
use App\Models\Journal;
use App\Models\MainTarget;
use App\Models\Subject;
use App\Models\Target;
use App\TeachingStatusEnum;
use Filament\Actions\Action;
use Guava\Calendar\Filament\Actions\CreateAction;
use Guava\Calendar\Filament\Actions\EditAction;
use Guava\Calendar\Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Section;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Student;
use App\StatusAttendanceEnum;
use Filament\Notifications\Notification;
use Guava\Calendar\Filament\CalendarWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Guava\Calendar\ValueObjects\FetchInfo;
use Guava\Calendar\ValueObjects\DateClickInfo;
use Guava\Calendar\ValueObjects\EventClickInfo;
use Guava\Calendar\ValueObjects\EventDropInfo;
use Guava\Calendar\Contracts\ContextualInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class JournalWidget extends CalendarWidget
{
    protected ?string $locale = 'id';

    protected bool $dateClickEnabled = true;

    protected bool $eventClickEnabled = true;

    protected bool $eventDragEnabled = true;

    protected ?string $defaultEventClickAction = null; // Disable default action untuk menggunakan context menu

    // Property untuk menyimpan tanggal yang diklik
    public ?string $selectedDate = null;

    // Property untuk menyimpan data attendance sementara
    public array $attendanceData = [];

    protected function getEvents(FetchInfo $info): Collection | array | Builder
    {
        return Journal::query()->myJournals();
    }

    public function refreshEvents(): void
    {
        $this->refreshRecords();
    }

    protected function onDateClick(DateClickInfo $info): void
    {
        // Simpan tanggal yang diklik dengan reflection
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
            // Silent fail
        }

        // Fallback
        if (!$selectedDate) {
            $selectedDate = $info->date ?? $info->dateStr ?? now()->format('Y-m-d');
        }

        $this->selectedDate = $selectedDate;
        $this->mountAction('createJournalAction');
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
        if ($event instanceof Journal) {
            try {
                // Akses tanggal baru dengan reflection karena property protected
                $reflection = new \ReflectionClass($info->event);
                $startProperty = $reflection->getProperty('start');
                $startProperty->setAccessible(true);
                $newDate = $startProperty->getValue($info->event);

                // Format tanggal untuk database
                $formattedDate = $newDate->format('Y-m-d');

                // Update journal dengan tanggal baru
                $event->update(['date' => $formattedDate]);

                // Sync attendance dates
                $event->attendance()->update(['date' => $formattedDate]);

                $this->refreshRecords();

                Notification::make()
                    ->title('Journal berhasil dipindahkan')
                    ->body("Tanggal journal '{$event->chapter}' telah diubah ke {$newDate->format('d/m/Y')}")
                    ->success()
                    ->send();

                return true;
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Gagal memindahkan journal')
                    ->body('Terjadi kesalahan saat memindahkan journal')
                    ->danger()
                    ->send();

                return false;
            }
        }

        return false;
    }

    public function createJournalAction(): CreateAction
    {
        return $this->createAction(Journal::class)
            ->slideOver()
            ->form($this->getJournalForm())
            ->fillForm(function (array $arguments): array {
                // Gunakan tanggal yang disimpan dari onDateClick
                $dateToUse = $this->selectedDate ?? now()->format('Y-m-d');

                // Pastikan format tanggal benar
                if ($dateToUse && !is_string($dateToUse)) {
                    if ($dateToUse instanceof \Carbon\Carbon) {
                        $dateToUse = $dateToUse->format('Y-m-d');
                    } elseif (is_object($dateToUse) && method_exists($dateToUse, 'format')) {
                        $dateToUse = $dateToUse->format('Y-m-d');
                    } else {
                        $dateToUse = (string) $dateToUse;
                    }
                }

                // Reset selectedDate setelah digunakan
                $this->selectedDate = null;

                return [
                    'date' => $dateToUse,
                ];
            })
            ->mutateFormDataUsing(function (array $data): array {
                $data['academic_year_id'] = AcademicYear::active()->first()->id;
                $data['user_id'] = Auth::id();

                if (isset($data['subject_id'])) {
                    $subject = Subject::find($data['subject_id']);
                    if ($subject) {
                        $data['grade_id'] = $subject->grade_id;
                    }
                }

                if ($data['status'] == TeachingStatusEnum::DITIADAKAN) {
                    $data['chapter'] = '-';
                    $data['activity'] = '-';
                }

                // Simpan data attendance ke property sementara dan hapus dari data utama
                $this->attendanceData = $data['attendance'] ?? [];
                unset($data['attendance']);

                return $data;
            })
            ->after(function (Journal $record) {
                // Simpan data attendance
                foreach ($this->attendanceData as $attendance) {
                    if (!empty($attendance['student_id']) && !empty($attendance['status'])) {
                        \App\Models\Attendance::create([
                            'journal_id' => $record->id,
                            'student_id' => $attendance['student_id'],
                            'status' => $attendance['status'],
                            'date' => $record->date,
                        ]);
                    }
                }

                // Reset attendance data
                $this->attendanceData = [];

                $this->refreshRecords();

                // Notification::make()
                //     ->title('Journal berhasil dibuat')
                //     ->success()
                //     ->send();
            });
    }

    public function editAction(): EditAction
    {
        return EditAction::make()
            ->form($this->getJournalForm())
            ->fillForm(function (Journal $record): array {
                $data = $record->attributesToArray();
                $data['attendance'] = $record->attendance->map(fn($item) => [
                    'student_id' => $item->student_id,
                    'status' => $item->status,
                    'date' => $item->date,
                ])->toArray();
                return $data;
            })
            ->slideOver()
            ->mutateFormDataUsing(function (array $data): array {
                if (isset($data['subject_id'])) {
                    $subject = Subject::find($data['subject_id']);
                    if ($subject) {
                        $data['grade_id'] = $subject->grade_id;
                    }
                }

                // Simpan data attendance ke property sementara dan hapus dari data utama
                $this->attendanceData = $data['attendance'] ?? [];
                unset($data['attendance']);

                return $data;
            })
            ->after(function (Journal $record) {
                // Hapus attendance lama
                $record->attendance()->forceDelete();

                // Simpan data attendance baru
                foreach ($this->attendanceData as $attendance) {
                    if (!empty($attendance['student_id']) && !empty($attendance['status'])) {
                        \App\Models\Attendance::create([
                            'journal_id' => $record->id,
                            'student_id' => $attendance['student_id'],
                            'status' => $attendance['status'],
                            'date' => $record->date,
                        ]);
                    }
                }

                // Reset attendance data
                $this->attendanceData = [];

                $this->refreshRecords();

                Notification::make()
                    ->title('Journal berhasil diupdate')
                    ->success()
                    ->send();
            });
    }

    public function deleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->requiresConfirmation()
            ->modalHeading('Hapus Journal')
            ->modalDescription('Apakah Anda yakin ingin menghapus journal ini? Data yang sudah dihapus tidak dapat dikembalikan.')
            ->modalSubmitActionLabel('Ya, Hapus')
            ->after(function () {
                $this->refreshRecords();

                Notification::make()
                    ->title('Journal berhasil dihapus')
                    ->success()
                    ->send();
            });
    }

    protected function getJournalForm(): array
    {
        return [
            DatePicker::make('date')
                ->label('Tanggal')
                ->default(now())
                ->required(),

            Select::make('subject_id')
                ->label('Mata Pelajaran')
                ->options(
                    fn() => Subject::mySubjects()
                        ->get()
                        ->map(
                            fn($subject) => [
                                'label' => $subject->code . ' - ' . $subject->grade->name,
                                'value' => $subject->id
                            ]
                        )->pluck('label', 'value')
                )
                ->searchable()
                ->preload()
                ->reactive()
                ->required(),
            Radio::make('status')
                ->options(TeachingStatusEnum::class)
                ->default(TeachingStatusEnum::PEMBELAJARAN)
                ->columnSpanFull()
                ->afterStateUpdated(function ($state, callable $set) {
                    $set('main_target_id', []);
                    $set('target_id', []);
                })
                ->inline()
                ->reactive()
                ->required(),
            Select::make('main_target_id')
                ->visible(fn($get) => $get('status') == TeachingStatusEnum::PEMBELAJARAN)
                ->options(
                    function ($get) {
                        $mainTargets = MainTarget::myMainTargetsInSubject($get('subject_id'))
                            ->get();

                        if ($mainTargets->isEmpty()) {
                            return [];
                        }

                        return $mainTargets->map(
                            fn($mainTarget) => [
                                'label' => $mainTarget->main_target,
                                'value' => $mainTarget->id
                            ]
                        )->pluck('label', 'value');
                    }
                )
                ->reactive()
                ->searchable()
                ->multiple()
                ->preload()
                ->required(),

            Select::make('target_id')
                ->visible(fn($get) => $get('status') == TeachingStatusEnum::PEMBELAJARAN)
                ->options(
                    function ($get) {
                        $targets = Target::myTargetsInSubject($get('subject_id'))
                            ->whereIn('main_target_id', $get('main_target_id'))
                            ->get();

                        if ($targets->isEmpty()) {
                            return [];
                        }

                        return $targets->map(
                            fn($target) => [
                                'label' => $target->target,
                                'value' => $target->id
                            ]
                        )->pluck('label', 'value');
                    }
                )
                ->multiple()
                ->createOptionForm([
                    // Hidden::make('subject_id')
                    //     ->reactive()
                    //     ->default(fn ($get) => $get('subject_id')),
                    // Hidden::make('grade_id')
                    //     ->reactive()
                    //     ->default(fn ($get) => $get('grade_id')),
                    // Hidden::make('academic_year_id')
                    //     ->reactive()
                    //     ->default(fn ($get) => $get('academic_year_id')),
                    // Hidden::make('user_id')
                    //     ->reactive()
                    //     ->default(fn ($get) => $get('user_id')),
                    TextInput::make('target')
                        ->required(),
                ])
                ->createOptionUsing(function ($data, $get) {
                    // dd($get('subject_id'));
                    Target::create([
                        'subject_id' => $get('subject_id'),
                        'grade_id' => $get('grade_id'),
                        'academic_year_id' => $get('academic_year_id'),
                        'user_id' => $get('user_id'),
                        'target' => $data['target'],
                        'main_target_id' => $get('main_target_id'),
                    ]);
                })
                ->reactive()
                ->searchable()
                ->preload()
                ->required(),

            TextInput::make('chapter')
                ->label('Bab/Materi')
                ->required()
                ->hidden(fn($get) => $get('status') == TeachingStatusEnum::DITIADAKAN)
                ->columnSpanFull(),

            RichEditor::make('activity')
                ->label('Kegiatan Pembelajaran')
                ->toolbarButtons([
                    ['bold', 'italic', 'underline'],
                    ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                    ['bulletList', 'orderedList'],
                    ['table'],
                    ['undo', 'redo'],
                ])
                ->required()
                ->hidden(fn($get) => $get('status') == TeachingStatusEnum::DITIADAKAN)
                ->columnSpanFull(),

            RichEditor::make('notes')
                ->label('Catatan')
                ->toolbarButtons([
                    ['bold', 'italic', 'underline'],
                    ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                    ['bulletList', 'orderedList'],
                    ['table'],
                    ['undo', 'redo'],
                ])
                ->columnSpanFull(),

            SpatieMediaLibraryFileUpload::make('activity_photos')
                ->label('Foto Kegiatan')
                ->hint('Upload foto kegiatan pembelajaran')
                ->disk('public')
                ->multiple()
                ->openable()
                ->collection('activity_photos')
                ->image()
                ->hidden(fn($get) => $get('status') == TeachingStatusEnum::DITIADAKAN)
                ->columnSpanFull(),

            Section::make('Ketidakhadiran')
                ->schema([
                    Repeater::make('attendance')
                        // ->relationship()
                        ->schema([
                            Select::make('student_id')
                                ->label('Siswa')
                                ->options(function ($get) {
                                    // Ambil subject_id dari parent form
                                    // Karena ini di dalam repeater, kita perlu naik ke parent
                                    // Tapi relationship repeater biasanya terisolasi context-nya
                                    // Kita coba akses state form utama

                                    // Hack: Repeater item doesn't easily access parent state in all contexts
                                    // But we can try to rely on the fact that subject_id is set.
                                    // Let's try to get the subject_id from the livewire component state or similar if possible.
                                    // Or better, use $get('../../subject_id')

                                    $subjectId = $get('../../subject_id');
                                    if (!$subjectId) {
                                        return [];
                                    }

                                    $subject = Subject::find($subjectId);
                                    if (!$subject) {
                                        return [];
                                    }

                                    return Student::whereHas('grades', function ($query) use ($subject) {
                                        $query->where('grades.id', $subject->grade_id);
                                    })->pluck('name', 'id');
                                })
                                ->searchable()
                                ->required()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                            Radio::make('status')
                                ->options(StatusAttendanceEnum::class)
                                ->inline()
                                ->required(),

                            Hidden::make('date')
                                ->default(fn($get) => $get('../../date')),
                        ])
                        ->addActionLabel('Tambah Siswa')
                        ->deletable()
                        ->defaultItems(0)
                        ->grid(1)
                        ->columnSpanFull(),
                ])
                ->hidden(fn($get) => $get('status') == TeachingStatusEnum::DITIADAKAN)
                ->columnSpanFull(),
        ];
    }
}
