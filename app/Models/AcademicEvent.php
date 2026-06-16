<?php

namespace App\Models;

use App\AcademicEventType;
use App\Models\Scopes\AcademicYearScope;
use Guava\Calendar\Contracts\Eventable;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ScopedBy(AcademicYearScope::class)]
class AcademicEvent extends Model implements Eventable
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'academic_year_id',
        'title',
        'description',
        'type',
        'start_date',
        'end_date',
        'color',
        'google_calendar_event_id',
        'last_synced_at',
        'user_id',
    ];

    protected $casts = [
        'type' => AcademicEventType::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'last_synced_at' => 'datetime',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getEventColor(): string
    {
        return $this->color ?? $this->type->getHexColor();
    }

    public function toCalendarEvent(): CalendarEvent
    {
        $color = $this->getEventColor();
        $endDate = $this->end_date ?? $this->start_date;

        return CalendarEvent::make($this)
            ->title('['.$this->type->getLabel().'] '.$this->title)
            ->start($this->start_date)
            ->end($endDate)
            ->backgroundColor($color)
            ->textColor('#ffffff')
            ->allDay(true);
    }
}
