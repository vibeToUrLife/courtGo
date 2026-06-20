<?php

use App\Models\Court;
use App\Models\SessionTemplate;
use App\Models\VenueClosedDate;
use App\Services\AvailabilityService;
use Illuminate\Support\Carbon;

function availability(): AvailabilityService
{
    return app(AvailabilityService::class);
}

test('it returns active sessions for the matching weekday', function () {
    $date = Carbon::parse('2026-07-06'); // a fixed future date
    $court = Court::factory()->create();

    SessionTemplate::factory()->for($court)->create([
        'day_of_week' => $date->dayOfWeek,
        'start_time' => '09:00',
        'end_time' => '11:00',
    ]);

    $sessions = availability()->availableSessions($court, $date);

    expect($sessions)->toHaveCount(1);
});

test('it excludes sessions for other weekdays', function () {
    $date = Carbon::parse('2026-07-06');
    $court = Court::factory()->create();

    SessionTemplate::factory()->for($court)->create([
        'day_of_week' => ($date->dayOfWeek + 1) % 7, // a different day
    ]);

    expect(availability()->availableSessions($court, $date))->toHaveCount(0);
});

test('it excludes inactive sessions', function () {
    $date = Carbon::parse('2026-07-06');
    $court = Court::factory()->create();

    SessionTemplate::factory()->for($court)->create([
        'day_of_week' => $date->dayOfWeek,
        'is_active' => false,
    ]);

    expect(availability()->availableSessions($court, $date))->toHaveCount(0);
});

test('it returns nothing when the venue is closed on that date', function () {
    $date = Carbon::parse('2026-07-06');
    $court = Court::factory()->create();

    SessionTemplate::factory()->for($court)->create(['day_of_week' => $date->dayOfWeek]);
    VenueClosedDate::factory()->for($court->venue)->create(['date' => $date->toDateString()]);

    expect(availability()->availableSessions($court, $date))->toHaveCount(0);
});

test('a venue closed date hides every court in that venue', function () {
    $date = Carbon::parse('2026-07-06');
    $venue = \App\Models\Venue::factory()->create();
    $courtA = Court::factory()->for($venue)->create();
    $courtB = Court::factory()->for($venue)->create();

    SessionTemplate::factory()->for($courtA)->create(['day_of_week' => $date->dayOfWeek]);
    SessionTemplate::factory()->for($courtB)->create(['day_of_week' => $date->dayOfWeek]);
    VenueClosedDate::factory()->for($venue)->create(['date' => $date->toDateString()]);

    expect(availability()->availableSessions($courtA, $date))->toHaveCount(0)
        ->and(availability()->availableSessions($courtB, $date))->toHaveCount(0);
});

test('it returns nothing for a past date', function () {
    $court = Court::factory()->create();
    $past = Carbon::yesterday();

    SessionTemplate::factory()->for($court)->create(['day_of_week' => $past->dayOfWeek]);

    expect(availability()->availableSessions($court, $past))->toHaveCount(0);
});

test('for today it hides sessions that have already started', function () {
    // Freeze "now" at 10:00 on a known date (auto-resets after the test).
    $this->travelTo(Carbon::parse('2026-07-06 10:00:00'));

    $today = Carbon::today();
    $court = Court::factory()->create();

    SessionTemplate::factory()->for($court)->create([
        'day_of_week' => $today->dayOfWeek,
        'start_time' => '09:00', // already started -> hidden
        'end_time' => '11:00',
    ]);
    SessionTemplate::factory()->for($court)->create([
        'day_of_week' => $today->dayOfWeek,
        'start_time' => '14:00', // later today -> shown
        'end_time' => '16:00',
    ]);

    $sessions = availability()->availableSessions($court, $today);

    expect($sessions)->toHaveCount(1)
        ->and($sessions->first()->start_time)->toStartWith('14:00');
});

test('it orders sessions by start time', function () {
    $date = Carbon::parse('2026-07-06');
    $court = Court::factory()->create();

    SessionTemplate::factory()->for($court)->create(['day_of_week' => $date->dayOfWeek, 'start_time' => '18:00', 'end_time' => '20:00']);
    SessionTemplate::factory()->for($court)->create(['day_of_week' => $date->dayOfWeek, 'start_time' => '08:00', 'end_time' => '10:00']);

    $sessions = availability()->availableSessions($court, $date);

    expect($sessions->first()->start_time)->toStartWith('08:00')
        ->and($sessions->last()->start_time)->toStartWith('18:00');
});
