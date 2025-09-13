<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Models\Scopes\AcademicYearScope;

#[ScopedBy(AcademicYearScope::class)]
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
        'academic_year_id',
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

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function scopeSubjectWithGradeActive($query)
    {
        $gradeActive = Grade::gradeAcademicYearActive()->get();
        return $query->whereHas('grade', function ($query) use ($gradeActive) {
            $query->whereIn('grades.id', $gradeActive->pluck('id'));
        });
    }

    public function scopeMySubjects($query)
    {
        return $query->where('user_id', Auth::id());
    }
}
