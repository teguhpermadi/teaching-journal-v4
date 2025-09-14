<?php

namespace Database\Factories;

use App\Models\Grade;
use App\Models\Journal;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transcript>
 */
class TranscriptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $journal = Journal::all()->random();
        $subject = $journal->subject;
        $academicYear = $journal->academic_year_id;
        $user = $journal->user_id;
        $grade = $subject->grade_id;
        
        return [
            'subject_id' => $subject->id,
            'journal_id' => $journal->id,
            'academic_year_id' => $academicYear,
            'user_id' => $user,
            'grade_id' => $grade,
            'title' => fake()->sentence(10),
            'description' => fake()->sentence(10),
        ];
    }
}
