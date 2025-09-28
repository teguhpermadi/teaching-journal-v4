<?php

namespace App\Filament\Resources\Attendances\Tables;

use App\Models\Grade;
use App\StatusAttendanceEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.name')
                    ->label('Student')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('student.grades.name')
                    ->label('Grade')
                    ->sortable(),
                TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->label('Status')
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->options(StatusAttendanceEnum::class)
                    ->label('Status'),
                // filter by grade
                SelectFilter::make('grade')
                    ->relationship('student.grades', 'name')
                    ->label('Grade'),
            ])
            ->recordActions([
                // EditAction::make(),
            ])
            ->toolbarActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make(),
                //     ForceDeleteBulkAction::make(),
                //     RestoreBulkAction::make(),
                // ]),
            ])
            ->defaultGroup('student.name', 'date')
            ->defaultSort(function (Builder $query): Builder {
                return $query->orderBy('date', 'desc');
            })
            ->modifyQueryUsing(function (Builder $query) {
                // check user role teacher
                if (auth()->user()->hasRole('teacher')) {
                    $query->myStudents();
                }
            });
    }
}
