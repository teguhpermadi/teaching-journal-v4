<?php

namespace App\Filament\Resources\Transcripts\RelationManagers;

use App\Models\Transcript;
use App\Models\TranscriptStudent;
use Filament\Actions\Action;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;

class TranscriptStudentsRelationManager extends RelationManager
{
    protected static string $relationship = 'transcriptStudents';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // TextInput::make('student.name')
                //     ->required()
                //     ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('student.name')
            ->columns([
                TextColumn::make('student.nis')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('student.name')
                    ->sortable()
                    ->searchable(),
                TextInputColumn::make('score')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('Generate')
                    ->action($this->generateTranscript()),
                // CreateAction::make(),
                // AssociateAction::make(),
            ])
            ->recordActions([
                // EditAction::make(),
                // DissociateAction::make(),
                // DeleteAction::make(),
            ])
            ->toolbarActions([
                // BulkActionGroup::make([
                //     DissociateBulkAction::make(),
                //     DeleteBulkAction::make(),
                // ]),
            ])
            ->paginated(false);
    }

    public function generateTranscript()
    {
        $transcript = $this->getOwnerRecord();
        $data = [
            'transcript_id' => $transcript->id,
            'academic_year_id' => $transcript->academic_year_id,
            'subject_id' => $transcript->subject_id,
            'grade_id' => $transcript->grade_id,
            'score' => 0,
        ];

        $students = $transcript->grade()->first()->students()->pluck('id');

        foreach ($students as $student) {
            TranscriptStudent::updateOrCreate([
                'transcript_id' => $transcript->id,
                'academic_year_id' => $transcript->academic_year_id,
                'subject_id' => $transcript->subject_id,
                'grade_id' => $transcript->grade_id,
                'student_id' => $student,
            ],[
                'score' => 0,
            ]);
        }
    }
}
