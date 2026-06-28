<?php

namespace App\Filament\Resources\LessonPlans\Pages;

use App\Filament\Resources\LessonPlans\LessonPlanResource;
use App\Filament\Resources\LessonPlans\Widgets\LessonPlanWidget;
use App\Filament\Widgets\MonthStatsWidget;
use App\Models\LessonPlan;
use App\Models\Subject;
use Filament\Actions\CreateAction;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListLessonPlans extends ListRecords
{
    use ExposesTableToWidgets;

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
            $tabs['subject_'.$subject->id] = Tab::make()
                ->label($subject->code.' | '.$subject->grade->name)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('subject_id', $subject->id))
                ->badge(fn () => LessonPlan::where('subject_id', $subject->id)->count());
        }

        return $tabs;
    }

    public function updatedActiveTab(): void
    {
        parent::updatedActiveTab();

        $subjectId = null;
        if ($this->activeTab && str_starts_with((string) $this->activeTab, 'subject_')) {
            $subjectId = str_replace('subject_', '', (string) $this->activeTab);
        }

        $this->dispatch('activeTabChanged', subjectId: $subjectId);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MonthStatsWidget::class,
            LessonPlanWidget::class,
        ];
    }
}
