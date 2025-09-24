<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Target extends Model
{
    /** @use HasFactory<\Database\Factories\TargetFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'subject_id',
        'grade_id',
        'academic_year_id',
        'main_target_id',
        'target',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function mainTarget()
    {
        return $this->belongsTo(MainTarget::class);
    }

    public function scopeMyTargetsInSubject($query, $subject)
    {
        // check academic year is active
        $academicYear = AcademicYear::active()->first();
        return $query->where('user_id', Auth::id())
            ->where('academic_year_id', $academicYear->id)
            ->where('subject_id', $subject);
    }
}
