<?php

namespace App\Filament\Widgets;

use App\Models\AcademicYear;
use App\Services\AcademicWeekService;
use App\Settings\AcademicWeekSettings;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AcademicWeekStatsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $service = app(AcademicWeekService::class);
        $settings = app(AcademicWeekSettings::class);
        $activeYear = AcademicYear::active()->first();

        if (! $activeYear) {
            return [
                Stat::make('Tahun Akademik', 'Tidak ada')
                    ->description('Aktifkan tahun akademik terlebih dahulu')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('warning'),
            ];
        }

        $totalWeeks = $service->totalNonEffectiveWeeks($activeYear);
        $weeks = $service->getNonEffectiveWeeks($activeYear);
        $totalDays = $weeks->sum('non_effective_days');
        $avgDays = $totalWeeks > 0 ? round($totalDays / $totalWeeks, 1) : 0;

        return [
            Stat::make('Total Minggu Tidak Efektif', $totalWeeks)
                ->description('Sepanjang tahun akademik aktif')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($totalWeeks > 0 ? 'warning' : 'success'),

            Stat::make('Total Hari Tidak Efektif', $totalDays)
                ->description('Akumulasi hari tidak efektif')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($totalDays > 0 ? 'danger' : 'success'),

            Stat::make('Rata-rata Hari/Minggu', $avgDays)
                ->description('Hari tidak efektif per minggu')
                ->descriptionIcon('heroicon-m-chart-bar-square')
                ->color($avgDays > 0 ? 'warning' : 'success'),

            Stat::make('Ambang Batas Minimum', $settings->min_non_effective_days.' hari')
                ->description('Konfigurasi minggu tidak efektif')
                ->descriptionIcon('heroicon-m-cog-6-tooth')
                ->color('info'),
        ];
    }
}
