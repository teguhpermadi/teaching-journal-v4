<?php

namespace App\Filament\Resources\Journals\RelationManagers;

use App\Models\Transcript;
use Filament\Actions\Action;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class TranscriptsRelationManager extends RelationManager
{
    protected static string $relationship = 'transcripts';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('grade_id')
                    ->default($this->getOwnerRecord()->grade_id),
                Hidden::make('subject_id')
                    ->default($this->getOwnerRecord()->subject_id),
                Hidden::make('academic_year_id')
                    ->default($this->getOwnerRecord()->academic_year_id),
                Hidden::make('user_id')
                    ->default(Auth::id()),
                Textarea::make('title')
                    ->required(),
                Textarea::make('description'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([                
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('description')
                    ->wrap()
                    ->label('Description'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make()
                    ->slideOver()
                    ->preloadRecordSelect(),
            ])
            ->recordActions([
                // EditAction::make()
                //     ->slideOver(),
                Action::make('Edit')
                    ->url(fn (Transcript $record): string => route('filament.admin.resources.transcripts.edit', ['record' => $record])),
                DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
