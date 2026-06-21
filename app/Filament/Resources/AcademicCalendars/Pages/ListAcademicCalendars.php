<?php

namespace App\Filament\Resources\AcademicCalendars\Pages;

use App\Filament\Resources\AcademicCalendars\AcademicCalendarResource;
use App\Imports\AcademicCalendarImport;
use EightyNine\ExcelImport\ExcelImportAction;
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

                    $lines = array_filter(explode("\n", trim($output)));
                    $lastLine = end($lines);
                    $result = json_decode($lastLine, true);

                    if ($exitCode === 0 && $result) {
                        $parts = [];
                        if ($result['updated'] > 0) {
                            $parts[] = "{$result['updated']} data diperbarui";
                        }
                        if ($result['deleted'] > 0) {
                            $parts[] = "{$result['deleted']} data dihapus (orphaned)";
                        }
                        $summary = implode(', ', $parts) ?: 'Tidak ada perubahan';

                        Notification::make()
                            ->title('Sinkronisasi Berhasil')
                            ->success()
                            ->body("Sinkronisasi dari Google Calendar selesai.\n{$summary}.")
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Sinkronisasi Gagal')
                            ->danger()
                            ->body('Terjadi kesalahan saat sinkronisasi. Silakan coba lagi.')
                            ->send();
                    }
                }),
            ExcelImportAction::make()
                ->use(AcademicCalendarImport::class)
                ->slideOver()
                ->sampleExcel(
                    sampleData: [
                        ['title' => 'Hari Pertama Masuk Sekolah', 'date' => '2025-07-14', 'status' => 'Efektif', 'description' => 'Semester Ganjil 2025/2026'],
                        ['title' => 'Libur Hari Raya', 'date' => '2026-03-30', 'status' => 'Tidak Efektif', 'description' => 'Libur Nasional'],
                        ['title' => 'Penilaian Tengah Semester', 'date' => '2025-09-22', 'status' => 'Efektif', 'description' => 'PTS Ganjil'],
                        ['title' => 'Pembagian Raport', 'date' => '2025-12-20', 'status' => 'Tidak Efektif', 'description' => ''],
                    ],
                    fileName: 'academic-calendar-template.xlsx',
                    sampleButtonLabel: 'Download Template',
                ),
            CreateAction::make()
                ->modalWidth('lg'),
        ];
    }
}
