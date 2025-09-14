<?php

namespace Database\Factories;

use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Journal>
 */
class JournalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subject = Subject::all()->random();
        
        return [
            'academic_year_id' => $subject->academic_year_id,
            'subject_id' => $subject->id,
            'grade_id' => $subject->grade_id,
            'user_id' => $subject->user_id,
            'date' => Carbon::now()->subDays(rand(0, 6)),
            'target' => fake()->sentence(10),
            'chapter' => fake()->sentence(10),
            'activity' => fake()->sentence(10),
            'notes' => fake()->sentence(10),
        ];
    }
}
