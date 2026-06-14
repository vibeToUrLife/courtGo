<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OwnerUserSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::updateOrCreate(
            ['email' => 'owner@courtgo.test'],
            [
                'name' => 'Demo Owner',
                'password' => Hash::make('password'),
                'role' => UserRole::Owner,
                'email_verified_at' => now(),
            ],
        );

        // Give the demo owner a sample venue with a couple of courts (only if they have none yet).
        if ($owner->venues()->doesntExist()) {
            $venue = Venue::create([
                'owner_id' => $owner->id,
                'name' => 'Sunway Badminton Hall',
                'description' => 'A friendly neighbourhood badminton hall.',
                'address' => 'Jalan PJS 11, Bandar Sunway',
                'city' => 'Subang Jaya',
                'state' => 'Selangor',
            ]);

            foreach (['Court 1', 'Court 2', 'Court 3'] as $name) {
                Court::create([
                    'venue_id' => $venue->id,
                    'name' => $name,
                    'sport' => 'Badminton',
                    'is_active' => true,
                ]);
            }
        }
    }
}
