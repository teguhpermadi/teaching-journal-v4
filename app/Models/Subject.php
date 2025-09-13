<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    /** @use HasFactory<\Database\Factories\SubjectFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'schedule',
        'user_id',
        'grade_id',
    ];

    protected $casts = [
        'schedule' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function scopeSubjectWithGradeActive($query)
    {
        $gradeActive = Grade::gradeAcademicYearActive()->get();
        return $query->whereHas('grade', function ($query) use ($gradeActive) {
            $query->whereIn('grades.id', $gradeActive->pluck('id'));
        });
    }
}
