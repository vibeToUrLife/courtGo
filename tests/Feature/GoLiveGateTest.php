<?php

use App\Enums\UserRole;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;

/** Give a venue its own active subscription (one subscription per venue). */
function subscribeVenue(Venue $venue): void
{
    $venue->owner->subscriptions()->create([
        'type' => $venue->subscriptionType(),
        'stripe_id' => 'sub_'.uniqid(),
        'stripe_status' => 'active',
        'stripe_price' => 'price_test',
        'quantity' => 1,
    ]);
}

test('an owner is payout-ready only when not suspended and Connect-onboarded', function () {
    expect(User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => false])->canAcceptBookings())->toBeFalse()
        ->and(User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => true])->canAcceptBookings())->toBeTrue()
        ->and(User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => true, 'is_suspended' => true])->canAcceptBookings())->toBeFalse();
});

test('a venue is subscribed only with its own active subscription', function () {
    $venue = Venue::factory()->create();
    expect($venue->isSubscribed())->toBeFalse();

    subscribeVenue($venue);
    expect($venue->fresh()->isSubscribed())->toBeTrue();
});

test('a court is bookable when active, in an approved + subscribed venue with a live owner', function () {
    $venue = Venue::factory()->subscribed()->create(); // approved + onboarded owner + venue subscription

    $active = Court::factory()->for($venue)->create(['is_active' => true]);
    $inactive = Court::factory()->for($venue)->create(['is_active' => false]);

    expect($active->isBookable())->toBeTrue()
        ->and($inactive->isBookable())->toBeFalse();
});

test('a court is not bookable when its venue has no subscription', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => true]);
    $venue = Venue::factory()->for($owner, 'owner')->create(); // approved but not subscribed
    $court = Court::factory()->for($venue)->create(['is_active' => true]);

    expect($court->isBookable())->toBeFalse();
});

test('a court in a pending venue is not bookable even when subscribed', function () {
    $venue = Venue::factory()->pending()->subscribed()->create();
    $court = Court::factory()->for($venue)->create(['is_active' => true]);

    expect($court->fresh()->isBookable())->toBeFalse();
});

test('a pending venue is hidden from customers', function () {
    $venue = Venue::factory()->pending()->subscribed()->create();
    Court::factory()->for($venue)->create(['is_active' => true]);

    expect(Court::bookable()->count())->toBe(0)
        ->and(Venue::bookable()->count())->toBe(0);
});

test('approving a pending subscribed venue makes its courts bookable', function () {
    $venue = Venue::factory()->pending()->subscribed()->create();
    Court::factory()->for($venue)->create(['is_active' => true]);

    expect(Court::bookable()->count())->toBe(0);

    $venue->update(['approved_at' => now()]);

    expect(Court::bookable()->count())->toBe(1);
});

test('a subscription in its grace period (canceled, ends in the future) is still bookable', function () {
    $venue = Venue::factory()->subscribed()->create();
    Court::factory()->for($venue)->create(['is_active' => true]);
    $venue->owner->subscriptions()->first()->update(['stripe_status' => 'canceled', 'ends_at' => now()->addDays(5)]);

    expect($venue->fresh()->isSubscribed())->toBeTrue()      // PHP gate
        ->and(Court::bookable()->count())->toBe(1);           // SQL gate agrees
});

test('a subscription that has fully ended is not bookable', function () {
    $venue = Venue::factory()->subscribed()->create();
    Court::factory()->for($venue)->create(['is_active' => true]);
    $venue->owner->subscriptions()->first()->update(['stripe_status' => 'canceled', 'ends_at' => now()->subDay()]);

    expect($venue->fresh()->isSubscribed())->toBeFalse()
        ->and(Court::bookable()->count())->toBe(0);
});

test('subscribing one venue does not make the owners other venue bookable', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => true]);

    $subscribed = Venue::factory()->for($owner, 'owner')->create();
    subscribeVenue($subscribed);
    Court::factory()->for($subscribed)->create(['is_active' => true]);

    $unsubscribed = Venue::factory()->for($owner, 'owner')->create(); // approved, but no subscription
    Court::factory()->for($unsubscribed)->create(['is_active' => true]);

    expect(Court::bookable()->count())->toBe(1) // only the subscribed venue's court
        ->and($subscribed->courts()->first()->isBookable())->toBeTrue()
        ->and($unsubscribed->courts()->first()->isBookable())->toBeFalse();
});
