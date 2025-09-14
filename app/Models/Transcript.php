<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Transcript extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\TranscriptFactory> */
    use HasFactory, HasUlids, InteractsWithMedia;

    protected $fillable = [
        'student_id',
        'subject_id',
        'journal_id',
        'academic_year_id',
        'score',
        'notes',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
