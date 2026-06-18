<?php

namespace App\Providers;

use App\Models\AcademicCalendar;
use App\Models\Attendance;
use App\Observers\AcademicCalendarObserver;
use App\Observers\AttendanceObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Attendance::observe(AttendanceObserver::class);
        AcademicCalendar::observe(AcademicCalendarObserver::class);
    }
}
