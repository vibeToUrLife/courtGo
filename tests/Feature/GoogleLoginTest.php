<?php

use App\Enums\UserRole;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

test('the google callback creates and logs in a new customer', function () {
    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-abc-123',
        'name' => 'Ah Meng',
        'email' => 'ahmeng@example.com',
    ]));

    $response = $this->get('/auth/google/callback');

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'email' => 'ahmeng@example.com',
        'google_id' => 'google-abc-123',
        'role' => UserRole::Customer->value,
    ]);
    $response->assertRedirect('/dashboard');
});
