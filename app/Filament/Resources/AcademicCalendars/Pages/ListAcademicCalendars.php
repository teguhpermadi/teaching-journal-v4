<?php

namespace App\Filament\Resources\AcademicCalendars\Pages;

use App\Filament\Resources\AcademicCalendars\AcademicCalendarResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

class ListAcademicCalendars extends ListRecords
{
    protected static string $resource = AcademicCalendarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncFromGoogle')
                ->label('Sync Google Calendar')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Sync Google Calendar')
                ->modalDescription('Sinkronisasi data dari Google Calendar ke database lokal. Data yang diubah di Google Calendar akan diperbarui.')
                ->modalSubmitActionLabel('Sync')
                ->action(function () {
                    $exitCode = Artisan::call('academic-calendar:sync-from-google');
                    $output = Artisan::output();

                    if ($exitCode === 0) {
                        Notification::make()
                            ->title('Sinkronisasi Berhasil')
                            ->success()
                            ->body($output)
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Sinkronisasi Gagal')
                            ->danger()
                            ->body($output)
                            ->send();
                    }
                }),
            CreateAction::make(),
        ];
    }
}
