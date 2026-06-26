<?php

namespace App\Filament\Resources\LessonPlans\Pages;

use App\Filament\Resources\LessonPlans\LessonPlanResource;
use App\Filament\Resources\LessonPlans\Widgets\LessonPlanWidget;
use App\Models\LessonPlan;
use App\Models\Subject;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListLessonPlans extends ListRecords
{
    protected static string $resource = LessonPlanResource::class;

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
            $tabs[$subject->code.' | '.$subject->grade->name] = Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('subject_id', $subject->id))
                ->badge(fn () => LessonPlan::where('subject_id', $subject->id)->count());
        }

        return $tabs;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LessonPlanWidget::class,
        ];
    }
}
