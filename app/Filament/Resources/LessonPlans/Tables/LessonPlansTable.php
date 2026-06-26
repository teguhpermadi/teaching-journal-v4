<?php

namespace App\Filament\Resources\LessonPlans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class LessonPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('planned_date')
                    ->label('Tanggal')
                    ->date('D, d M Y')
                    ->sortable(),
                TextColumn::make('topic')
                    ->label('Topik')
                    ->wrap()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('subject.code')
                    ->label('Mapel')
                    ->sortable(),
                TextColumn::make('grade.name')
                    ->label('Kelas')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Guru')
                    ->visible(fn () => Auth::user()?->hasRole('admin') || Auth::user()?->hasRole('headmaster')),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('planned_date', 'desc')
            ->poll('10s');
    }
}
