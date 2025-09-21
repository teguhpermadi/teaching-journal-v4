<?php

namespace App\Filament\Resources\Students\Pages;

use App\Exports\StudentExport;
use App\Filament\Resources\Students\StudentResource;
use App\Imports\StudentImport;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExcelImportAction::make()
                ->use(StudentImport::class)
                ->sampleExcel(
                    sampleData: [
                        [
                            'name' => 'John Doe',
                            'gender' => 'Male',
                            'nisn' => '1234567890',
                            'nis' => '1234567890',
                        ],
                        [
                            'name' => 'Jane Doe',
                            'gender' => 'Female',
                            'nisn' => '1234567890',
                            'nis' => '1234567890',
                        ],
                    ],
                    fileName: 'students-template.xlsx',
                    sampleButtonLabel: 'Download Template',
                ),
        ];
    }
}
