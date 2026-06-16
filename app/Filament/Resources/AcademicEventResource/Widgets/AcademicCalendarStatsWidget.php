<?php

namespace App\Filament\Resources\AcademicEventResource\Widgets;

use App\Models\AcademicYear;
use App\Services\AcademicCalendarService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AcademicCalendarStatsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $year = AcademicYear::active()->first();

        if (! $year) {
            return [
                Stat::make('Tahun Ajaran', 'Tidak ada')
                    ->description('Aktifkan tahun ajaran terlebih dahulu')
                    ->color('warning'),
            ];
        }

        $service = app(AcademicCalendarService::class);

        $effectiveDays = $service->getEffectiveDays($year);
        $nonEffectiveDays = $service->getNonEffectiveDays($year);
        $effectiveWeeks = $service->getEffectiveWeeks($year);
        $nonEffectiveWeeks = $service->getNonEffectiveWeeks($year);

        return [
            Stat::make('Hari Efektif', $effectiveDays)
                ->description('Total hari belajar efektif')
                ->color('success')
                ->chart([7, 5, 8, 6, 7, 9, 6, 8, 7, 6]),

            Stat::make('Hari Tidak Efektif', $nonEffectiveDays)
                ->description('Libur & hari tidak efektif')
                ->color('danger'),

            Stat::make('Pekan Efektif', $effectiveWeeks)
                ->description('Pekan dengan kegiatan belajar')
                ->color('success'),

            Stat::make('Pekan Tidak Efektif', $nonEffectiveWeeks)
                ->description('Pekan tanpa kegiatan belajar')
                ->color('gray'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
}
