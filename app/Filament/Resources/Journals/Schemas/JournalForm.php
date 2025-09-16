<?php

namespace App\Filament\Resources\Journals\Schemas;

use App\Models\AcademicYear;
use App\Models\Subject;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class JournalForm
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
                DatePicker::make('date')
                    ->default(now())
                    ->required(),
                Select::make('subject_id')
                    ->options(
                        fn () => Subject::mySubjects()
                        ->get()
                        ->map(
                            fn ($subject) => [
                                'label' => $subject->code . ' - ' . $subject->grade->name,
                                'value' => $subject->id
                            ]
                        )->pluck('label', 'value')
                    )
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('grade_id', Subject::find($state)->grade_id);
                    })
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('target')
                    ->columnSpan('full')
                    ->required(),
                TextInput::make('chapter')
                    ->columnSpan('full')
                    ->required(),
                RichEditor::make('activity')
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline'],
                        ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                        ['bulletList', 'orderedList'],
                        ['table'],
                        ['undo', 'redo'],
                    ])
                    ->columnSpan('full')
                    ->required(),
                Textarea::make('notes')
                    ->columnSpan('full'),
                SpatieMediaLibraryFileUpload::make('activity_photos')
                    ->hint('Upload photos of the activity')
                    ->label('Photos')
                    ->disk('public')
                    ->multiple()
                    ->openable()
                    ->collection('activity_photos')
                    ->image(),
            ]);
        }
}
