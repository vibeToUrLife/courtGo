<?php

use App\Enums\UserRole;
use App\Livewire\Owner\Billing;
use App\Models\User;
use Livewire\Livewire;

test('the billing page renders for an owner', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    $this->actingAs($owner)->get('/owner/billing')
        ->assertOk()
        ->assertSeeLivewire(Billing::class);
});

test('a customer cannot open the billing page', function () {
    $customer = User::factory()->create(); // customer

    $this->actingAs($customer)->get('/owner/billing')->assertForbidden();
});

test('an owner can save their business registration number', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    Livewire::actingAs($owner)
        ->test(Billing::class)
        ->set('business_registration_number', 'BRN-998877')
        ->call('saveBrn')
        ->assertHasNoErrors();

    expect($owner->fresh()->business_registration_number)->toBe('BRN-998877');
});

test('subscribing without stripe configured redirects back safely', function () {
    config()->set('cashier.secret', null);
    config()->set('services.stripe.price_id', null);
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    $this->actingAs($owner)->get('/owner/billing/subscribe')
        ->assertRedirect(route('owner.billing'));
});

test('connecting a bank without stripe configured redirects back safely', function () {
    config()->set('cashier.secret', null);
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    $this->actingAs($owner)->get('/owner/connect')
        ->assertRedirect(route('owner.billing'));
});
