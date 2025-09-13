<?php

namespace App\Filament\Resources\Subjects\Schemas;

use App\ScheduleEnum;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Models\Grade;

class SubjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('user_id')
                    ->default(Auth::id()),
                TextInput::make('name')
                    ->label('Subject Name')
                    ->required(),
                TextInput::make('code')
                    ->label('Subject Code')
                    ->required(),
                Select::make('grade_id')
                    ->label('Grade')
                    ->relationship(name: 'grade', titleAttribute: 'name', modifyQueryUsing: fn (Builder $query) => Grade::gradeAcademicYearActive($query))
                    ->required(),
                Select::make('schedule')
                    ->label('Schedule')
                    ->options(ScheduleEnum::class)
                    ->multiple()
                    ->required(),
            ]);
    }
}
