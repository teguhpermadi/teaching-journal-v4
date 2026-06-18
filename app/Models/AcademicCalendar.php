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
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicCalendar extends Model implements Eventable
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'title',
        'date',
        'description',
        'status',
        'color',
        'google_calendar_event_id',
        'user_id',
        'academic_year_id',
    ];

    protected $casts = [
        'date' => 'date',
        'status' => AcademicStatusCalendarEnum::class,
        'color' => AcademicCalendarColorEnum::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function toCalendarEvent(): CalendarEvent
    {
        $color = $this->color?->value ?? ($this->status === AcademicStatusCalendarEnum::EFFECTIVE
            ? AcademicCalendarColorEnum::SAGE->value
            : AcademicCalendarColorEnum::TOMATO->value
        );

        return CalendarEvent::make($this)
            ->title($this->title)
            ->start($this->date)
            ->end($this->date)
            ->backgroundColor($color)
            ->textColor('#ffffff')
            ->allDay(true);
    }
}
