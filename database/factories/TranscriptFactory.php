<?php

namespace Database\Factories;

use App\Models\Grade;
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
        $grade = Grade::all()->random();
        $student = $grade->students()->all();
        $academicYear = $grade->academic_year_id;
        $subject = $grade->subjects()->all();
        $journal = $subject->journals()->all();
        
        return [
            'student_id' => $student->first()->id,
            'academic_year_id' => $academicYear,
            'subject_id' => $subject->first()->id,
            'journal_id' => $journal->first()->id,
            'score' => fake()->numberBetween(50, 100),
            'notes' => fake()->sentence(10),
        ];
    }
}
