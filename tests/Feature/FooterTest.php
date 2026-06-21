<?php

use App\Enums\UserRole;
use App\Models\User;

$marker = 'Book courts across Malaysia';

test('the homepage shows the site footer', function () use ($marker) {
    $this->get(route('home'))->assertOk()->assertSee($marker);
});

test('the for-business page shows the site footer', function () use ($marker) {
    $this->get(route('for-business'))->assertOk()->assertSee($marker);
});

test('customer pages show the site footer', function () use ($marker) {
    $this->actingAs(User::factory()->create())
        ->get(route('courts.browse'))
        ->assertOk()
        ->assertSee($marker);
});

test('owner (sidebar) pages do not show the site footer', function () use ($marker) {
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    $this->actingAs($owner)
        ->get(route('owner.venues.index'))
        ->assertOk()
        ->assertDontSee($marker);
});
