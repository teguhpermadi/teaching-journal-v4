<?php

namespace Database\Factories;

use App\Models\Journal;
use App\Models\Student;
use App\StatusAttendanceEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
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
        $grade = $subject->grade;
        $students = $grade->students;

        return [
            'student_id' => $students->random()->id,
            'journal_id' => $journal->id,
            'date' => $journal->date,
            'status' => fake()->randomElement(StatusAttendanceEnum::cases()),
        ];
    }
}
