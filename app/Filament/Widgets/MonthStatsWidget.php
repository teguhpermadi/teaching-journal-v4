<?php

namespace App\Filament\Widgets;

use App\AcademicStatusCalendarEnum;
use App\Models\AcademicYear;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateInterval;
use DatePeriod;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class MonthStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public ?string $month = null;

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
    }

    #[On('monthChanged')]
    public function onMonthChanged(string $month): void
    {
        $this->month = $month;
    }

    protected function getStats(): array
    {
        $activeYear = AcademicYear::active()->first();
        if (! $activeYear) {
            return [
                Stat::make('Tahun Akademik', 'Tidak ada')
                    ->description('Aktifkan tahun akademik terlebih dahulu')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('warning'),
            ];
        }

        $monthStr = $this->month ?? now()->format('Y-m');
        $startOfMonth = CarbonImmutable::parse($monthStr.'-01');
        $endOfMonth = $startOfMonth->endOfMonth();

        $events = $activeYear->academicCalendars()
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('start_date', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('end_date', [$startOfMonth, $endOfMonth])
                    ->orWhere(function ($q) use ($startOfMonth, $endOfMonth) {
                        $q->where('start_date', '<=', $startOfMonth)
                            ->where('end_date', '>=', $endOfMonth);
                    });
            })
            ->get();

        $effectiveDays = 0;
        $nonEffectiveDays = 0;

        $period = new DatePeriod($startOfMonth, new DateInterval('P1D'), $endOfMonth->addDay());

        foreach ($period as $date) {
            if ((int) $date->format('w') === 0) {
                continue;
            }

            $dayEvents = $events->filter(fn ($event) => $date >= $event->start_date && $date <= $event->end_date);

            $hasNonEffective = $dayEvents->contains(fn ($e) => $e->status === AcademicStatusCalendarEnum::NOT_EFFECTIVE);

            if ($hasNonEffective) {
                $nonEffectiveDays++;
            } else {
                $effectiveDays++;
            }
        }

        $weekStats = $this->calculateWeekStats($startOfMonth, $endOfMonth, $events);
        $totalWeeks = $weekStats['total_weeks'];
        $nonEffectiveWeeks = $weekStats['non_effective_weeks'];
        $effectiveWeeks = $totalWeeks - $nonEffectiveWeeks;

        $monthName = $startOfMonth->locale('id')->isoFormat('MMMM YYYY');

        return [
            Stat::make('Hari Efektif', $effectiveDays)
                ->description("{$monthName} — Senin s/d Sabtu")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Hari Tidak Efektif', $nonEffectiveDays)
                ->description("{$monthName} — Senin s/d Sabtu")
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($nonEffectiveDays > 0 ? 'danger' : 'success'),

            Stat::make('Minggu Efektif', $effectiveWeeks)
                ->description("Dari total {$totalWeeks} minggu")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),

            Stat::make('Minggu Tidak Efektif', $nonEffectiveWeeks)
                ->description('≥ 3 hari tidak efektif per minggu')
                ->descriptionIcon('heroicon-m-no-symbol')
                ->color($nonEffectiveWeeks > 0 ? 'warning' : 'success'),
        ];
    }

    private function calculateWeekStats(CarbonImmutable $startOfMonth, CarbonImmutable $endOfMonth, $events): array
    {
        $minNonEffectiveDays = app(\App\Settings\AcademicWeekSettings::class)->min_non_effective_days;

        $weeks = [];
        $totalWeeks = 0;
        $nonEffectiveWeeks = 0;

        $period = new DatePeriod($startOfMonth, new DateInterval('P1D'), $endOfMonth->addDay());

        foreach ($period as $date) {
            if ((int) $date->format('w') === 0) {
                continue;
            }

            $monday = CarbonImmutable::instance($date)->startOfWeek(Carbon::MONDAY);
            $weekKey = $monday->format('Y-m-d');

            if (! isset($weeks[$weekKey])) {
                $weeks[$weekKey] = [
                    'non_effective_days' => 0,
                    'total_days' => 0,
                ];
            }

            $weeks[$weekKey]['total_days']++;

            $dayEvents = $events->filter(fn ($event) => $date >= $event->start_date && $date <= $event->end_date);
            $hasNonEffective = $dayEvents->contains(fn ($e) => $e->status === AcademicStatusCalendarEnum::NOT_EFFECTIVE);

            if ($hasNonEffective) {
                $weeks[$weekKey]['non_effective_days']++;
            }
        }

        $totalWeeks = count($weeks);
        $nonEffectiveWeeks = 0;

        foreach ($weeks as $week) {
            if ($week['non_effective_days'] >= $minNonEffectiveDays) {
                $nonEffectiveWeeks++;
            }
        }

        return [
            'total_weeks' => $totalWeeks,
            'non_effective_weeks' => $nonEffectiveWeeks,
        ];
    }
}
