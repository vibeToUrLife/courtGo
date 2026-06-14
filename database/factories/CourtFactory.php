<?php

namespace Database\Factories;

use App\Models\Court;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Court>
 */
class CourtFactory extends Factory
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
            'name' => 'Court '.fake()->numberBetween(1, 12),
            'sport' => fake()->randomElement(['Badminton', 'Futsal', 'Basketball', 'Tennis', 'Pickleball']),
            'is_active' => true,
        ];
    }
}
