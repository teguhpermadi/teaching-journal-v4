<?php

namespace App\Services;

use App\Models\AcademicCalendar;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AcademicCalendarSplitService
{
    public function split(AcademicCalendar $record): Collection
    {
        if ($record->start_date->equalTo($record->end_date)) {
            throw new \InvalidArgumentException('Cannot split a single-day event.');
        }

        $records = new Collection;
        $gradeIds = $record->grades()->pluck('grades.id')->toArray();

        DB::transaction(function () use ($record, &$records, $gradeIds) {
            $period = $record->start_date->toPeriod($record->end_date);

            foreach ($period as $date) {
                $new = AcademicCalendar::create([
                    'title' => $record->title,
                    'start_date' => $date->format('Y-m-d'),
                    'end_date' => $date->format('Y-m-d'),
                    'description' => $record->description,
                    'status' => $record->status->value,
                    'color' => $record->color?->value,
                    'user_id' => $record->user_id,
                    'academic_year_id' => $record->academic_year_id,
                ]);

                if (! empty($gradeIds)) {
                    $new->grades()->attach($gradeIds);
                }

                $records->push($new);
            }

            $record->grades()->detach();
            $record->delete();
        });

        return $records;
    }
}
