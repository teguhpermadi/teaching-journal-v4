<?php

namespace App\Models;

use App\GenderEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    /** @use HasFactory<\Database\Factories\StudentFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'gender',
        'nisn',
        'nis',
        'photo',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'gender' => GenderEnum::class,
    ];

    public function grades()
    {
        return $this->belongsToMany(Grade::class, 'grade_student');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function scopeStudentWithoutGradeActive()
    {
        $gradeActive = Grade::gradeAcademicYearActive()->get();
        return $this->whereDoesntHave('grades', function ($query) use ($gradeActive) {
            $query->whereIn('grades.id', $gradeActive->pluck('id'));
        });
    }
}
