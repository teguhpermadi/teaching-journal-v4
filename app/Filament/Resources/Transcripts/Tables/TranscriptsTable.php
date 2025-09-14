<?php

namespace App\Filament\Resources\Transcripts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TranscriptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject.name')
                    ->label('Subject'),
                TextColumn::make('journal.chapter')
                    ->wrap()
                    ->label('Journal Chapter'),
                TextColumn::make('title')
                    ->wrap()
                    ->label('Title'),
                TextColumn::make('description')
                    ->wrap()
                    ->label('Description'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $query->myTranscripts();
            });
    }
}
