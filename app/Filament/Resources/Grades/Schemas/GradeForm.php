<?php

namespace App\Filament\Resources\Grades\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class GradeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Grade Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('level')
                    ->label('Level')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(12),
                Select::make('academic_year_id')
                    ->label('Academic Year')
                    ->relationship('academicYear', 'year')
                    ->required(),
            ]);
    }
}
