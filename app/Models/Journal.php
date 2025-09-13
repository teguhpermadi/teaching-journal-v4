<?php

namespace App\Models;

use App\Models\Scopes\AcademicYearScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ScopedBy(AcademicYearScope::class)]
class Journal extends Model
{
    /** @use HasFactory<\Database\Factories\JournalFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'academic_year_id',
        'subject_id',
        'user_id',
        'date',
        'target',
        'chapter',
        'activity',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
