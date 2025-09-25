<?php

namespace App\Imports;

use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StudentImport implements ToCollection, WithHeadingRow, SkipsEmptyRows, WithValidation
{
    public function collection(Collection $rows)
    {
        // dd($rows);
        foreach ($rows as $row) {
            Student::create($row->toArray());
        }
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function rules(): array
    {
        return [
            'nis' => 'required|unique:students',
            'nisn' => 'required|unique:students',
            'name' => 'required',
            'gender' => 'required',
            'birth_date' => 'required',
            'birth_place' => 'required',
        ];
    }
}
