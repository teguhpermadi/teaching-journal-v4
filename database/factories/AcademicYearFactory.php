<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'year' => $this->faker->year(),
            'semester' => $this->faker->randomElement(['Odd', 'Even']),
            'headmaster_name' => $this->faker->name(),
            'headmaster_nip' => $this->faker->numerify('################'),
            'date_start' => $this->faker->date(),
            'date_end' => $this->faker->date(),
            'active' => $this->faker->boolean(),
        ];
    }
}
