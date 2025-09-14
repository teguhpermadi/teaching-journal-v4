<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transcript extends Model
{
    /** @use HasFactory<\Database\Factories\TranscriptFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'subject_id',
        'journal_id',
        'academic_year_id',
        'title',
        'description',
    ];

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
