<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Grade;
use App\ScheduleEnum;

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
        return [
            'name' => fake()->word(),
            'code' => fake()->bothify('SUB-####'),
            'schedule' => fake()->randomElements(ScheduleEnum::cases(), 2),
            'user_id' => User::factory(),
            'grade_id' => Grade::gradeAcademicYearActive()->get()->random()->id,
        ];
    }
}
