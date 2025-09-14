<?php

namespace App\Filament\Resources\Transcripts\Pages;

use App\Filament\Resources\Transcripts\TranscriptResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTranscript extends EditRecord
{
    protected static string $resource = TranscriptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
