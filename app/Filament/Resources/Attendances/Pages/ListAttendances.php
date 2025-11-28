<?php

namespace App\Filament\Resources\Attendances\Pages;

use App\Filament\Resources\Attendances\AttendanceResource;
use App\Models\Subject;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('download')
                ->label('Download Laporan Kehadiran')
                ->icon('heroicon-o-document-arrow-down')
                ->modalHeading('Download Laporan Kehadiran Siswa')
                ->modalWidth('md')
                ->schema([
                    Select::make('id')
                        ->label('Siswa')
                        ->options(function () {
                            // Get students from my subjects' grades
                            $mySubjects = Subject::mySubjects()->with('grade.students')->get();
                            $students = collect();
                            
                            foreach ($mySubjects as $subject) {
                                if ($subject->grade && $subject->grade->students) {
                                    $students = $students->merge($subject->grade->students);
                                }
                            }
                            
                            // Remove duplicates and format for select
                            return $students->unique('id')
                                ->mapWithKeys(function ($student) {
                                    return [$student->id => $student->name . ' (' . ($student->nis ?? '-') . ')'];
                                })
                                ->sort();
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->placeholder('Pilih siswa'),
                    Select::make('month')
                        ->label('Bulan')
                        ->options([
                            1 => 'Januari',
                            2 => 'Februari',
                            3 => 'Maret',
                            4 => 'April',
                            5 => 'Mei',
                            6 => 'Juni',
                            7 => 'Juli',
                            8 => 'Agustus',
                            9 => 'September',
                            10 => 'Oktober',
                            11 => 'November',
                            12 => 'Desember',
                        ])
                        ->default(now()->month)
                        ->required()
                        ->placeholder('Pilih bulan'),
                ])
                ->action(function (array $data) {
                    // Redirect to download route for automatic Word download
                    return redirect()->route('download-attendance', [
                        'id' => $data['id'],
                        'month' => $data['month'],
                    ]);
                }),
        ];
    }
}
