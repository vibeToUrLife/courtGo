<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Court;
use App\Models\SessionTemplate;
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

        // Give the demo owner a sample venue with a few courts (only if they have none yet).
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

        // Ensure each of the demo owner's courts has a sample weekly schedule
        // (idempotent — only adds sessions to courts that have none yet).
        $owner->load('venues.courts');

        foreach ($owner->venues as $venue) {
            foreach ($venue->courts as $court) {
                if ($court->sessionTemplates()->exists()) {
                    continue;
                }

                // Weeknight evening sessions (Mon–Fri, 8–10pm, RM40).
                foreach ([1, 2, 3, 4, 5] as $weekday) {
                    SessionTemplate::create([
                        'court_id' => $court->id,
                        'day_of_week' => $weekday,
                        'start_time' => '20:00',
                        'end_time' => '22:00',
                        'price' => 40,
                        'is_active' => true,
                    ]);
                }

                // Weekend morning sessions (Sat & Sun, 9–11am, RM50).
                foreach ([6, 0] as $weekday) {
                    SessionTemplate::create([
                        'court_id' => $court->id,
                        'day_of_week' => $weekday,
                        'start_time' => '09:00',
                        'end_time' => '11:00',
                        'price' => 50,
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
}
