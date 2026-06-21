<?php

namespace App\Imports;

use App\AcademicCalendarColorEnum;
use App\AcademicStatusCalendarEnum;
use App\Models\AcademicCalendar;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;

class AcademicCalendarImport implements SkipsEmptyRows, ToModel, WithHeadingRow, WithUpserts
{
    use Importable;

    protected ?string $academicYearId;

    public function __construct(
        ?string $model = null,
        array $attributes = [],
        array $additionalData = []
    ) {
        $this->academicYearId = AcademicYear::active()->first()?->id;
    }

    public function model(array $row)
    {
        $status = $this->resolveStatus($row['status'] ?? null);
        $color = $this->resolveColor($status);

        try {
            $startDate = $this->transformDate($row['start_date'] ?? $row['date'] ?? null);
            $endDate = $this->transformDate($row['end_date'] ?? $row['date'] ?? null);

            if (! $startDate || ! $endDate) {
                return null;
            }

            return AcademicCalendar::updateOrCreate(
                [
                    'title' => $row['title'],
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                [
                    'status' => $status,
                    'color' => $color,
                    'description' => $row['description'] ?? null,
                    'user_id' => Auth::id(),
                    'academic_year_id' => $this->academicYearId,
                ]
            );
        } catch (\Exception $e) {
            return null;
        }
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function uniqueBy()
    {
        return ['title', 'start_date', 'end_date'];
    }

    private function transformDate($value, $format = 'Y-m-d')
    {
        if (empty($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format($format);
            }

            return \Carbon\Carbon::parse($value)->format($format);
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function resolveStatus(?string $excelValue): AcademicStatusCalendarEnum
    {
        if (empty($excelValue)) {
            return AcademicStatusCalendarEnum::NOT_EFFECTIVE;
        }

        $normalized = strtolower(trim($excelValue));

        return match ($normalized) {
            'efektif', 'effective' => AcademicStatusCalendarEnum::EFFECTIVE,
            default => AcademicStatusCalendarEnum::NOT_EFFECTIVE,
        };
    }

    protected function resolveColor(AcademicStatusCalendarEnum $status): AcademicCalendarColorEnum
    {
        return $status === AcademicStatusCalendarEnum::EFFECTIVE
            ? AcademicCalendarColorEnum::SAGE
            : AcademicCalendarColorEnum::TOMATO;
    }
}
