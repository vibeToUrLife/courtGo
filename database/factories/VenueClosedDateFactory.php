<?php

namespace Database\Factories;

use App\Models\Venue;
use App\Models\VenueClosedDate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenueClosedDate>
 */
class VenueClosedDateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'date' => fake()->unique()->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
            'reason' => fake()->randomElement(['Public holiday', 'Maintenance', 'Private event']),
        ];
    }
}
