<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Grade;
use App\Models\AcademicYear;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subject>
 */
class SubjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $grade = Grade::gradeAcademicYearActive()->get()->random();
        return [
            'name' => fake()->word(),
            'code' => fake()->bothify('SUB-####'),
            'user_id' => User::factory()->afterCreating(function (User $user) {
                $user->assignRole('teacher');
            }),
            'grade_id' => $grade->id,
            'academic_year_id' => $grade->academic_year_id,
        ];
    }
}
