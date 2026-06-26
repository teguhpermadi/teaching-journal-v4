<?php

namespace App\Filament\Resources\LessonPlans;

use App\Filament\Resources\LessonPlans\Pages\CreateLessonPlan;
use App\Filament\Resources\LessonPlans\Pages\EditLessonPlan;
use App\Filament\Resources\LessonPlans\Pages\ListLessonPlans;
use App\Filament\Resources\LessonPlans\Schemas\LessonPlanForm;
use App\Filament\Resources\LessonPlans\Tables\LessonPlansTable;
use App\Filament\Resources\LessonPlans\Widgets\LessonPlanWidget;
use App\Models\LessonPlan;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class LessonPlanResource extends Resource
{
    protected static ?string $model = LessonPlan::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'topic';

    protected static ?string $pluralModelLabel = 'Rencana Pembelajaran';

    protected static ?string $modelLabel = 'Rencana Pembelajaran';

    public static function form(Schema $schema): Schema
    {
        return LessonPlanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LessonPlansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLessonPlans::route('/'),
            'create' => CreateLessonPlan::route('/create'),
            'edit' => EditLessonPlan::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            LessonPlanWidget::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->when(
            ! Auth::user()?->hasRole('admin') && ! Auth::user()?->hasRole('headmaster'),
            fn (Builder $query) => $query->where('user_id', Auth::id())
        );
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->when(
                ! Auth::user()?->hasRole('admin') && ! Auth::user()?->hasRole('headmaster'),
                fn (Builder $query) => $query->where('user_id', Auth::id())
            );
    }
}
