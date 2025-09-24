<?php

namespace App\Filament\Resources\Students\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Laravolt\Avatar\Facade as Avatar;
use Illuminate\Database\Eloquent\Builder;

class StudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo')
                    ->label('Foto')
                    ->defaultImageUrl(function ($record) {
                        return (string) Avatar::create($record->name)->toBase64();
                    })
                    ->disk('public')
                    ->circular(),
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('nick_name')
                    ->sortable()
                    ->searchable(),
                // TextColumn::make('city_born')
                //     ->sortable()
                //     ->searchable(),
                // TextColumn::make('birthday')
                //     ->sortable()
                //     ->searchable(),
                TextColumn::make('nisn')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('nis')
                    ->sortable()
                    ->searchable(),
                // IconColumn::make('active')
                //     ->boolean()
                //     ->trueIcon('heroicon-o-check-circle')
                //     ->falseIcon('heroicon-o-x-circle')
                //     ->label('Active'),
                ToggleColumn::make('active')
                    ->label('Active'),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                TrashedFilter::make(),
                Filter::make('active')
                    ->query(function (Builder $query) {
                        $query->where('active', true);
                    })
                    ->label('Active'),
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
