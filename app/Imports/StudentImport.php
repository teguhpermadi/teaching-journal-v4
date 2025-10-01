<?php

namespace App\Imports;

use App\GenderEnum;
use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;

class StudentImport implements ToModel, WithHeadingRow, WithUpserts, SkipsEmptyRows
{
    use Importable;

    public function model(array $row)
    {
        // Konversi tanggal lahir
        $birthdayValue = $row['birthday'] ?? null;
        $genderValue = $row['gender'] ?? null;

        $gender = $this->resolveGender($genderValue);

        // Konversi tanggal lahir (gunakan $birthdayValue)
        $birthday = $this->transformDate($birthdayValue);

        try {
            return Student::updateOrCreate(
                [
                    // Gunakan kombinasi unik untuk pengecekan data
                    'nisn' => $row['nisn'],
                    'nis' => $row['nis'] ?? null,
                ],
                [
                    'name'      => $row['name'],
                    'nick_name' => $row['nick_name'] ?? null,
                    'city_born' => $row['city_born'] ?? null,
                    'birthday'  => $birthday,
                    'gender'    => $gender,
                ]
            );
        } catch (\Exception $e) {
            // Log error atau kembalikan null
            return null;
        }
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function uniqueBy()
    {
        return 'nisn';
    }

    /**
     * Konversi tanggal dari format Excel/string menjadi format Database (Y-m-d).
     * @param mixed $value
     * @return \Carbon\Carbon|null
     */
    private function transformDate($value, $format = 'Y-m-d')
    {
        if (empty($value)) {
            return null;
        }

        try {
            // Cek jika nilainya adalah angka (format tanggal Excel)
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format($format);
            }
            // Coba parsing sebagai string
            return \Carbon\Carbon::parse($value)->format($format);
        } catch (\Exception $e) {
            // Log error atau kembalikan null
            return null;
        }
    }

    /**
     * Mengkonversi nilai gender dari Excel menjadi nilai backing Enum.
     * @param string|null $excelValue
     * @return string|null
     */
    protected function resolveGender(?string $excelValue): ?string
    {
        if (empty($excelValue)) {
            // Kembalikan null atau nilai default jika kosong, tergantung aturan DB
            return null;
        }

        // Lakukan normalisasi (lowercase dan trim) agar lebih fleksibel
        $normalizedValue = strtolower(trim($excelValue));

        return match ($normalizedValue) {
            // Case 1: Nilai dari Excel sama dengan nilai backing (nilai DB)
            'laki-laki', 'l', 'male'    => GenderEnum::Male->value,
            'perempuan', 'p', 'female'  => GenderEnum::Female->value,

            // Case 2: Nilai dari Excel sama dengan nilai label (yang tampil di Filament)
            'laki-laki'                 => GenderEnum::Male->value,
            'perempuan'                 => GenderEnum::Female->value,

            // Default: Jika tidak cocok, kembalikan nilai asal atau null
            default                     => null, // Atau throw exception untuk menghentikan impor
        };
    }
}
