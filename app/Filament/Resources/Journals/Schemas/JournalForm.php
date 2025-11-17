<?php

namespace App\Filament\Resources\Journals\Schemas;

use App\Models\AcademicYear;
use App\Models\MainTarget;
use App\Models\Subject;
use App\Models\Target;
use App\TeachingStatusEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
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
                Radio::make('status')
                    ->options(TeachingStatusEnum::class)
                    ->default(TeachingStatusEnum::PEMBELAJARAN)
                    ->columnSpanFull()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('main_target_id', []);
                        $set('target_id', []);
                    })
                    ->inline()
                    ->reactive()
                    ->required(),
                Select::make('main_target_id')
                    ->visible(fn ($get) => $get('status') == TeachingStatusEnum::PEMBELAJARAN)
                    ->options(
                        function ($get){
                            $mainTargets = MainTarget::myMainTargetsInSubject($get('subject_id'))
                            ->get();

                            if($mainTargets->isEmpty()){
                                return [];
                            }

                            return $mainTargets->map(
                                fn ($mainTarget) => [
                                    'label' => $mainTarget->main_target,
                                    'value' => $mainTarget->id
                                ]
                            )->pluck('label', 'value');
                        }
                    )
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Select::make('target_id')
                    ->visible(fn ($get) => $get('status') == TeachingStatusEnum::PEMBELAJARAN)
                    ->options(
                        function ($get){
                            $targets = Target::myTargetsInSubject($get('subject_id'))
                            ->whereIn('main_target_id', $get('main_target_id'))
                            ->get();

                            if($targets->isEmpty()){
                                return [];
                            }

                            return $targets->map(
                                fn ($target) => [
                                    'label' => $target->target,
                                    'value' => $target->id
                                ]
                            )->pluck('label', 'value');
                        }
                    )
                    ->multiple()
                    ->createOptionForm([
                        Hidden::make('subject_id')
                            ->reactive()
                            ->default(fn ($get) => $get('subject_id')),
                        // Hidden::make('grade_id')
                        //     ->reactive()
                        //     ->default(fn ($get) => $get('grade_id')),
                        // Hidden::make('academic_year_id')
                        //     ->reactive()
                        //     ->default(fn ($get) => $get('academic_year_id')),
                        // Hidden::make('user_id')
                        //     ->reactive()
                        //     ->default(fn ($get) => $get('user_id')),
                        Select::make('main_target_id')
                            ->options(
                                function ($get) {
                                    $mainTargets = MainTarget::myMainTargetsInSubject($get('subject_id'))
                                        ->get();

                                    if ($mainTargets->isEmpty()) {
                                        return [];
                                    }

                                    return $mainTargets->map(
                                        fn($mainTarget) => [
                                            'label' => $mainTarget->main_target,
                                            'value' => $mainTarget->id
                                        ]
                                    )->pluck('label', 'value');
                                }
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('target')
                            ->required(),
                    ])
                    ->createOptionUsing(function($data, $get){
                        // dd($get('subject_id'));
                        Target::create([
                            'subject_id' => $get('subject_id'),
                            'grade_id' => $get('grade_id'),
                            'academic_year_id' => $get('academic_year_id'),
                            'user_id' => $get('user_id'),
                            'target' => $data['target'],
                            'main_target_id' => $get('main_target_id'),
                        ]);
                    })
                    ->reactive()
                    ->searchable()
                    ->preload(),
                TextInput::make('chapter')
                    ->hidden(fn($get) => $get('status') == TeachingStatusEnum::DITIADAKAN)
                    ->columnSpan('full')
                    ->required(),
                RichEditor::make('activity')
                    ->hidden(fn($get) => $get('status') == TeachingStatusEnum::DITIADAKAN)
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline'],
                        ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                        ['bulletList', 'orderedList'],
                        ['table'],
                        ['undo', 'redo'],
                    ])
                    ->columnSpan('full')
                    ->required(),
                RichEditor::make('notes')
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline'],
                        ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                        ['bulletList', 'orderedList'],
                        ['table'],
                        ['undo', 'redo'],
                    ])
                    ->columnSpan('full'),
                SpatieMediaLibraryFileUpload::make('activity_photos')
                    ->hint('Upload photos of the activity')
                    ->label('Photos')
                    ->disk('public')
                    ->multiple()
                    ->openable()
                    ->columnSpanFull()
                    ->collection('activity_photos')
                    ->panelLayout('grid')
                    ->hidden(fn($get) => $get('status') == TeachingStatusEnum::DITIADAKAN)
                    ->image(),
            ]);
        }
}
