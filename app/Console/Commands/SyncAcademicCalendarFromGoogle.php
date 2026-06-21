<?php

namespace App\Console\Commands;

use App\AcademicCalendarColorEnum;
use App\Models\AcademicCalendar;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Spatie\GoogleCalendar\Event;

class SyncAcademicCalendarFromGoogle extends Command
{
    public int $syncedCount = 0;

    public int $deletedCount = 0;

    public int $totalEvents = 0;

    protected $signature = 'academic-calendar:sync-from-google';

    protected $description = 'Sync changes made in Google Calendar back to local AcademicCalendar records';

    public function handle(): int
    {
        $start = Carbon::now()->subMonth();
        $end = Carbon::now()->addMonths(6);

        $this->components->task('Fetching Google Calendar events', function () use ($start, $end) {
            $googleEvents = Event::get($start, $end);

            $foundGoogleIds = collect();

            $syncedCount = 0;

            foreach ($googleEvents as $googleEvent) {
                $foundGoogleIds->push($googleEvent->id);

                $local = AcademicCalendar::where('google_calendar_event_id', $googleEvent->id)->first();

                if (! $local) {
                    continue;
                }

                $googleTitle = $googleEvent->name;
                $googleStartDate = $googleEvent->startDate;
                $googleEndDate = $googleEvent->endDate?->subDay();
                $googleDescription = $googleEvent->description ?? '';
                $googleColor = AcademicCalendarColorEnum::fromColorId($googleEvent->colorId);

                $changed = false;
                $updateData = [];

                if ($local->title !== $googleTitle) {
                    $updateData['title'] = $googleTitle;
                    $changed = true;
                }

                if ($local->start_date->format('Y-m-d') !== $googleStartDate->format('Y-m-d')) {
                    $updateData['start_date'] = $googleStartDate;
                    $changed = true;
                }

                if ($googleEndDate && (! $local->end_date || $local->end_date->format('Y-m-d') !== $googleEndDate->format('Y-m-d'))) {
                    $updateData['end_date'] = $googleEndDate;
                    $changed = true;
                }

                if (($local->description ?? '') !== $googleDescription) {
                    $updateData['description'] = $googleDescription ?: null;
                    $changed = true;
                }

                if ($googleColor && $local->color !== $googleColor) {
                    $updateData['color'] = $googleColor;
                    $changed = true;
                }

                if ($changed) {
                    $local->updateQuietly($updateData);
                    $syncedCount++;
                }
            }

            $deletedCount = $this->handleOrphanedRecords($foundGoogleIds);

            $this->syncedCount = $syncedCount;
            $this->deletedCount = $deletedCount;
            $this->totalEvents = count($googleEvents);

            return true;
        });

        $this->line(json_encode([
            'updated' => $this->syncedCount,
            'deleted' => $this->deletedCount,
            'total_events' => $this->totalEvents,
        ]));

        return Command::SUCCESS;
    }

    protected function handleOrphanedRecords($foundGoogleIds): int
    {
        $orphaned = AcademicCalendar::query()
            ->whereNotNull('google_calendar_event_id')
            ->whereNotIn('google_calendar_event_id', $foundGoogleIds)
            ->get();

        $count = 0;

        foreach ($orphaned as $orphan) {
            $orphan->deleteQuietly();
            $count++;
        }

        return $count;
    }
}
