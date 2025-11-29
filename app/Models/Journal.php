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
use App\Helpers\ColorHelper;
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

    /**
     * Get all signatures for the journal.
     */
    public function signatures()
    {
        return $this->hasMany(Signature::class);
    }

    /**
     * Get a specific signer's signature.
     */
    public function signature($role)
    {
        return $this->hasOne(Signature::class)->where('signer_role', $role)->latest();
    }

    /**
     * Check if the journal is signed by a specific role.
     */
    public function isSignedBy($role): bool
    {
        return $this->signatures()->where('signer_role', $role)->exists();
    }

    /**
     * Check if a user can sign this journal.
     * User can sign if:
     * 1. They are the owner of the journal (user_id), OR
     * 2. They have the 'headmaster' role
     */
    public function canSign(?User $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return false;
        }

        // Check if user is the owner
        if ($this->user_id === $user->id) {
            return true;
        }

        // Check if user has headmaster role
        return $user->hasRole('headmaster');
    }

    /**
     * Sign the journal as the owner (teacher).
     * 
     * @param string $signatureData Base64 encoded signature image or file path
     * @param User|null $user The user signing (defaults to authenticated user)
     * @return Signature|null
     */
    public function signAsOwner(string $signatureData, ?User $user = null): ?Signature
    {
        $user = $user ?? Auth::user();

        if (!$user || $this->user_id !== $user->id) {
            throw new \Exception('Only the journal owner can sign as owner.');
        }

        // Check if already signed by owner
        $existingSignature = $this->signatures()
            ->where('signer_role', 'owner')
            ->where('signer_id', $user->id)
            ->first();

        if ($existingSignature) {
            // Update existing signature
            $existingSignature->saveSignature($signatureData);
            return $existingSignature;
        }

        // Create new signature
        $signature = $this->signatures()->create([
            'signer_id' => $user->id,
            'signer_role' => 'owner',
        ]);

        $signature->saveSignature($signatureData);
        return $signature;
    }

    /**
     * Sign the journal as headmaster.
     * 
     * @param string $signatureData Base64 encoded signature image or file path
     * @param User|null $user The user signing (defaults to authenticated user)
     * @return Signature|null
     */
    public function signAsHeadmaster(string $signatureData, ?User $user = null): ?Signature
    {
        $user = $user ?? Auth::user();

        if (!$user || !$user->hasRole('headmaster')) {
            throw new \Exception('Only users with headmaster role can sign as headmaster.');
        }

        // Check if already signed by headmaster
        $existingSignature = $this->signatures()
            ->where('signer_role', 'headmaster')
            ->where('signer_id', $user->id)
            ->first();

        if ($existingSignature) {
            // Update existing signature
            $existingSignature->saveSignature($signatureData);
            return $existingSignature;
        }

        // Create new signature
        $signature = $this->signatures()->create([
            'signer_id' => $user->id,
            'signer_role' => 'headmaster',
        ]);

        $signature->saveSignature($signatureData);
        return $signature;
    }

    /**
     * Check if the journal is fully signed by both owner and headmaster.
     */
    public function isFullySigned(): bool
    {
        return $this->isSignedBy('owner') && $this->isSignedBy('headmaster');
    }

    /**
     * Get the owner's signature.
     */
    public function getOwnerSignature()
    {
        return $this->signatures()->where('signer_role', 'owner')->first();
    }

    /**
     * Get the headmaster's signature.
     */
    public function getHeadmasterSignature()
    {
        return $this->signatures()->where('signer_role', 'headmaster')->first();
    }

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
        $color = ColorHelper::normalizeColor($this->subject->color);

        // For eloquent models, make sure to pass the model to the constructor
        return CalendarEvent::make($this)
            ->title($this->chapter)
            ->start($this->date)
            ->end($this->date)
            ->backgroundColor($color)
            ->textColor(ColorHelper::getContrastColor($color))
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
