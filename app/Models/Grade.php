<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\AcademicYear;
use App\Models\Student;

class Grade extends Model
{
    /** @use HasFactory<\Database\Factories\GradeFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'level',
        'academic_year_id',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'grade_student');
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    public function scopeGradeAcademicYearActive($query)
    {
        return $query->whereHas('academicYear', function ($query) {
            $query->where('active', true);
        });
    }

    public function studentWithoutAttendance($date, $journal_id)
    {
        return $this->students()->whereDoesntHave('attendances', function ($query) use ($date, $journal_id) {
            $query->where('date', $date)
                ->where('journal_id', $journal_id);
        });
    }
}
