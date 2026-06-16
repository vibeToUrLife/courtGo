<?php

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Exceptions\SlotUnavailableException;
use App\Models\Court;
use App\Models\SessionTemplate;
use App\Models\User;
use App\Models\Venue;
use App\Services\BookingService;
use Illuminate\Support\Carbon;

/** A session on a court whose owner is subscribed + Connect-onboarded (so it's bookable). */
function liveSession(Carbon $date): SessionTemplate
{
    $owner = User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => true]);
    $owner->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_'.uniqid(),
        'stripe_status' => 'active',
        'stripe_price' => 'price_test',
        'quantity' => 1,
    ]);
    $venue = Venue::factory()->for($owner, 'owner')->create();
    $court = Court::factory()->for($venue)->create(['is_active' => true]);

    return SessionTemplate::factory()->for($court)->create([
        'day_of_week' => $date->dayOfWeek,
        'start_time' => '09:00',
        'end_time' => '11:00',
        'price' => 40,
    ]);
}

test('reserving creates a pending booking with a hold', function () {
    $date = Carbon::parse('2026-07-06');
    $session = liveSession($date);

    $booking = app(BookingService::class)->reserve(User::factory()->create(), $session, $date);

    expect($booking->status)->toBe(BookingStatus::Pending)
        ->and($booking->hold_expires_at)->not->toBeNull()
        ->and((float) $booking->price)->toBe(40.0);
});

test('reserving an already-held slot throws SlotUnavailable', function () {
    $date = Carbon::parse('2026-07-06');
    $session = liveSession($date);

    app(BookingService::class)->reserve(User::factory()->create(), $session, $date);

    expect(fn () => app(BookingService::class)->reserve(User::factory()->create(), $session, $date))
        ->toThrow(SlotUnavailableException::class);
});

test('a slot with an expired pending hold can be reserved again', function () {
    $date = Carbon::parse('2026-07-06');
    $session = liveSession($date);

    // First customer holds the slot, then the hold expires (row still 'pending' — sweep job hasn't run).
    $first = app(BookingService::class)->reserve(User::factory()->create(), $session, $date);
    $first->update(['hold_expires_at' => now()->subMinute()]);

    // Second customer should be able to grab the reverted slot.
    $second = app(BookingService::class)->reserve(User::factory()->create(), $session, $date);

    expect($second->status)->toBe(BookingStatus::Pending)
        ->and($first->fresh()->status)->toBe(BookingStatus::Expired);
});

test('reserving a court that is not live throws SlotUnavailable', function () {
    $date = Carbon::parse('2026-07-06');
    $owner = User::factory()->create(['role' => UserRole::Owner]); // no subscription / not onboarded
    $venue = Venue::factory()->for($owner, 'owner')->create();
    $court = Court::factory()->for($venue)->create(['is_active' => true]);
    $session = SessionTemplate::factory()->for($court)->create([
        'day_of_week' => $date->dayOfWeek, 'start_time' => '09:00', 'end_time' => '11:00',
    ]);

    expect(fn () => app(BookingService::class)->reserve(User::factory()->create(), $session, $date))
        ->toThrow(SlotUnavailableException::class);
});
