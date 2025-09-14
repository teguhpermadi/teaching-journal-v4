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

class TranscriptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('academic_year_id')
                    ->default(AcademicYear::active()->first()->id),
                Select::make('subject_id')
                    ->options(Subject::mySubjects()->pluck('name', 'id'))
                    ->required(),
                Select::make('journal_id')
                    ->options(Journal::myJournals()->pluck('chapter', 'id'))
                    ->required(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->required(),
            ]);
    }
}
