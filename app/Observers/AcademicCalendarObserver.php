<?php

namespace App\Observers;

use App\AcademicCalendarColorEnum;
use App\AcademicStatusCalendarEnum;
use App\Models\AcademicCalendar;
use Carbon\Carbon;
use Spatie\GoogleCalendar\Event;

class AcademicCalendarObserver
{
    public function creating(AcademicCalendar $academicCalendar): void
    {
        if ($academicCalendar->color === null) {
            $academicCalendar->color = $academicCalendar->status === AcademicStatusCalendarEnum::EFFECTIVE
                ? AcademicCalendarColorEnum::SAGE
                : AcademicCalendarColorEnum::TOMATO;
        }
    }

    public function created(AcademicCalendar $academicCalendar): void
    {
        $this->syncToGoogleCalendar($academicCalendar);
    }

    public function updated(AcademicCalendar $academicCalendar): void
    {
        if ($academicCalendar->google_calendar_event_id) {
            $this->updateGoogleCalendarEvent($academicCalendar);
        } else {
            $this->syncToGoogleCalendar($academicCalendar);
        }
    }

    public function deleted(AcademicCalendar $academicCalendar): void
    {
        if ($academicCalendar->isForceDeleting()) {
            $this->deleteFromGoogleCalendar($academicCalendar);
        }
    }

    public function restored(AcademicCalendar $academicCalendar): void
    {
        $this->syncToGoogleCalendar($academicCalendar);
    }

    protected function syncToGoogleCalendar(AcademicCalendar $academicCalendar): void
    {
        try {
            $event = new Event;

            $event->name = $academicCalendar->title;
            $event->description = $academicCalendar->description ?? '';
            $event->startDate = Carbon::parse($academicCalendar->date);
            $event->endDate = Carbon::parse($academicCalendar->date)->addDay();

            if ($academicCalendar->color) {
                $event->setColorId($academicCalendar->color->getColorId());
            }

            $savedEvent = $event->save();

            $academicCalendar->updateQuietly([
                'google_calendar_event_id' => $savedEvent->id,
            ]);
        } catch (\Exception $e) {
            report($e);
        }
    }

    protected function updateGoogleCalendarEvent(AcademicCalendar $academicCalendar): void
    {
        try {
            $event = Event::find($academicCalendar->google_calendar_event_id);

            if (! $event) {
                $this->syncToGoogleCalendar($academicCalendar);

                return;
            }

            $event->name = $academicCalendar->title;
            $event->description = $academicCalendar->description ?? '';
            $event->startDate = Carbon::parse($academicCalendar->date);
            $event->endDate = Carbon::parse($academicCalendar->date)->addDay();

            if ($academicCalendar->color) {
                $event->setColorId($academicCalendar->color->getColorId());
            }

            $event->save();
        } catch (\Exception $e) {
            report($e);
        }
    }

    protected function deleteFromGoogleCalendar(AcademicCalendar $academicCalendar): void
    {
        if (! $academicCalendar->google_calendar_event_id) {
            return;
        }

        try {
            $event = Event::find($academicCalendar->google_calendar_event_id);

            if ($event) {
                $event->delete();
            }
        } catch (\Exception $e) {
            report($e);
        }
    }
}
