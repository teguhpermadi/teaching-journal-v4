<?php

namespace App\Filament\Resources\Transcripts\Pages;

use App\Filament\Resources\Transcripts\TranscriptResource;
use App\Models\Subject;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTranscripts extends ListRecords
{
    protected static string $resource = TranscriptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $mySubjects = Subject::mySubjects()->get();

        $tabs = [];

        foreach ($mySubjects as $subject) {
            $tabs[$subject->code] = Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('subject_id', $subject->id));
        }

        return $tabs;
    }
}
