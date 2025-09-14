<?php

namespace Database\Seeders;

use App\Models\Transcript;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class TranscriptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i=0; $i < 100; $i++) { 
            try {
                Transcript::factory()->create();
            } catch (\Exception $e) {
                Log::info($e->getMessage());
            }
        }
    }
}
