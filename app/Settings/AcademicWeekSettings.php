<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class AcademicWeekSettings extends Settings
{
    public int $min_non_effective_days = 3;

    public static function group(): string
    {
        return 'academic_week';
    }
}
