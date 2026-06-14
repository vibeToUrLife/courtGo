<?php

use App\Enums\UserRole;
use App\Livewire\Admin\Owners;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;
use Livewire\Livewire;

function subscribedOnboardedOwner(array $extra = []): User
{
    $owner = User::factory()->create(array_merge([
        'role' => UserRole::Owner,
        'connect_onboarded' => true,
    ], $extra));

    $owner->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_'.uniqid(),
        'stripe_status' => 'active',
        'stripe_price' => 'price_test',
        'quantity' => 1,
    ]);

    return $owner;
}

test('the admin dashboard renders for an admin', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
});

test('a customer cannot access the admin area', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)->get('/admin/dashboard')->assertForbidden();
});

test('an admin can suspend and unsuspend an owner', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    Livewire::actingAs($admin)->test(Owners::class)->call('toggleSuspend', $owner->id);
    expect($owner->fresh()->is_suspended)->toBeTrue();

    Livewire::actingAs($admin)->test(Owners::class)->call('toggleSuspend', $owner->id);
    expect($owner->fresh()->is_suspended)->toBeFalse();
});

test('a suspended owner cannot accept bookings', function () {
    $owner = subscribedOnboardedOwner(['is_suspended' => true]);

    expect($owner->fresh()->canAcceptBookings())->toBeFalse();
});

test('a suspended owners courts are not bookable', function () {
    $owner = subscribedOnboardedOwner(['is_suspended' => true]);
    $venue = Venue::factory()->for($owner, 'owner')->create();
    Court::factory()->for($venue)->create(['is_active' => true]);

    expect(Court::bookable()->count())->toBe(0);
});
