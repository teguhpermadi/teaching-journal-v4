<?php

namespace App\Filament\Resources\Attendances\Schemas;

use App\StatusAttendanceEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class AttendanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('student_id')
                    ->label('Student')
                    ->searchable()
                    ->relationship('student', 'name')
                    ->required(),
                Select::make('journal_id')
                    ->label('Journal')
                    ->searchable()
                    ->relationship('journal', 'chapter')
                    ->required(),
                Select::make('status')
                    ->label('Status')
                    ->options(StatusAttendanceEnum::class)
                    ->required(),
                DatePicker::make('date')
                    ->label('Date')
                    ->required(),
            ]);
    }
}
