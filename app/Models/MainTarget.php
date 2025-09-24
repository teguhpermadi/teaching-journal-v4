<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MainTarget extends Model
{
    /** @use HasFactory<\Database\Factories\MainTargetFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'academic_year_id',
        'user_id',
        'subject_id',
        'grade_id',
        'main_target',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

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

    public function targets()
    {
        return $this->hasMany(Target::class);
    }
}
