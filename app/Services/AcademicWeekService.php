<?php

namespace App\Services;

use App\AcademicStatusCalendarEnum;
use App\Models\AcademicYear;
use App\Settings\AcademicWeekSettings;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AcademicWeekService
{
    public function totalNonEffectiveWeeks(
        AcademicYear $academicYear,
        ?callable $callback = null,
    ): int {
        return $this->getNonEffectiveWeeks($academicYear, $callback)->count();
    }

    public function nonEffectiveWeeksByMonth(
        AcademicYear $academicYear,
        ?callable $callback = null,
    ): Collection {
        return $this->getNonEffectiveWeeks($academicYear, $callback)
            ->groupBy('month_number')
            ->map(fn (Collection $weeks, int $monthNumber) => [
                'month' => $weeks->first()['month'],
                'month_number' => $monthNumber,
                'total_weeks' => $weeks->count(),
            ])
            ->sortKeys()
            ->values();
    }

    public function getNonEffectiveWeeks(
        AcademicYear $academicYear,
        ?callable $callback = null,
    ): Collection {
        $settings = app(AcademicWeekSettings::class);

        $callback ??= fn (int $totalSchoolDays, int $nonEffectiveDays) => $nonEffectiveDays >= $settings->min_non_effective_days;

        $weeks = collect();

        $academicYear->academicCalendars()
            ->where('status', AcademicStatusCalendarEnum::NOT_EFFECTIVE)
            ->get()
            ->each(function ($event) use ($weeks) {
                $period = $event->start_date->toPeriod($event->end_date);

                foreach ($period as $date) {
                    if ($date->dayOfWeek === Carbon::SUNDAY) {
                        continue;
                    }

                    $monday = $date->copy()->startOfWeek(Carbon::MONDAY);
                    $weekKey = $monday->format('Y-m-d');

                    $week = $weeks->get($weekKey, [
                        'week_key' => $weekKey,
                        'monday' => $monday->format('Y-m-d'),
                        'month' => $monday->locale('id')->monthName,
                        'month_number' => $monday->month,
                        'total_school_days' => 6,
                        'non_effective_days' => 0,
                    ]);

                    $week['non_effective_days']++;
                    $weeks->put($weekKey, $week);
                }
            });

        return $weeks
            ->filter(fn (array $week) => $callback($week['total_school_days'], $week['non_effective_days']))
            ->values();
    }
}
