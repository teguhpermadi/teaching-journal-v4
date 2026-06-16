<?php

namespace App\Services;

use App\AcademicEventType;
use App\Models\AcademicYear;

class AcademicCalendarService
{
    public function getActiveWeekdays(AcademicYear $year): array
    {
        $weekdays = [1, 2, 3, 4, 5]; // Senin-Jumat (Carbon: 1=Mon, 7=Sun)

        if ($year->saturday_is_active) {
            $weekdays[] = 6;
        }

        return $weekdays;
    }

    public function getEffectiveDays(AcademicYear $year): int
    {
        return $this->getEffectiveDayCollection($year)->count();
    }

    public function getNonEffectiveDays(AcademicYear $year): int
    {
        $events = $year->academicEvents()
            ->whereIn('type', [
                AcademicEventType::LIBUR_NASIONAL,
                AcademicEventType::LIBUR_SEKOLAH,
                AcademicEventType::HARI_TIDAK_EFEKTIF,
            ])
            ->get();

        $days = collect();

        foreach ($events as $event) {
            $period = $event->start_date->toPeriod($event->end_date ?? $event->start_date);
            foreach ($period as $date) {
                $days->push($date->format('Y-m-d'));
            }
        }

        return $days->unique()->count();
    }

    public function getEffectiveWeeks(AcademicYear $year): int
    {
        $breakdown = $this->getWeeklyBreakdown($year);

        return $breakdown->filter(fn ($week) => $week['effective_days'] > 0)->count();
    }

    public function getNonEffectiveWeeks(AcademicYear $year): int
    {
        $breakdown = $this->getWeeklyBreakdown($year);

        return $breakdown->filter(fn ($week) => $week['effective_days'] === 0)->count();
    }

    public function getWeeklyBreakdown(AcademicYear $year): \Illuminate\Support\Collection
    {
        $activeWeekdays = $this->getActiveWeekdays($year);
        $range = $year->date_start->toPeriod($year->date_end);

        $holidayDates = $this->getHolidayDateSet($year);
        $effectiveOverrideDates = $this->getEffectiveOverrideDateSet($year);

        $weeks = collect();

        foreach ($range as $date) {
            $weekKey = $date->format('o-W');
            $dayOfWeek = $date->dayOfWeekIso;

            $isActiveWeekday = in_array($dayOfWeek, $activeWeekdays);
            $isHoliday = $holidayDates->has($date->format('Y-m-d'));
            $isEffectiveOverride = $effectiveOverrideDates->has($date->format('Y-m-d'));

            $isEffective = false;

            if ($isEffectiveOverride) {
                $isEffective = true;
            } elseif ($isActiveWeekday && ! $isHoliday) {
                $isEffective = true;
            }

            if (! $weeks->has($weekKey)) {
                $weeks->put($weekKey, [
                    'week_key' => $weekKey,
                    'start_date' => $date->copy()->startOfWeek(),
                    'end_date' => $date->copy()->endOfWeek(),
                    'effective_days' => 0,
                    'non_effective_days' => 0,
                    'total_days' => 0,
                    'days' => collect(),
                ]);
            }

            $week = $weeks->get($weekKey);
            $week['total_days']++;

            if ($isEffective) {
                $week['effective_days']++;
            } else {
                $week['non_effective_days']++;
            }

            $week['days']->push([
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->isoFormat('dddd'),
                'is_effective' => $isEffective,
                'is_active_weekday' => $isActiveWeekday,
                'is_holiday' => $isHoliday,
                'is_effective_override' => $isEffectiveOverride,
            ]);

            $weeks->put($weekKey, $week);
        }

        return $weeks->values();
    }

    public function getMonthlyBreakdown(AcademicYear $year): \Illuminate\Support\Collection
    {
        $weeks = $this->getWeeklyBreakdown($year);

        return $weeks->groupBy(fn ($week) => \Carbon\Carbon::parse($week['start_date'])->format('Y-m'))
            ->map(function ($weeksInMonth, $ym) {
                $date = \Carbon\Carbon::createFromFormat('Y-m', $ym);

                return [
                    'month' => $ym,
                    'month_name' => $date->isoFormat('MMMM YYYY'),
                    'effective_days' => $weeksInMonth->sum('effective_days'),
                    'non_effective_days' => $weeksInMonth->sum('non_effective_days'),
                    'effective_weeks' => $weeksInMonth->filter(fn ($w) => $w['effective_days'] > 0)->count(),
                    'non_effective_weeks' => $weeksInMonth->filter(fn ($w) => $w['effective_days'] === 0)->count(),
                    'total_weeks' => $weeksInMonth->count(),
                    'weeks' => $weeksInMonth,
                ];
            })->values();
    }

    private function getEffectiveDayCollection(AcademicYear $year): \Illuminate\Support\Collection
    {
        $activeWeekdays = $this->getActiveWeekdays($year);
        $range = $year->date_start->toPeriod($year->date_end);

        $holidayDates = $this->getHolidayDateSet($year);
        $effectiveOverrideDates = $this->getEffectiveOverrideDateSet($year);

        $effectiveDays = collect();

        foreach ($range as $date) {
            $dayOfWeek = $date->dayOfWeekIso;
            $dateStr = $date->format('Y-m-d');

            $isActiveWeekday = in_array($dayOfWeek, $activeWeekdays);
            $isHoliday = $holidayDates->has($dateStr);
            $isEffectiveOverride = $effectiveOverrideDates->has($dateStr);

            if ($isEffectiveOverride) {
                $effectiveDays->push($dateStr);
            } elseif ($isActiveWeekday && ! $isHoliday) {
                $effectiveDays->push($dateStr);
            }
        }

        return $effectiveDays;
    }

    private function getHolidayDateSet(AcademicYear $year): \Illuminate\Support\Collection
    {
        $events = $year->academicEvents()
            ->whereIn('type', [
                AcademicEventType::LIBUR_NASIONAL,
                AcademicEventType::LIBUR_SEKOLAH,
                AcademicEventType::HARI_TIDAK_EFEKTIF,
            ])
            ->get();

        $dates = collect();

        foreach ($events as $event) {
            $period = $event->start_date->toPeriod($event->end_date ?? $event->start_date);
            foreach ($period as $date) {
                $dates->put($date->format('Y-m-d'), true);
            }
        }

        return $dates;
    }

    private function getEffectiveOverrideDateSet(AcademicYear $year): \Illuminate\Support\Collection
    {
        $events = $year->academicEvents()
            ->where('type', AcademicEventType::HARI_EFEKTIF)
            ->get();

        $dates = collect();

        foreach ($events as $event) {
            $period = $event->start_date->toPeriod($event->end_date ?? $event->start_date);
            foreach ($period as $date) {
                $dates->put($date->format('Y-m-d'), true);
            }
        }

        return $dates;
    }
}
