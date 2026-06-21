<?php

use App\Enums\UserRole;
use App\Livewire\Admin\VenueShow;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;
use Livewire\Livewire;

test('an admin can open a venue and see its information', function () {
    $venue = Venue::factory()->create([
        'name' => 'Sunway Arena',
        'city' => 'Subang Jaya',
        'description' => 'Eight feature courts with parking.',
        'policy' => 'No outside food allowed.',
    ]);
    Court::factory()->for($venue)->create(['name' => 'Court A', 'sport' => 'Badminton']);

    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->get(route('admin.venues.show', $venue))
        ->assertOk()
        ->assertSee('Sunway Arena')
        ->assertSee($venue->owner->email)        // owner details
        ->assertSee('Court A')                   // courts
        ->assertSee('Badminton')
        ->assertSee('Eight feature courts with parking.') // description
        ->assertSee('No outside food allowed.'); // policy
});

test('an admin can approve a venue from its detail page once fully verified', function () {
    $venue = Venue::factory()->pending()->verified()->create();
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    Livewire::actingAs($admin)
        ->test(VenueShow::class, ['venue' => $venue])
        ->assertSee('Pending approval')
        ->call('approve');

    expect($venue->fresh()->isApproved())->toBeTrue();
});

test('a venue cannot be approved until every verification item is ticked', function () {
    $venue = Venue::factory()->pending()->create(); // no items verified
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    Livewire::actingAs($admin)
        ->test(VenueShow::class, ['venue' => $venue])
        ->call('approve'); // gated — should do nothing

    expect($venue->fresh()->isApproved())->toBeFalse();

    // The owner uploads each document, then the admin ticks each item.
    foreach (Venue::verificationKeys() as $type) {
        $venue->documents()->create(['type' => $type, 'path' => "venue-documents/{$type}.pdf", 'original_name' => "{$type}.pdf"]);
    }

    Livewire::actingAs($admin)
        ->test(VenueShow::class, ['venue' => $venue])
        ->call('toggleVerified', 'ssm')
        ->call('toggleVerified', 'right_to_occupy')
        ->call('toggleVerified', 'council_licence')
        ->call('toggleVerified', 'address_proof')
        ->call('approve');

    expect($venue->fresh()->isApproved())->toBeTrue();
});

test('an admin cannot mark an item verified when the owner uploaded no document', function () {
    $venue = Venue::factory()->pending()->create(); // no documents
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    Livewire::actingAs($admin)
        ->test(VenueShow::class, ['venue' => $venue])
        ->call('toggleVerified', 'ssm');

    expect($venue->fresh()->isItemVerified('ssm'))->toBeFalse(); // blocked — nothing uploaded

    // Once the document exists, the same action verifies it.
    $venue->documents()->create(['type' => 'ssm', 'path' => 'venue-documents/ssm.pdf', 'original_name' => 'ssm.pdf']);

    Livewire::actingAs($admin)
        ->test(VenueShow::class, ['venue' => $venue])
        ->call('toggleVerified', 'ssm');

    expect($venue->fresh()->isItemVerified('ssm'))->toBeTrue();
});

test('the venue list links through to the detail page', function () {
    $venue = Venue::factory()->create(['name' => 'Linkable Venue']);
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->get(route('admin.venues'))
        ->assertOk()
        ->assertSee(route('admin.venues.show', $venue), escape: false);
});

test('a non-admin cannot open the admin venue detail', function () {
    $venue = Venue::factory()->create();
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    $this->actingAs($owner)->get(route('admin.venues.show', $venue))->assertForbidden();
});
