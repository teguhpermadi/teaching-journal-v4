<?php

namespace App\Models;

use App\Observers\TranscriptObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

#[ObservedBy(TranscriptObserver::class)]
class Transcript extends Model
{
    /** @use HasFactory<\Database\Factories\TranscriptFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'subject_id',
        'journal_id',
        'academic_year_id',
        'user_id',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeMyTranscripts($query)
    {
        return $query->where('user_id', Auth::id());
    }

    public function transcriptStudents()
    {
        return $this->hasMany(TranscriptStudent::class);
    }
}
