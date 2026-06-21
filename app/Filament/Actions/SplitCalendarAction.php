<?php

namespace App\Filament\Actions;

use App\Services\AcademicCalendarSplitService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Guava\Calendar\Concerns\CalendarAction;
use Guava\Calendar\Contracts\HasCalendar;

class SplitCalendarAction extends Action
{
    use CalendarAction;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Split')
            ->icon('heroicon-o-arrows-right-left')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Split Kegiatan')
            ->modalDescription(function (HasCalendar $livewire) {
                /** @var \Guava\Calendar\Filament\CalendarWidget $livewire */
                $record = $livewire->getEventRecord();

                if (! $record) {
                    return '';
                }

                $totalDays = $record->start_date->diffInDays($record->end_date) + 1;

                return "Apakah Anda yakin ingin membagi kegiatan '{$record->title}' ({$record->start_date->format('d/m/Y')} - {$record->end_date->format('d/m/Y')}) menjadi {$totalDays} hari terpisah?";
            })
            ->modalSubmitActionLabel('Ya, Split')
            ->action(function (HasCalendar $livewire) {
                /** @var \Guava\Calendar\Filament\CalendarWidget $livewire */
                $record = $livewire->getEventRecord();

                if (! $record) {
                    return;
                }

                try {
                    app(AcademicCalendarSplitService::class)->split($record);
                    $livewire->refreshRecords();
                    $livewire->dispatch('academic-calendar-updated');
                    Notification::make()
                        ->title('Kegiatan berhasil di-split')
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Gagal split kegiatan')
                        ->danger()
                        ->send();
                }
            })
            ->hidden(function (HasCalendar $livewire) {
                /** @var \Guava\Calendar\Filament\CalendarWidget $livewire */
                $record = $livewire->getEventRecord();

                return ! $record || $record->start_date->equalTo($record->end_date);
            });
    }
}
