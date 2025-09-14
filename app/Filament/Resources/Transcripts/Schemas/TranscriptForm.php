<?php

namespace App\Filament\Resources\Transcripts\Schemas;

use App\Models\AcademicYear;
use App\Models\Journal;
use App\Models\Subject;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class TranscriptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('academic_year_id')
                    ->default(AcademicYear::active()->first()->id),
                Hidden::make('user_id')
                    ->default(Auth::id()),
                Hidden::make('grade_id')
                    ->reactive(),
                Select::make('subject_id')
                    ->options(Subject::mySubjects()->get()->map(function ($subject) {
                        return [
                            'label' => $subject->name . ' - ' . $subject->grade->name,
                            'value' => $subject->id,
                        ];
                    })->pluck('label', 'value'))
                    ->afterStateUpdated(function ($state, $set) {
                        $set('grade_id', Subject::find($state)->grade_id);
                    })
                    ->reactive()
                    ->required(),
                Select::make('journal_id')
                    ->options(Journal::myJournals()->pluck('chapter', 'id'))
                    ->required(),
                Textarea::make('title')
                    ->required(),
                Textarea::make('description'),
            ]);
    }
}
