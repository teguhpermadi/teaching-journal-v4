<?php

namespace App\Filament\Resources\Signatures\Pages;

use App\Filament\Resources\Signatures\SignatureResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSignatures extends ListRecords
{
    protected static string $resource = SignatureResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Jurnal')
                ->badge(fn () => \App\Models\Journal::count())
                ->modifyQueryUsing(fn (Builder $query) => $query),
            
            'unsigned' => Tab::make('Belum Ditandatangani')
                ->badge(function () {
                    return \App\Models\Journal::whereDoesntHave('signatures', function ($q) {
                        $q->where('signer_role', 'headmaster');
                    })->count();
                })
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->whereDoesntHave('signatures', function ($q) {
                        $q->where('signer_role', 'headmaster');
                    });
                }),
            
            'signed' => Tab::make('Sudah Ditandatangani')
                ->badge(function () {
                    return \App\Models\Journal::whereHas('signatures', function ($q) {
                        $q->where('signer_role', 'headmaster');
                    })->count();
                })
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->whereHas('signatures', function ($q) {
                        $q->where('signer_role', 'headmaster');
                    });
                }),
        ];
    }
}

