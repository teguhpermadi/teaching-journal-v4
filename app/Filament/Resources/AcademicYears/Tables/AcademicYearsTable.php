<?php

namespace App\Filament\Resources\AcademicYears\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use App\Models\AcademicYear;
use App\Models\Scopes\AcademicYearScope;

class AcademicYearsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('year')
                    ->label('Academic Year')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('semester')
                    ->label('Semester')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('headmaster_name')
                    ->label('Headmaster')
                    ->searchable()
                    ->sortable(),
                ToggleColumn::make('active')
                    ->label('Active')
                    ->sortable()
                    ->afterStateUpdated(function ($state, $record) {
                        AcademicYear::setActive($record->id);
                    }),
                TextColumn::make('grades_count')
                    ->label('Grades Count')
                    ->counts('grades')
                    ->sortable(),
                TextColumn::make('subjects_count')
                    ->label('Subjects Count')
                    ->counts('subjects')
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
