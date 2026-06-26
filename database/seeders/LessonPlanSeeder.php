<?php

namespace Database\Seeders;

use App\Models\LessonPlan;
use App\Models\Scopes\AcademicYearScope;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class LessonPlanSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = Subject::withoutGlobalScope(AcademicYearScope::class)->get();

        foreach ($subjects as $subject) {
            LessonPlan::factory()->count(20)->create([
                'academic_year_id' => $subject->academic_year_id,
                'grade_id' => $subject->grade_id,
                'subject_id' => $subject->id,
                'user_id' => $subject->user_id,
            ]);
        }
    }
}
