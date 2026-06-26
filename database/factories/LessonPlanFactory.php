<?php

namespace Database\Factories;

use App\Models\Scopes\AcademicYearScope;
use App\Models\Subject;
use App\Models\Target;
use App\TeachingStatusEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonPlanFactory extends Factory
{
    public function definition(): array
    {
        $subject = Subject::withoutGlobalScope(AcademicYearScope::class)->inRandomOrder()->first();

        return [
            'academic_year_id' => $subject->academic_year_id,
            'grade_id' => $subject->grade_id,
            'subject_id' => $subject->id,
            'user_id' => $subject->user_id,
            'target_id' => Target::inRandomOrder()->first()?->id,
            'topic' => fake()->sentence(6),
            'learning_objectives' => fake()->paragraph(3),
            'activities' => fake()->paragraph(5),
            'materials' => fake()->paragraph(3),
            'assessment' => fake()->paragraph(2),
            'planned_date' => Carbon::now()->startOfMonth()->addDays(rand(0, (int) Carbon::now()->daysInMonth - 1)),
            'status' => fake()->randomElement(TeachingStatusEnum::cases()),
        ];
    }
}
