<?php

use App\AcademicCalendarColorEnum;
use App\Models\AcademicCalendar;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
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

function mockGoogleEvent(string $id, string $title, string $date, ?string $description = null, ?int $colorId = null): object
{
    return new class($id, $title, $date, $description, $colorId)
    {
        public string $id;

        public string $name;

        public Carbon $startDate;

        public ?string $description;

        public ?int $colorId;

        public function __construct(string $id, string $title, string $date, ?string $description, ?int $colorId)
        {
            $this->id = $id;
            $this->name = $title;
            $this->startDate = Carbon::parse($date);
            $this->description = $description;
            $this->colorId = $colorId;
        }
    };
}

test('sync command updates local record when title changes in google', function () {
    $academicCalendar = AcademicCalendar::withoutEvents(fn () => AcademicCalendar::factory()->create([
        'google_calendar_event_id' => 'google-id-1',
        'title' => 'Old Title',
    ]));

    $mock = Mockery::mock('overload:Spatie\GoogleCalendar\Event');
    $mock->shouldReceive('get')
        ->once()
        ->andReturn(collect([
            mockGoogleEvent('google-id-1', 'Updated Title', '2026-08-17', ''),
        ]));

    Artisan::call('academic-calendar:sync-from-google');

    $academicCalendar->refresh();

    expect($academicCalendar->title)->toBe('Updated Title');
});

test('sync command updates local record when date changes in google', function () {
    $academicCalendar = AcademicCalendar::withoutEvents(fn () => AcademicCalendar::factory()->create([
        'google_calendar_event_id' => 'google-id-2',
        'date' => '2026-01-01',
    ]));

    $mock = Mockery::mock('overload:Spatie\GoogleCalendar\Event');
    $mock->shouldReceive('get')
        ->once()
        ->andReturn(collect([
            mockGoogleEvent('google-id-2', $academicCalendar->title, '2026-08-17', ''),
        ]));

    Artisan::call('academic-calendar:sync-from-google');

    $academicCalendar->refresh();

    expect($academicCalendar->date->format('Y-m-d'))->toBe('2026-08-17');
});

test('sync command updates local record when description changes in google', function () {
    $academicCalendar = AcademicCalendar::withoutEvents(fn () => AcademicCalendar::factory()->create([
        'google_calendar_event_id' => 'google-id-3',
        'description' => 'Old Description',
    ]));

    $mock = Mockery::mock('overload:Spatie\GoogleCalendar\Event');
    $mock->shouldReceive('get')
        ->once()
        ->andReturn(collect([
            mockGoogleEvent('google-id-3', $academicCalendar->title, $academicCalendar->date->format('Y-m-d'), 'New Description'),
        ]));

    Artisan::call('academic-calendar:sync-from-google');

    $academicCalendar->refresh();

    expect($academicCalendar->description)->toBe('New Description');
});

test('sync command updates local record when color changes in google', function () {
    $academicCalendar = AcademicCalendar::withoutEvents(fn () => AcademicCalendar::factory()->create([
        'google_calendar_event_id' => 'google-id-color',
        'title' => 'Color Test',
        'date' => '2026-08-17',
        'color' => AcademicCalendarColorEnum::BANANA,
    ]));

    $mock = Mockery::mock('overload:Spatie\GoogleCalendar\Event');
    $mock->shouldReceive('get')
        ->once()
        ->andReturn(collect([
            mockGoogleEvent('google-id-color', 'Color Test', '2026-08-17', '', 1), // Lavender
        ]));

    Artisan::call('academic-calendar:sync-from-google');

    $academicCalendar->refresh();

    expect($academicCalendar->color)->toBe(AcademicCalendarColorEnum::LAVENDER);
});

test('sync command soft-deletes orphaned local records', function () {
    AcademicCalendar::withoutEvents(fn () => AcademicCalendar::factory()->create([
        'google_calendar_event_id' => 'google-id-orphaned',
    ]));

    $mock = Mockery::mock('overload:Spatie\GoogleCalendar\Event');
    $mock->shouldReceive('get')
        ->once()
        ->andReturn(collect([]));

    Artisan::call('academic-calendar:sync-from-google');

    expect(AcademicCalendar::count())->toBe(0)
        ->and(AcademicCalendar::withTrashed()->count())->toBe(1);
});

test('sync command does not update records without google_calendar_event_id', function () {
    AcademicCalendar::withoutEvents(fn () => AcademicCalendar::factory()->create([
        'google_calendar_event_id' => null,
        'title' => 'Untouched',
    ]));

    $mock = Mockery::mock('overload:Spatie\GoogleCalendar\Event');
    $mock->shouldReceive('get')
        ->once()
        ->andReturn(collect([
            mockGoogleEvent('some-google-id', 'Should Not Sync', '2026-08-17', ''),
        ]));

    Artisan::call('academic-calendar:sync-from-google');

    expect(AcademicCalendar::first()->title)->toBe('Untouched');
});

test('sync command does not touch local records when google data is identical', function () {
    $academicCalendar = AcademicCalendar::withoutEvents(fn () => AcademicCalendar::factory()->create([
        'google_calendar_event_id' => 'google-id-same',
        'title' => 'Same Title',
        'date' => '2026-08-17',
        'description' => 'Same Description',
    ]));

    $originalUpdatedAt = $academicCalendar->updated_at->format('Y-m-d H:i:s');

    $mock = Mockery::mock('overload:Spatie\GoogleCalendar\Event');
    $mock->shouldReceive('get')
        ->once()
        ->andReturn(collect([
            mockGoogleEvent('google-id-same', 'Same Title', '2026-08-17', 'Same Description'),
        ]));

    Artisan::call('academic-calendar:sync-from-google');

    $academicCalendar->refresh();

    expect($academicCalendar->updated_at->format('Y-m-d H:i:s'))->toBe($originalUpdatedAt);
});

test('sync command skips google events with no matching local record', function () {
    $mock = Mockery::mock('overload:Spatie\GoogleCalendar\Event');
    $mock->shouldReceive('get')
        ->once()
        ->andReturn(collect([
            mockGoogleEvent('unknown-google-id', 'Orphan Event', '2026-08-17', ''),
        ]));

    Artisan::call('academic-calendar:sync-from-google');

    expect(AcademicCalendar::count())->toBe(0);
});

test('sync command does not trigger observer when updating', function () {
    $academicCalendar = AcademicCalendar::withoutEvents(fn () => AcademicCalendar::factory()->create([
        'google_calendar_event_id' => 'google-id-observer',
    ]));

    $mock = Mockery::mock('overload:Spatie\GoogleCalendar\Event');
    $mock->shouldReceive('get')
        ->once()
        ->andReturn(collect([
            mockGoogleEvent('google-id-observer', 'Changed Title', $academicCalendar->date->format('Y-m-d'), ''),
        ]));

    Artisan::call('academic-calendar:sync-from-google');

    $academicCalendar->refresh();

    expect($academicCalendar->title)->toBe('Changed Title')
        ->and($academicCalendar->google_calendar_event_id)->toBe('google-id-observer');
});
