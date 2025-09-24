<?php

namespace App\Filament\Resources\Journals\Tables;

use App\Models\Target;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
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
                    ->date('d, M Y')
                    ->sortable(),
                TextColumn::make('mainTarget.main_target')
                    ->label('Main Target')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('chapter')
                    ->label('Chapter')
                    ->wrap()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('target_id')
                    ->label('Target')
                    ->getStateUsing(function ($record) {
                        return collect($record->target_id)->map(function ($target_id) {
                            return Target::find($target_id)->target;
                        });
                    })
                    ->wrap()
                    ->bulleted()
                    ->searchable(),
                TextColumn::make('attendance_count')
                    ->counts('attendance')
                    ->label('Attendance Count'),
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
