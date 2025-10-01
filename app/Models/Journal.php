<?php

namespace App\Models;

use App\Models\Scopes\AcademicYearScope;
use App\TeachingStatusEnum;
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
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

#[ScopedBy(AcademicYearScope::class)]
class Journal extends Model implements HasMedia, Eventable
{
    /** @use HasFactory<\Database\Factories\JournalFactory> */
    use HasFactory, HasUlids, SoftDeletes, InteractsWithMedia, HasJsonRelationships;

    protected $fillable = [
        'academic_year_id',
        'subject_id',
        'grade_id',
        'user_id',
        'date',
        'main_target_id',
        'target_id',
        'chapter',
        'activity',
        'notes',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'main_target_id' => 'array',
        'target_id' => 'array',
        'status' => TeachingStatusEnum::class,
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

    /**
     * Get the targets associated with the journal.
     * This is an accessor because target_id stores an array of IDs.
     */
    public function getTargetsAttribute()
    {
        return Target::whereIn('id', $this->target_id ?? [])->get();
    }

    public function mainTargets()
    {
        return $this->belongsToJson(MainTarget::class, 'main_target_id');
    }

    public function targets()
    {
        return $this->belongsToJson(Target::class, 'target_id');
    }

    /**
     * Get the main targets associated with the journal.
     * This is an accessor because main_target_id stores an array of IDs.
     */
    public function getMainTargetAttribute()
    {
        // The 'main_target_id' from the database is automatically cast to an array.
        // We use that array to fetch all the corresponding MainTarget models.
        return MainTarget::whereIn('id', $this->main_target_id ?? [])->get();
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

    public function registerMediaConversions(?Media $media = null): void
    {
        // Konversi ini akan dijalankan SETELAH file disimpan (jika > 1MB)
        $this->addMediaConversion('activity_photos_compressed')
            // ->nonQueued() // Untuk debugging, bisa dihapus di production
            ->performOnCollections('activity_photos') // Sesuaikan dengan nama koleksi di Filament
            ->width(1200) // Opsional: Batasi lebar maksimum
            ->quality(50) // Set kualitas (untuk JPG/WebP)
            ->optimize();
    }
}
