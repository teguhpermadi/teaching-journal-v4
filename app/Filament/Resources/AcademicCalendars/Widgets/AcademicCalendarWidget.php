<?php

namespace App\Filament\Resources\AcademicCalendars\Widgets;

use App\AcademicCalendarColorEnum;
use App\AcademicStatusCalendarEnum;
use App\Filament\Actions\SplitCalendarAction;
use App\Models\AcademicCalendar;
use App\Models\AcademicYear;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Guava\Calendar\Filament\Actions\CreateAction;
use Guava\Calendar\Filament\Actions\DeleteAction;
use Guava\Calendar\Filament\Actions\EditAction;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\DateClickInfo;
use Guava\Calendar\ValueObjects\DatesSetInfo;
use Guava\Calendar\ValueObjects\EventDropInfo;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class AcademicCalendarWidget extends CalendarWidget
{
    protected ?string $locale = 'id';

    protected bool $dateClickEnabled = true;

    protected bool $eventClickEnabled = true;

    protected ?string $defaultEventClickAction = null;

    protected bool $eventDragEnabled = true;

    public ?string $selectedDate = null;

    protected bool $datesSetEnabled = true;

    protected function onDatesSet(DatesSetInfo $info): void
    {
        $this->dispatch('monthChanged', month: $info->start->addDays(15)->format('Y-m'));
    }

    #[On('academic-calendar-updated')]
    public function refreshFromEvent(): void
    {
        $this->refreshRecords();
    }

    protected function onEventDrop(EventDropInfo $info, Model $event): bool
    {
        if ($event instanceof AcademicCalendar) {
            try {
                $reflection = new \ReflectionClass($info->event);
                $startProperty = $reflection->getProperty('start');
                $startProperty->setAccessible(true);
                $newStart = $startProperty->getValue($info->event);

                $newStartDate = $newStart->format('Y-m-d');

                $originalDuration = $event->start_date->diffInDays($event->end_date);
                $newEndDate = \Carbon\Carbon::parse($newStartDate)
                    ->addDays($originalDuration)
                    ->format('Y-m-d');

                $event->update([
                    'start_date' => $newStartDate,
                    'end_date' => $newEndDate,
                ]);

                $this->refreshRecords();

                Notification::make()
                    ->title('Kegiatan berhasil dipindahkan')
                    ->success()
                    ->send();

                return true;
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }

    protected function getEvents(FetchInfo $info): Collection|array|Builder
    {
        return AcademicCalendar::query();
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
        $this->mountAction('createAcademicCalendarAction');
    }

    protected function getEventClickContextMenuActions(): array
    {
        return [
            $this->editAction(),
            SplitCalendarAction::make('split'),
            $this->deleteAction(),
        ];
    }

    public function createAcademicCalendarAction(): CreateAction
    {
        return $this->createAction(AcademicCalendar::class)
            ->slideOver()
            ->form($this->getFormSchema())
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
                    'start_date' => $dateToUse,
                    'end_date' => $dateToUse,
                ];
            })
            ->mutateFormDataUsing(function (array $data): array {
                $data['user_id'] = Auth::id();
                $data['academic_year_id'] = AcademicYear::active()->first()?->id;

                return $data;
            })
            ->after(function () {
                $this->refreshRecords();
            });
    }

    public function editAction(): EditAction
    {
        return EditAction::make()
            ->slideOver()
            ->form($this->getFormSchema())
            ->after(function () {
                $this->refreshRecords();

                Notification::make()
                    ->title('Kegiatan berhasil diupdate')
                    ->success()
                    ->send();
            });
    }

    public function deleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->requiresConfirmation()
            ->modalHeading('Hapus Kegiatan')
            ->modalDescription('Apakah Anda yakin ingin menghapus kegiatan ini?')
            ->modalSubmitActionLabel('Ya, Hapus')
            ->after(function () {
                $this->refreshRecords();

                Notification::make()
                    ->title('Kegiatan berhasil dihapus')
                    ->success()
                    ->send();
            });
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('title')
                ->label('Judul')
                ->required()
                ->maxLength(255)
                ->placeholder('Masukkan judul kegiatan'),
            DatePicker::make('start_date')
                ->label('Tanggal Mulai')
                ->required()
                ->placeholder('Pilih tanggal mulai'),
            DatePicker::make('end_date')
                ->label('Tanggal Selesai')
                ->required()
                ->afterOrEqual('start_date')
                ->default(fn ($get) => $get('start_date'))
                ->placeholder('Pilih tanggal selesai'),
            Select::make('status')
                ->label('Status')
                ->options(AcademicStatusCalendarEnum::class)
                ->default(AcademicStatusCalendarEnum::EFFECTIVE)
                ->reactive()
                ->required(),
            Select::make('color')
                ->label('Warna')
                ->native(false)
                ->options(AcademicCalendarColorEnum::optionsWithPreview())
                ->allowHtml()
                ->default(fn (callable $get) => $get('status') === AcademicStatusCalendarEnum::EFFECTIVE->value
                    ? AcademicCalendarColorEnum::SAGE->value
                    : AcademicCalendarColorEnum::TOMATO->value
                ),
            Select::make('grades')
                ->multiple()
                ->relationship('grades', 'name')
                ->preload()
                ->label('Kelas')
                ->placeholder('Kosongkan jika untuk semua kelas'),
            Textarea::make('description')
                ->label('Deskripsi')
                ->nullable()
                ->columnSpanFull(),
        ];
    }
}
