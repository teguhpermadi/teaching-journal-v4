<?php

namespace App\Filament\Resources\Subjects\Schemas;

use App\ScheduleEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SubjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Subject Name')
                    ->required(),
                TextInput::make('code')
                    ->label('Subject Code')
                    ->required(),
                Select::make('grade_id')
                    ->label('Grade')
                    ->relationship('grade', 'name')
                    ->required(),
                Select::make('schedule')
                    ->label('Schedule')
                    ->options(ScheduleEnum::class)
                    ->multiple()
                    ->required(),
            ]);
    }
}
