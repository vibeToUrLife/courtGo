<?php

use App\Enums\UserRole;
use App\Models\User;

test('registering with role=owner creates an owner account', function () {
    $this->post(route('register.store'), [
        'name' => 'Venue Boss',
        'email' => 'boss@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'owner',
    ])->assertSessionHasNoErrors();

    expect(User::where('email', 'boss@example.com')->first()->role)
        ->toBe(UserRole::Owner);
});

test('registering without a role still creates a customer', function () {
    $this->post(route('register.store'), [
        'name' => 'Normal Player',
        'email' => 'player@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    expect(User::where('email', 'player@example.com')->first()->role)
        ->toBe(UserRole::Customer);
});

test('the register page renders the hidden owner field when arriving as an owner', function () {
    $this->get(route('register', ['as' => 'owner']))
        ->assertOk()
        ->assertSee('Create your owner account')
        ->assertSee('name="role"', escape: false)
        ->assertSee('value="owner"', escape: false);
});

test('the plain register page never exposes the role field', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertDontSee('name="role"', escape: false);
});

test('owner mode survives bouncing between the register and login links', function () {
    // The register page's "Log in" link keeps owner mode...
    $this->get(route('register', ['as' => 'owner']))
        ->assertSee(route('login', ['as' => 'owner']), escape: false);

    // ...and the login page's "Sign up" link keeps owner mode, so the
    // round-trip register -> login -> register stays an owner sign-up.
    $this->get(route('login', ['as' => 'owner']))
        ->assertSee(route('register', ['as' => 'owner']), escape: false);
});

test('a visitor cannot self-register as an admin', function () {
    $this->post(route('register.store'), [
        'name' => 'Sneaky',
        'email' => 'sneaky@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'admin',
    ]);

    // 'admin' is never honoured — they fall back to a plain customer.
    expect(User::where('email', 'sneaky@example.com')->first()->role)
        ->toBe(UserRole::Customer);
});
