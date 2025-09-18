<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // RoleSeeder::class,
            // PermissionsSeeder::class,
            ShieldSeeder::class,
            UserSeeder::class,
            AcademicYearSeeder::class,
            StudentSeeder::class,
            GradeSeeder::class,
            SubjectSeeder::class,
            JournalSeeder::class,
            AttendanceSeeder::class,
            TranscriptSeeder::class,
        ]);
    }
}
