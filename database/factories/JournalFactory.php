<?php

namespace Database\Factories;

use App\Models\Journal;
use App\Models\Subject;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

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
            'activity' => fake()->paragraph(5),
            'notes' => fake()->paragraph(1),
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Journal $journal) {
            // Gunakan URL Picsum Photos untuk mendapatkan gambar
            $imageUrl = 'https://picsum.photos/640/480';
            $imageContent = file_get_contents($imageUrl);

            // Buat file temporer dan simpan gambar
            $tempFileName = $this->faker->uuid() . '.jpg';
            Storage::put('temp_images/' . $tempFileName, $imageContent);

            // Tambahkan gambar ke koleksi 'activity_photos' menggunakan Spatie Media Library
            $journal->addMediaFromDisk('temp_images/' . $tempFileName)
                    ->preservingOriginal()
                    ->toMediaCollection('activity_photos');

            // Hapus file gambar temporer setelah selesai
            Storage::delete('temp_images/' . $tempFileName);
        });
    }
}
