<?php

use App\AcademicCalendarColorEnum;
use App\AcademicStatusCalendarEnum;
use App\Models\AcademicCalendar;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\User;
use Guava\Calendar\ValueObjects\CalendarEvent;
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

test('can create academic calendar with fillable attributes', function () {
    $academicCalendar = AcademicCalendar::factory()->create();

    expect($academicCalendar)->toBeInstanceOf(AcademicCalendar::class)
        ->and($academicCalendar->title)->not->toBeEmpty()
        ->and($academicCalendar->date)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($academicCalendar->description)->not->toBeEmpty()
        ->and($academicCalendar->status)->toBeInstanceOf(AcademicStatusCalendarEnum::class);
});

test('fillable attributes are mass assignable', function () {
    $user = User::factory()->create();
    $year = AcademicYear::factory()->create();

    $data = [
        'title' => 'Libur Nasional',
        'date' => '2026-08-17',
        'description' => 'Hari Kemerdekaan RI',
        'status' => AcademicStatusCalendarEnum::NOT_EFFECTIVE,
        'color' => AcademicCalendarColorEnum::BANANA,
        'user_id' => $user->id,
        'academic_year_id' => $year->id,
    ];

    $academicCalendar = AcademicCalendar::create($data);

    expect($academicCalendar->title)->toBe('Libur Nasional')
        ->and($academicCalendar->date->format('Y-m-d'))->toBe('2026-08-17')
        ->and($academicCalendar->description)->toBe('Hari Kemerdekaan RI')
        ->and($academicCalendar->status)->toBe(AcademicStatusCalendarEnum::NOT_EFFECTIVE)
        ->and($academicCalendar->color)->toBe(AcademicCalendarColorEnum::BANANA)
        ->and($academicCalendar->user_id)->toBe($user->id)
        ->and($academicCalendar->academic_year_id)->toBe($year->id);
});

test('cast date attribute to Carbon', function () {
    $academicCalendar = AcademicCalendar::factory()->create(['date' => '2026-06-18']);

    expect($academicCalendar->date)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($academicCalendar->date->format('Y-m-d'))->toBe('2026-06-18');
});

test('cast color attribute to enum', function () {
    $academicCalendar = AcademicCalendar::factory()->create();

    expect($academicCalendar->color)->toBeInstanceOf(AcademicCalendarColorEnum::class);
});

test('cast status attribute to enum', function () {
    $effective = AcademicCalendar::factory()->effective()->create();
    $notEffective = AcademicCalendar::factory()->notEffective()->create();

    expect($effective->status)->toBe(AcademicStatusCalendarEnum::EFFECTIVE)
        ->and($notEffective->status)->toBe(AcademicStatusCalendarEnum::NOT_EFFECTIVE);
});

test('belongs to user', function () {
    $user = User::factory()->create();
    $academicCalendar = AcademicCalendar::factory()->create(['user_id' => $user->id]);

    expect($academicCalendar->user)->toBeInstanceOf(User::class)
        ->and($academicCalendar->user->id)->toBe($user->id);
});

test('belongs to academic year', function () {
    $year = AcademicYear::factory()->create();
    $academicCalendar = AcademicCalendar::factory()->create(['academic_year_id' => $year->id]);

    expect($academicCalendar->academicYear)->toBeInstanceOf(AcademicYear::class)
        ->and($academicCalendar->academicYear->id)->toBe($year->id);
});

test('uses ulid as primary key', function () {
    $academicCalendar = AcademicCalendar::factory()->create();

    expect($academicCalendar->id)->toBeString()
        ->and(strlen($academicCalendar->id))->toBe(26);
});

test('supports soft deletes', function () {
    $academicCalendar = AcademicCalendar::factory()->create();
    $academicCalendar->delete();

    expect(AcademicCalendar::count())->toBe(0)
        ->and($academicCalendar->fresh()->trashed())->toBeTrue();
});

test('implements Eventable interface and returns CalendarEvent', function () {
    $academicCalendar = AcademicCalendar::factory()->effective()->create();

    $calendarEvent = $academicCalendar->toCalendarEvent();

    expect($calendarEvent)->toBeInstanceOf(CalendarEvent::class);
});

test('toCalendarEvent returns correct title', function () {
    $academicCalendar = AcademicCalendar::factory()->create(['title' => 'Test Event']);

    $calendarEvent = $academicCalendar->toCalendarEvent();

    expect($calendarEvent->getTitle())->toBe('Test Event');
});

test('toCalendarEvent returns all-day event', function () {
    $academicCalendar = AcademicCalendar::factory()->create(['date' => '2026-08-17']);

    $calendarEvent = $academicCalendar->toCalendarEvent();

    expect($calendarEvent->getAllDay())->toBeTrue();
});

test('toCalendarEvent uses sage background for EFFECTIVE status', function () {
    $academicCalendar = AcademicCalendar::factory()->effective()->create();

    $calendarEvent = $academicCalendar->toCalendarEvent();

    expect($calendarEvent->getBackgroundColor())->toBe(AcademicCalendarColorEnum::SAGE->value);
});

test('toCalendarEvent uses tomato background for NOT_EFFECTIVE status', function () {
    $academicCalendar = AcademicCalendar::factory()->notEffective()->create();

    $calendarEvent = $academicCalendar->toCalendarEvent();

    expect($calendarEvent->getBackgroundColor())->toBe(AcademicCalendarColorEnum::TOMATO->value);
});

test('factory effective state sets correct status', function () {
    $academicCalendar = AcademicCalendar::factory()->effective()->create();

    expect($academicCalendar->status)->toBe(AcademicStatusCalendarEnum::EFFECTIVE);
});

test('factory notEffective state sets correct status', function () {
    $academicCalendar = AcademicCalendar::factory()->notEffective()->create();

    expect($academicCalendar->status)->toBe(AcademicStatusCalendarEnum::NOT_EFFECTIVE);
});

test('observer sets default sage color for EFFECTIVE status', function () {
    $academicCalendar = AcademicCalendar::factory()->effective()->create();

    expect($academicCalendar->color)->toBe(AcademicCalendarColorEnum::SAGE);
});

test('observer sets default tomato color for NOT_EFFECTIVE status', function () {
    $academicCalendar = AcademicCalendar::factory()->notEffective()->create();

    expect($academicCalendar->color)->toBe(AcademicCalendarColorEnum::TOMATO);
});

test('can override default color', function () {
    $academicCalendar = AcademicCalendar::factory()->create([
        'status' => AcademicStatusCalendarEnum::EFFECTIVE,
        'color' => AcademicCalendarColorEnum::BLUEBERRY,
    ]);

    expect($academicCalendar->color)->toBe(AcademicCalendarColorEnum::BLUEBERRY);
});

test('toCalendarEvent uses stored color when set', function () {
    $academicCalendar = AcademicCalendar::factory()->create([
        'status' => AcademicStatusCalendarEnum::NOT_EFFECTIVE,
        'color' => AcademicCalendarColorEnum::BANANA,
    ]);

    $calendarEvent = $academicCalendar->toCalendarEvent();

    expect($calendarEvent->getBackgroundColor())->toBe(AcademicCalendarColorEnum::BANANA->value);
});
