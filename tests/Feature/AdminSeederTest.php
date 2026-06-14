<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;

test('the admin seeder creates an admin user', function () {
    $this->seed(AdminUserSeeder::class);

    $admin = User::where('email', 'admin@courtgo.test')->first();

    expect($admin)->not->toBeNull();
    expect($admin->role)->toBe(UserRole::Admin);
});
