<?php

namespace App\Imports;

use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentImport implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        $collection->each(function ($row) {
            Student::updateOrCreate([
                'nisn' => $row['nisn'],
            ], [
                'name' => $row['name'],
                'gender' => $row['gender'],
                'nis' => $row['nis'],
            ]);
        });
    }
}
