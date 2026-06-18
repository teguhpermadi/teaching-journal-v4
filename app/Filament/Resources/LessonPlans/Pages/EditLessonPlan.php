<?php

namespace App\Filament\Resources\LessonPlans\Pages;

use App\Filament\Resources\LessonPlans\LessonPlanResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Nben\FilamentRecordNav\Actions\NextRecordAction;
use Nben\FilamentRecordNav\Actions\PreviousRecordAction;
use Nben\FilamentRecordNav\Enums\NavigationPage;

class EditLessonPlan extends EditRecord
{
    protected static string $resource = LessonPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
            PreviousRecordAction::make()->navigateTo(NavigationPage::Edit),
            NextRecordAction::make()->navigateTo(NavigationPage::Edit),
        ];
    }

    public function getPreviousRecord(): ?Model
    {
        return $this->getRecord()
            ->newQuery()
            ->where('user_id', Auth::id())
            ->where('subject_id', $this->getRecord()->subject_id)
            ->where('planned_date', '<', $this->getRecord()->planned_date)
            ->reorder()
            ->orderBy('planned_date', 'desc')
            ->first();
    }

    public function getNextRecord(): ?Model
    {
        return $this->getRecord()
            ->newQuery()
            ->where('user_id', Auth::id())
            ->where('subject_id', $this->getRecord()->subject_id)
            ->where('planned_date', '>', $this->getRecord()->planned_date)
            ->reorder()
            ->orderBy('planned_date', 'asc')
            ->first();
    }
}
