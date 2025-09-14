<?php

namespace App\Filament\Resources\Transcripts;

use App\Filament\Resources\Transcripts\Pages\CreateTranscript;
use App\Filament\Resources\Transcripts\Pages\EditTranscript;
use App\Filament\Resources\Transcripts\Pages\ListTranscripts;
use App\Filament\Resources\Transcripts\Schemas\TranscriptForm;
use App\Filament\Resources\Transcripts\Tables\TranscriptsTable;
use App\Models\Transcript;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TranscriptResource extends Resource
{
    protected static ?string $model = Transcript::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return TranscriptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TranscriptsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTranscripts::route('/'),
            'create' => CreateTranscript::route('/create'),
            'edit' => EditTranscript::route('/{record}/edit'),
        ];
    }
}
