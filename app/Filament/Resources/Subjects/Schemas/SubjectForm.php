<?php

namespace App\Filament\Resources\Subjects\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Models\AcademicYear;
use App\Models\Grade;
use Colors\RandomColor;
use Filament\Forms\Components\ColorPicker;

class SubjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('user_id')
                    ->default(Auth::id()),
                Hidden::make('academic_year_id')
                    ->default(AcademicYear::active()->first()->id),
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
                ColorPicker::make('color')
                    ->default(RandomColor::one())
                    ->label('Color')
                    ->required(),
            ]);
    }
}
