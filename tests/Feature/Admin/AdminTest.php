<?php

use App\Enums\UserRole;
use App\Livewire\Admin\Owners;
use App\Livewire\Admin\Venues;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;
use Livewire\Livewire;

/** A Connect-onboarded owner (the per-venue subscription lives on the venue now). */
function onboardedOwner(array $extra = []): User
{
    return User::factory()->create(array_merge([
        'role' => UserRole::Owner,
        'connect_onboarded' => true,
    ], $extra));
}

test('the admin dashboard renders for an admin', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
});

test('a customer cannot access the admin area', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)->get('/admin/dashboard')->assertForbidden();
});

test('an admin can approve a pending venue', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $venue = Venue::factory()->pending()->create();

    expect($venue->isApproved())->toBeFalse();

    Livewire::actingAs($admin)->test(Venues::class)->call('approve', $venue->id);

    expect($venue->fresh()->isApproved())->toBeTrue();
});

test('an admin can suspend and unsuspend an owner', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    Livewire::actingAs($admin)->test(Owners::class)->call('toggleSuspend', $owner->id);
    expect($owner->fresh()->is_suspended)->toBeTrue();

    Livewire::actingAs($admin)->test(Owners::class)->call('toggleSuspend', $owner->id);
    expect($owner->fresh()->is_suspended)->toBeFalse();
});

test('the admin dashboard counts active per-venue subscriptions', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    Venue::factory()->subscribed()->create(); // one active venue subscription

    Livewire::actingAs($admin)
        ->test(\App\Livewire\Admin\Dashboard::class)
        ->assertViewHas('activeSubscriptions', 1);
});

test('a suspended owner cannot accept bookings', function () {
    $owner = onboardedOwner(['is_suspended' => true]);

    expect($owner->fresh()->canAcceptBookings())->toBeFalse();
});

test('a suspended owners courts are not bookable', function () {
    // Otherwise fully live (approved + subscribed), so suspension is the only blocker.
    $venue = Venue::factory()->subscribed()->create();
    $venue->owner->update(['is_suspended' => true]);
    Court::factory()->for($venue)->create(['is_active' => true]);

    expect(Court::bookable()->count())->toBe(0);
});
