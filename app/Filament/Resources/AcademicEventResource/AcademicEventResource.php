<?php

namespace App\Filament\Resources\AcademicEventResource;

use App\Filament\Resources\AcademicEventResource\Pages\CreateAcademicEvent;
use App\Filament\Resources\AcademicEventResource\Pages\EditAcademicEvent;
use App\Filament\Resources\AcademicEventResource\Pages\ListAcademicEvents;
use App\Filament\Resources\AcademicEventResource\Schemas\AcademicEventForm;
use App\Filament\Resources\AcademicEventResource\Tables\AcademicEventsTable;
use App\Models\AcademicEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AcademicEventResource extends Resource
{
    protected static ?string $model = AcademicEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Kalender Pendidikan';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|UnitEnum|null $navigationGroup = 'Kalender Pendidikan';

    public static function form(Schema $schema): Schema
    {
        return AcademicEventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AcademicEventsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAcademicEvents::route('/'),
            'create' => CreateAcademicEvent::route('/create'),
            'edit' => EditAcademicEvent::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\AcademicCalendarStatsWidget::class,
        ];
    }
}
