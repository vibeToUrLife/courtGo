<?php

use App\Models\Court;
use App\Models\Venue;

test('a court belongs to a venue', function () {
    $venue = Venue::factory()->create();
    $court = Court::factory()->for($venue)->create();

    expect($court->venue->id)->toBe($venue->id);
});

test('a venue has many courts', function () {
    $venue = Venue::factory()->create();
    Court::factory()->count(3)->for($venue)->create();

    expect($venue->courts)->toHaveCount(3);
});

test('is_active is cast to a boolean', function () {
    $court = Court::factory()->create(['is_active' => 1]);

    expect($court->is_active)->toBeTrue();
});
