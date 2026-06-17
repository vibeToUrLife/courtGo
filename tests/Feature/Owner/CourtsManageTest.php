<?php

use App\Enums\UserRole;
use App\Livewire\Owner\Venues\Courts;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;
use Livewire\Livewire;

test('the wizard creates numbered courts that share one schedule', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('sport', 'Badminton')
        ->set('count', 3)
        ->set('namingStyle', 'number')
        ->set('prefix', 'Court')
        ->call('toStep2')
        ->assertHasNoErrors()
        ->set('scheduleMode', 'same')
        ->call('toStep3')
        ->set('sessions.0.from_day', 1) // Monday only
        ->set('sessions.0.to_day', 1)
        ->set('sessions.0.start_time', '20:00')
        ->set('sessions.0.end_time', '22:00')
        ->set('sessions.0.price', 40)
        ->call('create')
        ->assertHasNoErrors();

    expect($venue->courts()->pluck('name')->sort()->values()->all())
        ->toBe(['Court 1', 'Court 2', 'Court 3']);

    // Every court got the shared one-day slot.
    expect($venue->courts->every(fn ($court) => $court->sessionTemplates()->count() === 1))->toBeTrue();
});

test('a day range creates one slot per day', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('sport', 'Badminton')
        ->set('count', 1)
        ->set('namingStyle', 'number')
        ->call('toStep2')
        ->set('scheduleMode', 'same')
        ->call('toStep3')
        ->set('sessions.0.from_day', 1) // Mon
        ->set('sessions.0.to_day', 5)   // Fri → 5 days
        ->set('sessions.0.start_time', '20:00')
        ->set('sessions.0.end_time', '22:00')
        ->set('sessions.0.price', 40)
        ->call('create')
        ->assertHasNoErrors();

    expect($venue->courts()->first()->sessionTemplates()->count())->toBe(5);
});

test('the wizard creates lettered courts with different schedules', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('sport', 'Futsal')
        ->set('count', 2)
        ->set('namingStyle', 'letter')
        ->set('prefix', 'Court')
        ->call('toStep2')
        ->assertHasNoErrors()
        ->set('scheduleMode', 'different')
        ->call('toStep3')
        ->set('courtSessions.0.0.from_day', 1)
        ->set('courtSessions.0.0.to_day', 1)
        ->set('courtSessions.0.0.start_time', '18:00')
        ->set('courtSessions.0.0.end_time', '19:00')
        ->set('courtSessions.0.0.price', 30)
        ->set('courtSessions.1.0.from_day', 2)
        ->set('courtSessions.1.0.to_day', 2)
        ->set('courtSessions.1.0.start_time', '09:00')
        ->set('courtSessions.1.0.end_time', '10:00')
        ->set('courtSessions.1.0.price', 50)
        ->call('create')
        ->assertHasNoErrors();

    expect($venue->courts()->pluck('name')->sort()->values()->all())->toBe(['Court A', 'Court B']);

    $a = $venue->courts()->where('name', 'Court A')->first();
    expect($a->sessionTemplates()->count())->toBe(1)
        ->and((int) $a->sessionTemplates()->first()->day_of_week)->toBe(1);
});

test('going back and forward keeps per-court schedule edits', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('sport', 'Tennis')
        ->set('count', 2)
        ->set('namingStyle', 'letter')
        ->call('toStep2')
        ->set('scheduleMode', 'different')
        ->call('toStep3')
        ->set('courtSessions.0.0.start_time', '18:00')
        ->call('back')        // back to step 2
        ->call('toStep3')     // re-enter step 3
        ->assertSet('courtSessions.0.0.start_time', '18:00'); // edit preserved
});

test('the wizard accepts an Other custom sport', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('sport', 'Other')
        ->set('customSport', 'Frisbee')
        ->set('count', 1)
        ->set('namingStyle', 'number')
        ->call('toStep2')
        ->assertHasNoErrors()
        ->set('scheduleMode', 'same')
        ->call('toStep3')
        ->call('create')
        ->assertHasNoErrors();

    expect($venue->courts()->first()->sport)->toBe('Frisbee');
});

test('the wizard rejects a sport outside the curated list', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('sport', 'Quidditch') // not in config('courtgo.sports') and not "Other"
        ->set('count', 1)
        ->call('toStep2')
        ->assertHasErrors(['sport']);
});

test('an Other sport cannot be only whitespace', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('sport', 'Other')
        ->set('customSport', '   ')
        ->set('count', 1)
        ->call('toStep2')
        ->assertHasErrors(['customSport']);
});

test('choosing Other requires a custom sport name', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('sport', 'Other')
        ->set('customSport', '')
        ->set('count', 1)
        ->call('toStep2')
        ->assertHasErrors(['customSport']);
});

test('the wizard requires at least one slot per court', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('sport', 'Badminton')
        ->set('count', 1)
        ->set('namingStyle', 'number')
        ->call('toStep2')
        ->set('scheduleMode', 'same')
        ->call('toStep3')
        ->call('removeSession', 0) // delete the only slot
        ->call('create')
        ->assertHasErrors(['sessions']);

    expect($venue->courts()->count())->toBe(0);
});

test('the wizard requires a sport and at least one court', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('sport', '')
        ->set('count', 0)
        ->call('toStep2')
        ->assertHasErrors(['sport', 'count']);
});

test('lettered naming is capped at 26 courts', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('sport', 'Tennis')
        ->set('count', 27)
        ->set('namingStyle', 'letter')
        ->call('toStep2')
        ->assertHasErrors(['count']);
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
