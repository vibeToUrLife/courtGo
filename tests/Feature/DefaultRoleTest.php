<?php

use App\Enums\UserRole;
use App\Models\User;

test('a new user defaults to the customer role', function () {
    $user = User::factory()->create();

    expect($user->fresh()->role)->toBe(UserRole::Customer);
});
