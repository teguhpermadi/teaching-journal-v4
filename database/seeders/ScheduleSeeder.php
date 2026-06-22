<?php

namespace Database\Seeders;

use App\Models\Schedule;
use App\Models\Subject;
use App\ScheduleEnum;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $days = ScheduleEnum::cases();

        Subject::all()->each(function ($subject) use ($days) {
            $randomDays = collect($days)
                ->random(rand(1, 4))
                ->map(fn ($day) => $day->value)
                ->values()
                ->toArray();

            Schedule::create([
                'subject_id' => $subject->id,
                'days' => $randomDays,
            ]);
        });
    }
}
