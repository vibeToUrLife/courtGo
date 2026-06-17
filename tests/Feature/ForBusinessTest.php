<?php

use App\Enums\UserRole;
use App\Models\User;

test('the for-business page is public and pitches venue owners', function () {
    $this->get(route('for-business'))
        ->assertOk()
        ->assertSee('Grow your venue')
        ->assertSee('0% booking commission')
        ->assertSee('Create your owner account');
});

test('the for-business page links sign-up to owner registration', function () {
    $this->get(route('for-business'))
        ->assertOk()
        ->assertSee(route('register', ['as' => 'owner']), escape: false);
});

test('an authenticated user sees a dashboard link on the for-business page', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Owner]));

    $this->get(route('for-business'))
        ->assertOk()
        ->assertSee('Go to dashboard');
});
