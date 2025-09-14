<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TranscriptStudent extends Model
{
    /** @use HasFactory<\Database\Factories\TranscriptStudentFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'transcript_id',
        'academic_year_id',
        'subject_id',
        'student_id',
        'score',
    ];

    public function transcript()
    {
        return $this->belongsTo(Transcript::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
