<?php

use App\AcademicCalendarColorEnum;
use App\Models\AcademicCalendar;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create([
        'ulid' => (string) Str::ulid(),
        'name' => 'teacher',
        'guard_name' => 'web',
    ]);

    AcademicCalendar::withoutEvents(function () {
        User::factory()->create();
        AcademicYear::factory()->create();
    });
});

afterEach(function () {
    Mockery::close();
});

test('created event syncs to google calendar and saves event id', function () {
    $mockEvent = Mockery::mock('overload:Spatie\GoogleCalendar\Event');
    $mockEvent->shouldReceive('setColorId')->once();
    $mockEvent->shouldReceive('save')
        ->once()
        ->andReturn((object) ['id' => 'google-event-created-123']);

    $academicCalendar = AcademicCalendar::factory()->create();

    expect($academicCalendar->google_calendar_event_id)->toBe('google-event-created-123');
});

test('updated event updates existing google calendar event in-place', function () {
    // Create model without triggering observer
    $academicCalendar = AcademicCalendar::withoutEvents(fn () => AcademicCalendar::factory()->create());
    $academicCalendar->updateQuietly(['google_calendar_event_id' => 'existing-google-id']);

    $mockEvent = Mockery::mock('overload:Spatie\GoogleCalendar\Event');

    $mockEvent->shouldReceive('find')
        ->once()
        ->with('existing-google-id')
        ->andReturn($mockEvent);

    $mockEvent->shouldReceive('setColorId')->once();

    $mockEvent->shouldReceive('save')
        ->once()
        ->andReturn((object) ['id' => 'existing-google-id']);

    $academicCalendar->update(['title' => 'Updated Title']);
    $academicCalendar->refresh();

    expect($academicCalendar->google_calendar_event_id)->toBe('existing-google-id')
        ->and($academicCalendar->title)->toBe('Updated Title');
});

test('soft deleted event does not remove from google calendar', function () {
    $academicCalendar = AcademicCalendar::withoutEvents(fn () => AcademicCalendar::factory()->create());

    // soft delete should NOT trigger Google Calendar deletion
    $academicCalendar->delete();

    expect($academicCalendar->trashed())->toBeTrue();
});

test('force deleted event removes from google calendar', function () {
    $academicCalendar = AcademicCalendar::withoutEvents(fn () => AcademicCalendar::factory()->create());
    $academicCalendar->updateQuietly(['google_calendar_event_id' => 'google-event-force-delete']);

    $mockEvent = Mockery::mock('overload:Spatie\GoogleCalendar\Event');

    $mockEvent->shouldReceive('find')
        ->once()
        ->with('google-event-force-delete')
        ->andReturn($mockEvent);

    $mockEvent->shouldReceive('delete')->once();

    $academicCalendar->forceDelete();
});

test('restored event re-syncs to google calendar', function () {
    // Create model without triggering observer
    $academicCalendar = AcademicCalendar::withoutEvents(fn () => AcademicCalendar::factory()->create());
    $academicCalendar->updateQuietly([
        'google_calendar_event_id' => 'existing-google-id',
        'color' => AcademicCalendarColorEnum::SAGE,
    ]);

    $mockEvent = Mockery::mock('overload:Spatie\GoogleCalendar\Event');

    // soft delete does NOT trigger Google Calendar deletion
    $academicCalendar->delete();

    // restore triggers a new save to Google Calendar
    $mockEvent->shouldReceive('setColorId')->once();

    $mockEvent->shouldReceive('save')
        ->once()
        ->andReturn((object) ['id' => 'restored-google-id']);

    $academicCalendar->restore();
    $academicCalendar->refresh();

    expect($academicCalendar->google_calendar_event_id)->toBe('restored-google-id');
});
