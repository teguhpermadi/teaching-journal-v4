<?php

namespace App\Filament\Resources\Signatures;

use App\Filament\Resources\Signatures\Pages\ListSignatures;
use App\Filament\Resources\Signatures\Tables\SignatureTable;
use App\Models\Journal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class SignatureResource extends Resource
{
    protected static ?string $model = Journal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static ?string $navigationLabel = 'Signatures';

    protected static ?string $modelLabel = 'Signature';

    protected static ?string $pluralModelLabel = 'Signatures';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('ViewAny:Signature');
    }

    public static function canViewAny(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('ViewAny:Signature');
    }

    public static function table(Table $table): Table
    {
        return SignatureTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSignatures::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

