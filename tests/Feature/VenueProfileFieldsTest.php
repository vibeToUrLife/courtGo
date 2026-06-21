<?php

use App\Enums\UserRole;
use App\Livewire\Owner\Venues\Profile;
use App\Models\Court;
use App\Models\SessionTemplate;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

/** A live, bookable venue (approved venue + subscribed + Connect-onboarded owner). */
function profileLiveVenue(): Venue
{
    $owner = User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => true]);
    $owner->subscriptions()->create([
        'type' => 'default', 'stripe_id' => 'sub_'.uniqid(),
        'stripe_status' => 'active', 'stripe_price' => 'price_test', 'quantity' => 1,
    ]);

    return Venue::factory()->for($owner, 'owner')->create();
}

test('price range is the min and max of active, bookable slot prices', function () {
    $venue = profileLiveVenue();
    $court = Court::factory()->for($venue)->create(['is_active' => true]);
    SessionTemplate::factory()->for($court)->create(['price' => 30, 'is_active' => true]);
    SessionTemplate::factory()->for($court)->create(['price' => 50, 'is_active' => true]);
    SessionTemplate::factory()->for($court)->create(['price' => 999, 'is_active' => false]); // ignored

    expect($venue->priceRange())->toBe(['min' => 30.0, 'max' => 50.0]);
});

test('price range is null when the venue has no priced slots', function () {
    expect(Venue::factory()->create()->priceRange())->toBeNull();
});

test('price range is null for a venue whose owner is not live (matches the empty grid)', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]); // not subscribed → not bookable
    $venue = Venue::factory()->for($owner, 'owner')->create();
    $court = Court::factory()->for($venue)->create(['is_active' => true]);
    SessionTemplate::factory()->for($court)->create(['price' => 30, 'is_active' => true]);

    expect($venue->priceRange())->toBeNull();
});

test('an announcement is visible only when active, present and not expired', function () {
    expect(Venue::factory()->create(['announcement' => 'Hi', 'announcement_active' => true])->announcementVisible())->toBeTrue()
        ->and(Venue::factory()->create(['announcement' => 'Hi', 'announcement_active' => false])->announcementVisible())->toBeFalse()
        ->and(Venue::factory()->create(['announcement' => null, 'announcement_active' => true])->announcementVisible())->toBeFalse();

    Carbon::setTestNow('2026-07-01');
    expect(Venue::factory()->create(['announcement' => 'Hi', 'announcement_active' => true, 'announcement_until' => '2026-06-30'])->announcementVisible())->toBeFalse()
        ->and(Venue::factory()->create(['announcement' => 'Hi', 'announcement_active' => true, 'announcement_until' => '2026-07-01'])->announcementVisible())->toBeTrue();
    Carbon::setTestNow();
});

test('an owner can save venue details', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Profile::class, ['venue' => $venue])
        ->set('pricingNote', 'Peak RM45')
        ->set('policy', 'No outside food')
        ->set('contactEmail', 'hi@venue.test')
        ->set('contactWhatsapp', '60123456789')
        ->set('openingHours.1.closed', false)
        ->set('openingHours.1.open', '08:00')
        ->set('openingHours.1.close', '22:00')
        ->call('saveInfo')
        ->assertHasNoErrors();

    $venue->refresh();
    expect($venue->pricing_note)->toBe('Peak RM45')
        ->and($venue->policy)->toBe('No outside food')
        ->and($venue->contact_email)->toBe('hi@venue.test')
        ->and($venue->opening_hours[1]['open'])->toBe('08:00');
});

test('saving details rejects a bad email and a backwards opening time', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Profile::class, ['venue' => $venue])
        ->set('contactEmail', 'not-an-email')
        ->call('saveInfo')
        ->assertHasErrors('contactEmail')
        ->assertDispatched('profile-error'); // tells the page to jump to the error

    Livewire::actingAs($owner)
        ->test(Profile::class, ['venue' => $venue])
        ->set('openingHours.1.closed', false)
        ->set('openingHours.1.open', '22:00')
        ->set('openingHours.1.close', '08:00')
        ->call('saveInfo')
        ->assertHasErrors('openingHours.1.close')
        ->assertDispatched('profile-error');
});

test('an owner can bulk-apply the same opening hours to every day', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Profile::class, ['venue' => $venue])
        ->set('bulkOpen', '09:00')
        ->set('bulkClose', '23:00')
        ->call('applyHoursToAll')
        ->call('saveInfo')
        ->assertHasNoErrors();

    $hours = $venue->fresh()->opening_hours;
    expect($hours[1]['open'])->toBe('09:00')          // Monday
        ->and($hours[1]['close'])->toBe('23:00')
        ->and($hours[1]['closed'])->toBeFalse()
        ->and($hours[0]['open'])->toBe('09:00');       // Sunday too
});

test('an owner can mark every day closed at once', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Profile::class, ['venue' => $venue])
        ->call('closeAllDays')
        ->call('saveInfo')
        ->assertHasNoErrors();

    expect($venue->fresh()->opening_hours[1]['closed'])->toBeTrue();
});

test('a half-filled opening day (only one time) is rejected', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Profile::class, ['venue' => $venue])
        ->set('openingHours.1.closed', false)
        ->set('openingHours.1.open', '08:00')
        ->set('openingHours.1.close', '') // only one time set
        ->call('saveInfo')
        ->assertHasErrors('openingHours.1.close')
        ->assertDispatched('profile-error');
});

test('details still save when the stored announcement date is already in the past', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create(['announcement_until' => '2020-01-01']);

    Livewire::actingAs($owner)
        ->test(Profile::class, ['venue' => $venue])
        ->set('pricingNote', 'Updated')
        ->call('saveInfo')
        ->assertHasNoErrors();

    expect($venue->fresh()->pricing_note)->toBe('Updated');
});
