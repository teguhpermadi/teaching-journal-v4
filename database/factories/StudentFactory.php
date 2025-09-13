<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'gender' => fake()->randomElement(['male', 'female']),
            'nisn' => fake()->unique()->numberBetween(100000000000, 999999999999),
            'nis' => fake()->unique()->numberBetween(100000000000, 999999999999),
            'photo' => fake()->image(storage_path('app/public/photos'), 640, 480, 'people', false),
            'active' => fake()->boolean(),
        ];
    }
}
