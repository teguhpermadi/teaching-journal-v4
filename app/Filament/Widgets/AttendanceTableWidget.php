<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;

class AttendanceTableWidget extends BaseWidget
{
    protected static ?string $title = 'Tabel Ketidakhadiran';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Student::query()
            )
            ->columns([
                Tables\Columns\TextColumn::make('grades.name')
                    ->label('Kelas')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Siswa')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('attendances_count')
                    ->counts('attendances')
                    ->label('Jumlah Ketidakhadiran')
                    ->sortable(),
            ])
            ->defaultSort('attendances_count', 'desc')
            // ->filters([
            //     Filter::make('month')
            //         ->schema([
            //             Select::make('value')
            //                 ->label('Bulan')
            //                 ->options(function () {
            //                     $options = [];
            //                     $date = now()->startOfMonth();
            //                     for ($i = 0; $i < 12; $i++) {
            //                         $options[$date->format('Y-m-d')] = $date->translatedFormat('F Y');
            //                         $date->subMonth();
            //                     }
            //                     return $options;
            //                 })
            //                 ->default(now()->startOfMonth()->format('Y-m-d')),
            //         ])
            //         ->query(function (Builder $query, array $data) {
            //             $dateStr = $data['value'] ?? now()->startOfMonth()->format('Y-m-d');
            //             $date = Carbon::parse($dateStr);
            //             $start = $date->copy()->startOfMonth();
            //             $end = $date->copy()->endOfMonth();

            //             $query->withCount(['attendances' => function (Builder $query) use ($start, $end) {
            //                 $query->whereBetween('date', [$start, $end]);
            //             }])
            //                 ->having('attendances_count', '>', 0);
            //         })
            //         ->indicateUsing(function (array $data): ?string {
            //             if (! $data['value']) {
            //                 return null;
            //             }
            //             return 'Bulan: ' . Carbon::parse($data['value'])->translatedFormat('F Y');
            //         }),
            // ])
            ->paginated([5, 10, 25]);
    }
}
