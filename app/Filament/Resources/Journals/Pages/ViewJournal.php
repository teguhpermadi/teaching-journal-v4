<?php

namespace App\Filament\Resources\Journals\Pages;

use App\Filament\Resources\Journals\JournalResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Nben\FilamentRecordNav\Actions\NextRecordAction;
use Nben\FilamentRecordNav\Actions\PreviousRecordAction;

class ViewJournal extends ViewRecord
{
    protected static string $resource = JournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            PreviousRecordAction::make(),
            NextRecordAction::make(),
        ];
    }

    public function getPreviousRecord(): ?Model
    {
        return $this->getRecord()
            ->newQuery()
            ->where('user_id', Auth::id())
            ->where('subject_id', $this->getRecord()->subject_id)
            ->where('date', '<', $this->getRecord()->date)
            ->reorder()
            ->orderBy('date', 'desc')
            ->first();
    }

    public function getNextRecord(): ?Model
    {
        return $this->getRecord()
            ->newQuery()
            ->where('user_id', Auth::id())
            ->where('subject_id', $this->getRecord()->subject_id)
            ->where('date', '>', $this->getRecord()->date)
            ->reorder()
            ->orderBy('date', 'asc')
            ->first();
    }
}
