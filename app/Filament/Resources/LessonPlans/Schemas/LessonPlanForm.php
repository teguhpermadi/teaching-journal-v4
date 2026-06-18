<?php

namespace App\Filament\Resources\LessonPlans\Schemas;

use App\Models\AcademicYear;
use App\Models\MainTarget;
use App\Models\Subject;
use App\Models\Target;
use App\TeachingStatusEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class LessonPlanForm
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
                DatePicker::make('planned_date')
                    ->default(now())
                    ->required(),
                Select::make('subject_id')
                    ->options(
                        fn () => Subject::mySubjects()
                            ->get()
                            ->map(
                                fn ($subject) => [
                                    'label' => $subject->code.' - '.$subject->grade->name,
                                    'value' => $subject->id,
                                ]
                            )->pluck('label', 'value')
                    )
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) => $set('grade_id', Subject::find($state)?->grade_id))
                    ->searchable()
                    ->preload()
                    ->required(),
                ToggleButtons::make('status')
                    ->options(TeachingStatusEnum::class)
                    ->default(TeachingStatusEnum::PEMBELAJARAN)
                    ->columnSpanFull()
                    ->inline()
                    ->reactive()
                    ->required(),
                Select::make('target_id')
                    ->hidden(fn ($get) => ! $get('subject_id'))
                    ->options(
                        fn ($get) => Target::myTargetsInSubject($get('subject_id'))
                            ->get()
                            ->map(
                                fn ($target) => [
                                    'label' => $target->target,
                                    'value' => $target->id,
                                ]
                            )->pluck('label', 'value')
                    )
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->createOptionForm(function (Get $get) {
                        $subjectId = $get('subject_id');
                        $gradeId = $get('grade_id');
                        $academicYearId = $get('academic_year_id');
                        $userId = $get('user_id');

                        return [
                            Hidden::make('subject_id')
                                ->default($subjectId),
                            Hidden::make('grade_id')
                                ->default($gradeId),
                            Hidden::make('academic_year_id')
                                ->default($academicYearId),
                            Hidden::make('user_id')
                                ->default($userId),
                            Select::make('main_target_id')
                                ->label('Tujuan Utama')
                                ->options(
                                    fn () => MainTarget::myMainTargetsInSubject($subjectId)
                                        ->get()
                                        ->pluck('main_target', 'id')
                                )
                                ->searchable()
                                ->preload()
                                ->required()
                                ->createOptionForm([
                                    TextInput::make('main_target')
                                        ->label('Tujuan Utama Baru')
                                        ->required(),
                                ])
                                ->createOptionUsing(function (array $data) use ($subjectId, $gradeId, $academicYearId, $userId) {
                                    return MainTarget::create([
                                        'subject_id' => $subjectId,
                                        'grade_id' => $gradeId,
                                        'academic_year_id' => $academicYearId,
                                        'user_id' => $userId,
                                        'main_target' => $data['main_target'],
                                    ])->id;
                                }),
                            TextInput::make('target')
                                ->label('Target Baru')
                                ->required(),
                        ];
                    })
                    ->createOptionUsing(function (array $data, callable $get) {
                        return Target::create([
                            'subject_id' => $get('subject_id'),
                            'grade_id' => $get('grade_id'),
                            'academic_year_id' => $get('academic_year_id'),
                            'user_id' => $get('user_id'),
                            'main_target_id' => $data['main_target_id'],
                            'target' => $data['target'],
                        ])->id;
                    }),
                TextInput::make('topic')
                    ->columnSpanFull()
                    ->required(),
                RichEditor::make('learning_objectives')
                    ->label('Tujuan Pembelajaran')
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline'],
                        ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                        ['bulletList', 'orderedList'],
                        ['undo', 'redo'],
                    ])
                    ->columnSpanFull()
                    ->required(),
                RichEditor::make('activities')
                    ->label('Kegiatan Pembelajaran')
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline'],
                        ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                        ['bulletList', 'orderedList'],
                        ['table'],
                        ['undo', 'redo'],
                    ])
                    ->columnSpanFull()
                    ->required(),
                Toggle::make('has_materials_assessment')
                    ->label('Tambahkan Materi & Penilaian')
                    ->live()
                    ->columnSpanFull(),
                RichEditor::make('materials')
                    ->hidden(fn ($get) => ! $get('has_materials_assessment'))
                    ->label('Materi Ajar')
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline'],
                        ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                        ['bulletList', 'orderedList'],
                        ['table'],
                        ['undo', 'redo'],
                    ])
                    ->columnSpanFull(),
                RichEditor::make('assessment')
                    ->hidden(fn ($get) => ! $get('has_materials_assessment'))
                    ->label('Penilaian')
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline'],
                        ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                        ['bulletList', 'orderedList'],
                        ['table'],
                        ['undo', 'redo'],
                    ])
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('lesson_plan_files')
                    ->label('Lampiran')
                    ->disk('public')
                    ->multiple()
                    ->openable()
                    ->columnSpanFull()
                    ->collection('lesson_plan_files')
                    ->panelLayout('grid'),
            ]);
    }
}
