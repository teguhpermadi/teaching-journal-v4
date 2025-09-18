<?php

namespace App\Filament\Resources\Journals\Pages;

use App\Filament\Resources\Journals\JournalResource;
use App\Filament\Resources\Journals\Widgets\JournalWidget;
use App\Jobs\JournalWordJob;
use App\Models\AcademicYear;
use App\Models\Journal;
use App\Models\Subject;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListJournals extends ListRecords
{
    protected static string $resource = JournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('download')
                ->modalWidth('md')
                ->slideOver()
                ->label('Download')
                ->schema([
                    Hidden::make('academic_year_id')
                        ->default(AcademicYear::active()->first()->id),
                    Hidden::make('user_id')
                        ->default(Auth::id()),
                    Hidden::make('grade_id')
                        ->reactive(),
                    Select::make('subject_id')
                        ->options(
                            fn() => Subject::mySubjects()
                                ->get()
                                ->map(
                                    fn($subject) => [
                                        'label' => $subject->code . ' - ' . $subject->grade->name,
                                        'value' => $subject->id
                                    ]
                                )->pluck('label', 'value')
                        )
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('grade_id', Subject::find($state)->grade_id);
                        })
                        ->searchable()
                        ->preload()
                        ->required(),
                    // Grid::make()
                    //     ->columns(2)
                    //     ->schema([
                    //         DatePicker::make('start_date')
                    //             ->label('Start Date'),
                    //         DatePicker::make('end_date')
                    //             ->label('End Date'),
                    //     ]),
                    Select::make('month')
                        ->options([
                            '1' => 'January',
                            '2' => 'February',
                            '3' => 'March',
                            '4' => 'April',
                            '5' => 'May',
                            '6' => 'June',
                            '7' => 'July',
                            '8' => 'August',
                            '9' => 'September',
                            '10' => 'October',
                            '11' => 'November',
                            '12' => 'December',
                        ])
                        ->required(),
                ])
                ->action(function (array $data) {
                    // Dispatch job dengan user ID untuk notifikasi
                    JournalWordJob::dispatch($data, Auth::id());
                }),
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

    protected function getHeaderWidgets(): array
    {
        return [
            JournalWidget::class,
        ];
    }
}
