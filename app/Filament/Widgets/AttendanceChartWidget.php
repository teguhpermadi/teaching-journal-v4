<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceChartWidget extends ChartWidget
{
    protected static ?string $title = 'Statistik Ketidakhadiran';
    
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $filter = $this->filter;
        
        // Default to current month if no filter
        $date = $filter ? Carbon::parse($filter) : now();
        
        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();

        // Query distinct students per day
        // Using raw query to count distinct student_id efficiently
        $data = Attendance::query()
            ->select(DB::raw('DATE(date) as date_str'), DB::raw('count(distinct student_id) as count'))
            ->whereBetween('date', [$start, $end])
            ->groupBy('date_str')
            ->orderBy('date_str')
            ->get()
            ->pluck('count', 'date_str')
            ->toArray();

        // Fill missing days
        $labels = [];
        $values = [];
        
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $dateString = $day->format('Y-m-d');
            $labels[] = $day->format('d M');
            $values[] = $data[$dateString] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Siswa Tidak Masuk',
                    'data' => $values,
                    'borderColor' => '#f59e0b', // Amber/Warning color
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => 'start',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getFilters(): ?array
    {
        // Generate last 12 months for filter
        $filters = [];
        $date = now()->startOfMonth();
        
        for ($i = 0; $i < 12; $i++) {
            $key = $date->format('Y-m-d'); // Use full date as key to be safe for parsing
            $value = $date->translatedFormat('F Y');
            $filters[$key] = $value;
            $date->subMonth();
        }

        return $filters;
    }

    protected function getType(): string
    {
        return 'line';
    }
}
