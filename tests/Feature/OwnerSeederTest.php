<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\OwnerUserSeeder;

test('the owner seeder creates a demo owner with a venue and courts', function () {
    $this->seed(OwnerUserSeeder::class);

    $owner = User::where('email', 'owner@courtgo.test')->first();

    expect($owner)->not->toBeNull();
    expect($owner->role)->toBe(UserRole::Owner);
    expect($owner->venues()->count())->toBeGreaterThan(0);
    expect($owner->venues()->first()->courts()->count())->toBeGreaterThan(0);
});
