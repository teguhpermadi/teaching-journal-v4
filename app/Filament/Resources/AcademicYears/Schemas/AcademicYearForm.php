<?php

namespace App\Filament\Resources\AcademicYears\Schemas;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;

class AcademicYearForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('year')
                    ->label('Academic Year')
                    ->required()
                    ->maxLength(9)
                    ->mask('9999/9999')
                    ->placeholder('e.g., 2023/2024'),
                Select::make('semester')
                    ->label('Semester')
                    ->options([
                        'odd' => 'Odd',
                        'even' => 'Even',
                    ])
                    ->required()
                    ->placeholder('Select Semester'),
                TextInput::make('headmaster_name')
                    ->label('Headmaster Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Enter Headmaster Name'),
                TextInput::make('headmaster_nip')
                    ->label('Headmaster NIP')
                    ->required()
                    ->maxLength(20)
                    ->default('-')
                    ->placeholder('Enter Headmaster NIP'),
                DatePicker::make('date_start')
                    ->label('Start Date')
                    ->required()
                    ->placeholder('Select Start Date'),
                DatePicker::make('date_end')
                    ->label('End Date')
                    ->required()
                    ->placeholder('Select End Date'),
                Toggle::make('active')
                    ->label('Active')
                    ->default(false),
            ]);
    }
}
