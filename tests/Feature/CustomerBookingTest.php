<?php

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Livewire\VenueShow;
use App\Models\Booking;
use App\Models\Court;
use App\Models\SessionTemplate;
use App\Models\User;
use App\Models\Venue;
use App\Services\BookingService;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

/** A live venue with two courts, each having one session on $date. */
function liveTwoCourtVenue(Carbon $date): array
{
    $owner = User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => true]);
    $owner->subscriptions()->create([
        'type' => 'default', 'stripe_id' => 'sub_'.uniqid(), 'stripe_status' => 'active',
        'stripe_price' => 'price_test', 'quantity' => 1,
    ]);
    $venue = Venue::factory()->for($owner, 'owner')->create();
    $courtA = Court::factory()->for($venue)->create(['is_active' => true, 'name' => 'Court A']);
    $courtB = Court::factory()->for($venue)->create(['is_active' => true, 'name' => 'Court B']);
    $sA = SessionTemplate::factory()->for($courtA)->create(['day_of_week' => $date->dayOfWeek, 'start_time' => '09:00', 'end_time' => '11:00', 'price' => 40]);
    $sB = SessionTemplate::factory()->for($courtB)->create(['day_of_week' => $date->dayOfWeek, 'start_time' => '09:00', 'end_time' => '11:00', 'price' => 50]);

    return [$venue, $courtA, $courtB, $sA, $sB];
}

/** A session on a court whose owner is live (subscribed + Connect-onboarded). */
function liveCourtSession(Carbon $date): SessionTemplate
{
    $owner = User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => true]);
    $owner->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_'.uniqid(),
        'stripe_status' => 'active',
        'stripe_price' => 'price_test',
        'quantity' => 1,
    ]);
    $venue = Venue::factory()->for($owner, 'owner')->create(['city' => 'Subang Jaya']);
    $court = Court::factory()->for($venue)->create(['is_active' => true, 'sport' => 'Badminton']);

    return SessionTemplate::factory()->for($court)->create([
        'day_of_week' => $date->dayOfWeek, 'start_time' => '09:00', 'end_time' => '11:00', 'price' => 40,
    ]);
}

test('a guest is redirected to login from browse', function () {
    $this->get('/courts')->assertRedirect('/login');
});

test('the browse page lists the place (venue), not individual courts', function () {
    $session = liveCourtSession(Carbon::parse('2026-07-06'));

    $this->actingAs(User::factory()->create())->get('/courts')
        ->assertOk()
        ->assertSee($session->court->venue->name);
});

test('the browse page hides places whose owner is not live', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]); // not subscribed/onboarded
    $venue = Venue::factory()->for($owner, 'owner')->create(['name' => 'Hidden Hall']);
    Court::factory()->for($venue)->create(['is_active' => true]);

    $this->actingAs(User::factory()->create())->get('/courts')->assertDontSee('Hidden Hall');
});

test('the browse page can filter places by name', function () {
    $a = liveCourtSession(Carbon::parse('2026-07-06'));
    $a->court->venue->update(['name' => 'Alpha Hall']);
    $b = liveCourtSession(Carbon::parse('2026-07-06'));
    $b->court->venue->update(['name' => 'Beta Hall']);

    $this->actingAs(User::factory()->create())
        ->get(route('courts.browse', ['name' => 'Alpha']))
        ->assertSee('Alpha Hall')
        ->assertDontSee('Beta Hall');
});

test('the venue page shows a bookable calendar grid for the chosen date', function () {
    $date = Carbon::parse('2026-07-06');
    $session = liveCourtSession($date);

    $this->actingAs(User::factory()->create())
        ->get(route('venues.show', ['venue' => $session->court->venue, 'date' => $date->toDateString()]))
        ->assertOk()
        ->assertSee($session->court->venue->name)
        ->assertSee($session->court->name)  // a court row
        ->assertSee('9:00 AM')              // a time column
        ->assertSee('RM 40');               // a selectable slot
});

test('the venue page waits for a date before showing the calendar', function () {
    $date = Carbon::parse('2026-07-06');
    $session = liveCourtSession($date);

    Livewire::actingAs(User::factory()->create())
        ->test(VenueShow::class, ['venue' => $session->court->venue])
        ->assertSet('date', '')                  // no default date
        ->assertSee('Choose a date above')       // a prompt, not the calendar
        ->assertDontSee($session->court->name)
        ->set('date', $date->toDateString())     // the customer picks a date
        ->assertSee($session->court->name)       // now the calendar appears
        ->assertSee('RM 40');
});

test('time slots that have already started today are hidden, not shown as booked', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-06 14:00:00')); // Monday, 2pm
    $date = Carbon::parse('2026-07-06');

    $owner = User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => true]);
    $owner->subscriptions()->create([
        'type' => 'default', 'stripe_id' => 'sub_'.uniqid(), 'stripe_status' => 'active',
        'stripe_price' => 'price_test', 'quantity' => 1,
    ]);
    $venue = Venue::factory()->for($owner, 'owner')->create();
    $court = Court::factory()->for($venue)->create(['is_active' => true]);
    SessionTemplate::factory()->for($court)->create(['day_of_week' => $date->dayOfWeek, 'start_time' => '10:00', 'end_time' => '11:00', 'price' => 8]); // past
    SessionTemplate::factory()->for($court)->create(['day_of_week' => $date->dayOfWeek, 'start_time' => '16:00', 'end_time' => '17:00', 'price' => 8]); // future

    $grid = Livewire::actingAs(User::factory()->create())
        ->test(VenueShow::class, ['venue' => $venue])
        ->set('date', $date->toDateString())
        ->viewData('grid');

    Carbon::setTestNow(); // reset before asserting

    expect($grid[$court->id] ?? [])->not->toHaveKey('10:00-11:00') // past slot hidden
        ->and($grid[$court->id])->toHaveKey('16:00-17:00');         // future slot shown
});

test('a booked slot shows as taken in the calendar grid', function () {
    config()->set('cashier.secret', null); // demo mode confirms the booking
    $date = Carbon::parse('2026-07-06');
    $session = liveCourtSession($date);

    // A customer books the only slot.
    $this->actingAs(User::factory()->create())
        ->get(route('bookings.checkout', ['court' => $session->court, 'session' => $session, 'date' => $date->toDateString()]));

    // Another customer now sees it as taken, not selectable.
    $this->actingAs(User::factory()->create())
        ->get(route('venues.show', ['venue' => $session->court->venue, 'date' => $date->toDateString()]))
        ->assertOk()
        ->assertSee('Booked')
        ->assertDontSee('RM 40'); // the only slot is taken → no selectable price
});

test('a customer can select multiple slots and pay for them in one go', function () {
    config()->set('cashier.secret', null); // demo mode confirms
    $date = Carbon::parse('2026-07-06');
    [$venue, $courtA, $courtB, $sA, $sB] = liveTwoCourtVenue($date);
    $customer = User::factory()->create();

    Livewire::actingAs($customer)
        ->test(VenueShow::class, ['venue' => $venue])
        ->set('date', $date->toDateString())
        ->call('toggleSlot', $courtA->id, $sA->id)
        ->call('toggleSlot', $courtB->id, $sB->id)
        ->assertCount('selected', 2)
        ->call('checkout')
        ->assertRedirect(route('bookings.mine'));

    expect($customer->bookings()->count())->toBe(2)
        ->and($customer->bookings()->where('status', BookingStatus::Confirmed->value)->count())->toBe(2)
        ->and((float) $customer->bookings()->sum('price'))->toBe(90.0); // 40 + 50
});

test('booking slots on one court leaves the other courts available in the grid', function () {
    config()->set('cashier.secret', null); // demo confirms
    $date = Carbon::parse('2026-07-06');

    $owner = User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => true]);
    $owner->subscriptions()->create([
        'type' => 'default', 'stripe_id' => 'sub_'.uniqid(), 'stripe_status' => 'active',
        'stripe_price' => 'price_test', 'quantity' => 1,
    ]);
    $venue = Venue::factory()->for($owner, 'owner')->create();
    $courtA = Court::factory()->for($venue)->create(['is_active' => true, 'name' => 'Court A']);
    $courtB = Court::factory()->for($venue)->create(['is_active' => true, 'name' => 'Court B']);
    foreach ([$courtA, $courtB] as $court) {
        SessionTemplate::factory()->for($court)->create(['day_of_week' => $date->dayOfWeek, 'start_time' => '10:00', 'end_time' => '10:30', 'price' => 8]);
        SessionTemplate::factory()->for($court)->create(['day_of_week' => $date->dayOfWeek, 'start_time' => '10:30', 'end_time' => '11:00', 'price' => 8]);
    }

    // A customer books BOTH of Court A's slots.
    $aSessions = $courtA->sessionTemplates()->orderBy('start_time')->get();
    Livewire::actingAs(User::factory()->create())
        ->test(VenueShow::class, ['venue' => $venue])
        ->set('date', $date->toDateString())
        ->call('toggleSlot', $courtA->id, $aSessions[0]->id)
        ->call('toggleSlot', $courtA->id, $aSessions[1]->id)
        ->call('checkout')
        ->assertRedirect(route('bookings.mine'));

    // Another customer's grid: only Court A's slots are taken; Court B stays open.
    $grid = Livewire::actingAs(User::factory()->create())
        ->test(VenueShow::class, ['venue' => $venue])
        ->set('date', $date->toDateString())
        ->viewData('grid');

    expect($grid[$courtA->id]['10:00-10:30']['state'])->toBe('taken')
        ->and($grid[$courtA->id]['10:30-11:00']['state'])->toBe('taken')
        ->and($grid[$courtB->id]['10:00-10:30']['state'])->toBe('available')
        ->and($grid[$courtB->id]['10:30-11:00']['state'])->toBe('available');
});

test('selecting can be toggled off before booking', function () {
    $date = Carbon::parse('2026-07-06');
    [$venue, $courtA, , $sA] = liveTwoCourtVenue($date);
    $customer = User::factory()->create();

    Livewire::actingAs($customer)
        ->test(VenueShow::class, ['venue' => $venue])
        ->set('date', $date->toDateString())
        ->call('toggleSlot', $courtA->id, $sA->id)
        ->assertCount('selected', 1)
        ->call('toggleSlot', $courtA->id, $sA->id) // tap again to deselect
        ->assertCount('selected', 0);
});

test('booking multiple slots is all-or-nothing when one is taken', function () {
    config()->set('cashier.secret', null);
    $date = Carbon::parse('2026-07-06');
    [$venue, $courtA, $courtB, $sA, $sB] = liveTwoCourtVenue($date);

    // Someone else has already taken court A's slot.
    app(BookingService::class)->reserve(User::factory()->create(), $sA, $date)
        ->update(['status' => BookingStatus::Confirmed]);

    $customer = User::factory()->create();
    Livewire::actingAs($customer)
        ->test(VenueShow::class, ['venue' => $venue])
        ->set('date', $date->toDateString())
        ->call('toggleSlot', $courtA->id, $sA->id)
        ->call('toggleSlot', $courtB->id, $sB->id)
        ->call('checkout'); // no redirect — the whole booking is rejected

    // Neither slot was booked for this customer (all-or-nothing).
    expect($customer->bookings()->count())->toBe(0);
});

test('a customer can book a session (demo mode confirms it)', function () {
    config()->set('cashier.secret', null); // demo mode (no real Stripe)
    $date = Carbon::parse('2026-07-06');
    $session = liveCourtSession($date);
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('bookings.checkout', ['court' => $session->court, 'session' => $session, 'date' => $date->toDateString()]))
        ->assertRedirect();

    $booking = $customer->bookings()->first();
    expect($booking)->not->toBeNull()
        ->and($booking->status)->toBe(BookingStatus::Confirmed);
});

test('booking the same slot twice is rejected', function () {
    config()->set('cashier.secret', null);
    $date = Carbon::parse('2026-07-06');
    $session = liveCourtSession($date);

    $this->actingAs(User::factory()->create())
        ->get(route('bookings.checkout', ['court' => $session->court, 'session' => $session, 'date' => $date->toDateString()]));

    $this->actingAs(User::factory()->create())
        ->get(route('bookings.checkout', ['court' => $session->court, 'session' => $session, 'date' => $date->toDateString()]))
        ->assertSessionHas('booking_error');

    expect(Booking::where('court_id', $session->court_id)->count())->toBe(1);
});

test('a customer can resume payment for a pending booking', function () {
    config()->set('cashier.secret', null); // demo mode confirms
    $customer = User::factory()->create();
    $booking = Booking::factory()->pending()->create(['customer_id' => $customer->id]);

    $this->actingAs($customer)->get(route('bookings.pay', $booking))->assertRedirect();

    expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
});

test('a customer cannot resume payment for an expired hold', function () {
    $customer = User::factory()->create();
    $booking = Booking::factory()->pending()->create([
        'customer_id' => $customer->id,
        'hold_expires_at' => now()->subMinute(),
    ]);

    $this->actingAs($customer)->get(route('bookings.pay', $booking))
        ->assertRedirect(route('bookings.mine'));

    expect($booking->fresh()->status)->toBe(BookingStatus::Pending);
});

test('my bookings can filter to awaiting payment', function () {
    $customer = User::factory()->create();

    $cVenue = Venue::factory()->create(['name' => 'Confirmed Venue']);
    Booking::factory()->for(Court::factory()->for($cVenue)->create())
        ->create(['customer_id' => $customer->id, 'status' => BookingStatus::Confirmed]);

    $aVenue = Venue::factory()->create(['name' => 'Awaiting Venue']);
    Booking::factory()->pending()->for(Court::factory()->for($aVenue)->create())
        ->create(['customer_id' => $customer->id]);

    $this->actingAs($customer)->get(route('bookings.mine', ['filter' => 'awaiting']))
        ->assertSee('Awaiting Venue')
        ->assertDontSee('Confirmed Venue');
});

test('my bookings shows the customer booking', function () {
    config()->set('cashier.secret', null);
    $date = Carbon::parse('2026-07-06');
    $session = liveCourtSession($date);
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('bookings.checkout', ['court' => $session->court, 'session' => $session, 'date' => $date->toDateString()]));

    $this->actingAs($customer)->get('/my-bookings')
        ->assertOk()
        ->assertSee($session->court->venue->name)
        ->assertSee('Back to homepage')  // customer-layout affordance
        ->assertDontSee('Platform');     // owner/admin sidebar heading is absent
});
