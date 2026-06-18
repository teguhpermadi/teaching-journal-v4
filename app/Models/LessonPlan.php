<?php

namespace App\Models;

use App\TeachingStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class LessonPlan extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\LessonPlanFactory> */
    use HasFactory, HasUlids, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'academic_year_id',
        'grade_id',
        'subject_id',
        'user_id',
        'target_id',
        'topic',
        'learning_objectives',
        'activities',
        'materials',
        'assessment',
        'planned_date',
        'status',
    ];

    protected $casts = [
        'planned_date' => 'date',
        'status' => TeachingStatusEnum::class,
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Target::class);
    }
}
