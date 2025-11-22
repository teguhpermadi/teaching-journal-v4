<?php

namespace App\Filament\Resources\Journals\Pages;

use App\Filament\Resources\Journals\JournalResource;
use App\TeachingStatusEnum;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateJournal extends CreateRecord
{
    protected static string $resource = JournalResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // jika status ditiadakan maka setting chapter menjadi '-'
        if ($data['status'] == TeachingStatusEnum::DITIADAKAN) {
            $data['chapter'] = '-';
            $data['activity'] = '-';
        }

        return static::getModel()::create($data);
    }

    protected function afterCreate(): void
    {
        // Sync attendance dates
        $this->record->attendance()->update(['date' => $this->record->date]);
    }
}
