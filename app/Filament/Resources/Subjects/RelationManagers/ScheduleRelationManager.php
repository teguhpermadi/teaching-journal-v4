<?php

namespace App\Filament\Resources\Subjects\RelationManagers;

use App\ScheduleEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ScheduleRelationManager extends RelationManager
{
    protected static string $relationship = 'schedule';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('days')
                    ->multiple()
                    ->options(ScheduleEnum::class)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('days')
            ->columns([
                TextColumn::make('days')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return '-';
                        }

                        $days = collect($state)->map(function ($day) {
                            $enum = ScheduleEnum::tryFrom($day);

                            return $enum?->getLabel() ?? $day;
                        });

                        return $days->join(', ');
                    })
                    ->badge()
                    ->color('primary'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
