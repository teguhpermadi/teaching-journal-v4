<?php

use App\AcademicCalendarColorEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('academic_calendars')
            ->where('color', '#22c55e')
            ->update(['color' => AcademicCalendarColorEnum::SAGE->value]);

        DB::table('academic_calendars')
            ->where('color', '#ef4444')
            ->update(['color' => AcademicCalendarColorEnum::TOMATO->value]);
    }

    public function down(): void
    {
        DB::table('academic_calendars')
            ->where('color', AcademicCalendarColorEnum::SAGE->value)
            ->update(['color' => '#22c55e']);

        DB::table('academic_calendars')
            ->where('color', AcademicCalendarColorEnum::TOMATO->value)
            ->update(['color' => '#ef4444']);
    }
};
