<?php

use App\Enums\UserRole;
use App\Livewire\Owner\Venues\Courts;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;
use Livewire\Livewire;

test('an owner can add a court to their venue', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->set('name', 'Court 1')
        ->set('sport', 'Badminton')
        ->set('is_active', true)
        ->call('addCourt')
        ->assertHasNoErrors()
        ->assertSee('Court 1');

    expect(Court::where('venue_id', $venue->id)->where('name', 'Court 1')->exists())->toBeTrue();
});

test('court name and sport are required', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->set('name', '')
        ->set('sport', '')
        ->call('addCourt')
        ->assertHasErrors(['name', 'sport']);
});

test('an owner can delete a court', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();
    $court = Court::factory()->for($venue)->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('deleteCourt', $court->id)
        ->assertHasNoErrors();

    expect(Court::whereKey($court->id)->exists())->toBeFalse();
});

test('an owner can toggle a court active status', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();
    $court = Court::factory()->for($venue)->create(['is_active' => true]);

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('toggleActive', $court->id);

    expect($court->fresh()->is_active)->toBeFalse();
});

test('an owner cannot manage courts of another owners venue', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();
    $stranger = User::factory()->create(['role' => UserRole::Owner]);

    $this->actingAs($stranger)
        ->get(route('owner.venues.courts', $venue))
        ->assertForbidden();
});

test('the courts page renders for the venue owner', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    $this->actingAs($owner)
        ->get(route('owner.venues.courts', $venue))
        ->assertOk()
        ->assertSeeLivewire(Courts::class);
});
