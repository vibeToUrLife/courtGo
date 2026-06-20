<?php

use App\Enums\UserRole;
use App\Models\Court;
use App\Models\SessionTemplate;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Carbon;

/** A live venue (subscribed + Connect-onboarded owner) with one priced session on $date's weekday. */
function browseLiveVenue(Carbon $date, float $price = 40): Venue
{
    $owner = User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => true]);
    $owner->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_'.uniqid(),
        'stripe_status' => 'active',
        'stripe_price' => 'price_test',
        'quantity' => 1,
    ]);
    $venue = Venue::factory()->for($owner, 'owner')->create(['name' => 'Smash Arena', 'city' => 'Subang Jaya']);
    $court = Court::factory()->for($venue)->create(['is_active' => true, 'sport' => 'Badminton']);
    SessionTemplate::factory()->for($court)->create([
        'day_of_week' => $date->dayOfWeek, 'start_time' => '09:00', 'end_time' => '11:00', 'price' => $price,
    ]);

    return $venue;
}

test('the browse page shows price-from and live availability for the chosen date', function () {
    $date = Carbon::today()->addDays(8); // future, deterministic weekday
    $venue = browseLiveVenue($date, 40);

    $this->actingAs(User::factory()->create())
        ->get(route('courts.browse', ['date' => $date->toDateString()]))
        ->assertOk()
        ->assertSee('Smash Arena')
        ->assertSee('from RM40')
        ->assertSee('1 session(s) available');
});

test('the venue link does not pre-fill a date (the customer chooses one there)', function () {
    $date = Carbon::today()->addDays(8);
    $venue = browseLiveVenue($date);

    $this->actingAs(User::factory()->create())
        ->get(route('courts.browse', ['date' => $date->toDateString()]))
        ->assertSee(route('venues.show', ['venue' => $venue]), escape: false)
        ->assertDontSee(route('venues.show', ['venue' => $venue, 'date' => $date->toDateString()]), escape: false);
});

test('the browse page filters venues by state', function () {
    $date = Carbon::today()->addDays(8);

    $selangor = browseLiveVenue($date);
    $selangor->update(['name' => 'Selangor Arena', 'state' => 'Selangor']);

    $penang = browseLiveVenue($date);
    $penang->update(['name' => 'Penang Arena', 'state' => 'Penang']);

    $this->actingAs(User::factory()->create())
        ->get(route('courts.browse', ['state' => 'Selangor', 'date' => $date->toDateString()]))
        ->assertOk()
        ->assertSee('Selangor Arena')
        ->assertDontSee('Penang Arena');
});

test('the browse page uses the sidebar-free customer layout', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('courts.browse'))
        ->assertOk()
        ->assertSee('Back to homepage')  // the customer-layout affordance
        ->assertDontSee('Platform');     // the owner/admin sidebar heading is absent
});

test('a date with no matching session shows fully booked', function () {
    $date = Carbon::today()->addDays(8);
    browseLiveVenue($date); // session is on $date's weekday only

    // Query a different weekday (the day before) → no sessions that day.
    $otherDay = $date->copy()->addDay();

    $this->actingAs(User::factory()->create())
        ->get(route('courts.browse', ['date' => $otherDay->toDateString()]))
        ->assertOk()
        ->assertSee('Smash Arena')   // still listed (it is bookable)
        ->assertSee('Fully booked');
});
