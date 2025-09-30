<?php

namespace App\Filament\Resources\Journals\Tables;

use App\Filament\Resources\Journals\RelationManagers\AttendanceRelationManager;
use App\Models\Target;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Guava\FilamentModalRelationManagers\Actions\RelationManagerAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class JournalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Date')
                    ->date('D, d M Y')
                    ->sortable(),
                SpatieMediaLibraryImageColumn::make('activity_photos')
                    ->label('Photos')
                    ->collection('activity_photos')
                    ->stacked()
                    ->limit(3)
                    ->circular(),
                // TextColumn::make('mainTarget.main_target')
                //     ->label('Main Target')
                //     ->limit(100)
                //     ->wrap()
                //     ->searchable(),
                TextColumn::make('chapter')
                    ->label('Chapter')
                    ->wrap()
                    ->sortable()
                    ->searchable(),
                // TextColumn::make('target_id')
                //     ->label('Target')
                //     ->getStateUsing(function ($record) {
                //         return collect($record->target_id)->map(function ($target_id) {
                //             return Target::find($target_id)->target;
                //         });
                //     })
                //     ->wrap()
                //     ->bulleted()
                //     ->searchable(),
                TextColumn::make('attendance_count')
                    ->counts('attendance')
                    ->label('Attendance Count'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                // RelationManagerAction::make('attendance-relation-manager')
                //     ->label('View Attendance')
                //     // ->slideOver()
                //     // ->modalWidth('lg')
                //     ->modalHeading('Attendance')
                //     ->modalDescription('Attendance for this journal')
                //     ->relationManager(AttendanceRelationManager::make())
                //     ->compact(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                // check if user role teacher
                if (Auth::user()->hasRole('teacher')) {
                    $query->myJournals();
                }
            })
            ->poll('10s');
    }
}
