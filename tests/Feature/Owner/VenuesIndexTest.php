<?php

use App\Enums\UserRole;
use App\Livewire\Owner\Venues\Index;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('the add-venue form is collapsed until the Add venue button is used', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    Livewire::actingAs($owner)->test(Index::class)
        ->assertSee('Your venues')         // list shown by default
        ->assertDontSee('Add a new venue') // form hidden
        ->call('toggleForm')
        ->assertSee('Add a new venue')     // form shown
        ->assertDontSee('Your venues');    // list hidden while adding
});

test('deleting a venue removes its image file', function () {
    Storage::fake('public');
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create([
        'image_path' => UploadedFile::fake()->image('v.jpg')->store('venues', 'public'),
    ]);
    Storage::disk('public')->assertExists($venue->image_path);

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->call('delete', $venue->id)
        ->assertHasNoErrors();

    Storage::disk('public')->assertMissing($venue->image_path);
});

test('a venue state must be one of the curated states', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->set('name', 'Nowhere Hall')
        ->set('address', 'Jalan X')
        ->set('city', 'Atlantis')
        ->set('state', 'Atlantis') // not a Malaysian state
        ->call('save')
        ->assertHasErrors(['state']);
});

test('an owner can create a venue', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->set('name', 'Sunway Badminton Hall')
        ->set('address', 'Jalan PJS 11')
        ->set('city', 'Subang Jaya')
        ->set('state', 'Selangor')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Sunway Badminton Hall');

    expect(Venue::where('name', 'Sunway Badminton Hall')->where('owner_id', $owner->id)->exists())->toBeTrue();
});

test('the venue name and address are required', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->set('name', '')
        ->set('address', '')
        ->call('save')
        ->assertHasErrors(['name', 'address']);
});

test('an owner only sees their own venues', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $other = User::factory()->create(['role' => UserRole::Owner]);
    Venue::factory()->for($owner, 'owner')->create(['name' => 'My Own Hall']);
    Venue::factory()->for($other, 'owner')->create(['name' => 'Someone Elses Hall']);

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->assertSee('My Own Hall')
        ->assertDontSee('Someone Elses Hall');
});

test('an owner can delete their own venue', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->call('delete', $venue->id)
        ->assertHasNoErrors();

    expect(Venue::whereKey($venue->id)->exists())->toBeFalse();
});

test('an owner cannot delete another owners venue', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->create(); // a different owner's venue

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->call('delete', $venue->id)
        ->assertForbidden();

    expect(Venue::whereKey($venue->id)->exists())->toBeTrue();
});

test('the venues page renders for an owner', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);

    $this->actingAs($owner)->get('/owner/venues')
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

test('a customer cannot open the venues page', function () {
    $customer = User::factory()->create(); // defaults to customer

    $this->actingAs($customer)->get('/owner/venues')->assertForbidden();
});

test('the venues list links to each venue profile', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    $this->actingAs($owner)->get(route('owner.venues.index'))
        ->assertOk()
        ->assertSee(route('owner.venues.profile', $venue), escape: false);
});
