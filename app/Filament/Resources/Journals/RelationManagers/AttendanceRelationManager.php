<?php

namespace App\Filament\Resources\Journals\RelationManagers;

use App\Models\Grade;
use App\Models\Journal;
use App\Models\Student;
use App\StatusAttendanceEnum;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttendanceRelationManager extends RelationManager
{
    protected static string $relationship = 'attendance';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('journal_id')
                    ->default($this->getOwnerRecord()->id),
                Hidden::make('date')
                    ->default($this->getOwnerRecord()->date),
                Select::make('student_id')
                    ->options(
                        fn () => Grade::find($this->getOwnerRecord()->subject->grade_id)
                            ->studentWithoutAttendance($this->getOwnerRecord()->date)
                            ->pluck('name', 'id')
                            ->toArray())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpan('full'),
                Radio::make('status')
                    ->options(StatusAttendanceEnum::class)
                    ->required()
                    ->columnSpan('full'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('student.name')
            ->columns([
                TextColumn::make('student.name')
                    ->searchable(),
                TextColumn::make('student.nis')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Sick' => 'warning',
                        'Leave' => 'info',
                        'Absent' => 'danger',
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->slideOver()
                    ->modalHeading('Add Attendance')
                    ->modalDescription('Add attendance for this journal')
                    ->modalWidth('md'),
            ])
            ->recordActions([
                EditAction::make()
                    ->slideOver()
                    ->modalHeading('Edit Attendance')
                    ->modalDescription('Edit attendance for this journal')
                    ->modalWidth('md'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
