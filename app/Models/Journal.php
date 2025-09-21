<?php

namespace App\Models;

use App\Models\Scopes\AcademicYearScope;
use Guava\Calendar\Contracts\Eventable;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ScopedBy(AcademicYearScope::class)]
class Journal extends Model implements HasMedia, Eventable
{
    /** @use HasFactory<\Database\Factories\JournalFactory> */
    use HasFactory, HasUlids, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'academic_year_id',
        'subject_id',
        'grade_id',
        'user_id',
        'date',
        'target_id',
        'chapter',
        'activity',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    protected static function booted(): void
    {
        // add default sort
        static::addGlobalScope('sort', function (Builder $builder) {
            $builder->orderBy('date', 'desc');
        });
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function transcripts()
    {
        return $this->hasMany(Transcript::class);
    }

    public function target()
    {
        return $this->belongsTo(Target::class);
    }

    public function scopeMyJournals($query)
    {
        return $query->where('user_id', Auth::id());
    }

    public function toCalendarEvent(): CalendarEvent
    {
        // For eloquent models, make sure to pass the model to the constructor
        return CalendarEvent::make($this)
            ->title($this->chapter)
            ->start($this->date)
            ->end($this->date)
            ->backgroundColor($this->subject->color)
            ->allDay(true);
    }
}
