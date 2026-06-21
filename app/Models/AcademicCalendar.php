<?php

namespace App\Models;

use App\AcademicCalendarColorEnum;
use App\AcademicStatusCalendarEnum;
use Guava\Calendar\Contracts\Eventable;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class AcademicCalendar extends Model implements Eventable
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'title',
        'start_date',
        'end_date',
        'description',
        'status',
        'color',
        'google_calendar_event_id',
        'user_id',
        'academic_year_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => AcademicStatusCalendarEnum::class,
        'color' => AcademicCalendarColorEnum::class,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $record) {
            if ($record->end_date && $record->start_date && $record->end_date < $record->start_date) {
                $validator = validator([], []);
                $validator->after(fn ($v) => $v->errors()->add(
                    'end_date',
                    'Tanggal selesai harus setelah tanggal mulai.',
                ));
                throw new ValidationException($validator);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function grades(): BelongsToMany
    {
        return $this->belongsToMany(Grade::class);
    }

    public function toCalendarEvent(): CalendarEvent
    {
        $color = $this->color?->value ?? ($this->status === AcademicStatusCalendarEnum::EFFECTIVE
            ? AcademicCalendarColorEnum::SAGE->value
            : AcademicCalendarColorEnum::TOMATO->value
        );

        return CalendarEvent::make($this)
            ->title($this->title)
            ->start($this->start_date)
            ->end($this->end_date)
            ->backgroundColor($color)
            ->textColor('#ffffff')
            ->allDay(true);
    }
}
