<?php

use App\Enums\UserRole;
use App\Livewire\Owner\Venues\Courts;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueClosedDate;
use Illuminate\Support\Carbon;
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
        ->set('sessions.0.days', [1]) // Monday only
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

test('the wizard accepts a slot that ends at midnight', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('sport', 'Badminton')
        ->set('count', 1)
        ->call('toStep2')
        ->set('scheduleMode', 'same')
        ->call('toStep3')
        ->set('sessions.0.days', [5])
        ->set('sessions.0.start_time', '20:00')
        ->set('sessions.0.end_time', '00:00') // midnight
        ->set('sessions.0.hours', 2)          // 8pm–midnight ÷ 2h = two slots
        ->set('sessions.0.price', 40)
        ->call('create')
        ->assertHasNoErrors();

    expect($venue->courts()->first()->sessionTemplates()->count())->toBe(2);
});

test('the wizard splits each row into slots for every day in the range', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('sport', 'Badminton')
        ->set('count', 1)
        ->call('toStep2')
        ->set('scheduleMode', 'same')
        ->call('toStep3')
        ->set('sessions.0.days', [1, 2, 3, 4, 5]) // Mon–Fri
        ->set('sessions.0.start_time', '18:00')
        ->set('sessions.0.end_time', '22:00')
        ->set('sessions.0.hours', 2)    // 4-hour window ÷ 2h = 2 slots/day
        ->set('sessions.0.price', 40)
        ->call('create')
        ->assertHasNoErrors();

    $court = $venue->courts()->first();
    expect($court->sessionTemplates()->count())->toBe(10); // 2 slots × 5 days

    $monday = $court->sessionTemplates()->where('day_of_week', 1)->orderBy('start_time')->get()
        ->map(fn ($s) => substr((string) $s->start_time, 0, 5).'-'.substr((string) $s->end_time, 0, 5))->all();
    expect($monday)->toBe(['18:00-20:00', '20:00-22:00']);
});

test('the wizard supports 30-minute slots', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('sport', 'Badminton')
        ->set('count', 1)
        ->call('toStep2')
        ->set('scheduleMode', 'same')
        ->call('toStep3')
        ->set('sessions.0.days', [1])
        ->set('sessions.0.start_time', '20:00')
        ->set('sessions.0.end_time', '21:00')
        ->set('sessions.0.hours', '0.5') // string, as the dropdown supplies
        ->set('sessions.0.price', 40)
        ->call('create')
        ->assertHasNoErrors();

    $slots = $venue->courts()->first()->sessionTemplates()->orderBy('start_time')->get()
        ->map(fn ($s) => substr((string) $s->start_time, 0, 5).'-'.substr((string) $s->end_time, 0, 5))->all();
    expect($slots)->toBe(['20:00-20:30', '20:30-21:00']);
});

test('the wizard rejects overlapping slots from different rows on a shared day', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('sport', 'Badminton')
        ->set('count', 1)
        ->call('toStep2')
        ->set('scheduleMode', 'same')
        ->call('toStep3')
        ->call('addSession') // a second row
        ->set('sessions.0.days', [1, 2, 3]) // Mon–Wed
        ->set('sessions.0.start_time', '18:00')->set('sessions.0.end_time', '20:00')
        ->set('sessions.0.hours', 2)->set('sessions.0.price', 40)
        ->set('sessions.1.days', [2, 3, 4]) // Tue–Thu
        ->set('sessions.1.start_time', '19:00')->set('sessions.1.end_time', '21:00') // overlaps row 0 on Tue/Wed
        ->set('sessions.1.hours', 2)->set('sessions.1.price', 40)
        ->call('create')
        ->assertHasErrors();

    expect($venue->courts()->count())->toBe(0);
});

test('the wizard creates slots only on the ticked days', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('sport', 'Badminton')
        ->set('count', 1)
        ->call('toStep2')
        ->set('scheduleMode', 'same')
        ->call('toStep3')
        ->set('sessions.0.days', [1, 3, 5]) // Mon, Wed, Fri — not a contiguous range
        ->set('sessions.0.start_time', '20:00')
        ->set('sessions.0.end_time', '22:00')
        ->set('sessions.0.hours', 2)
        ->set('sessions.0.price', 40)
        ->call('create')
        ->assertHasNoErrors();

    $days = $venue->courts()->first()->sessionTemplates()->pluck('day_of_week')
        ->map(fn ($d) => (int) $d)->sort()->values()->all();
    expect($days)->toBe([1, 3, 5]);
});

test('the wizard requires at least one day per slot row', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('sport', 'Badminton')
        ->set('count', 1)
        ->call('toStep2')
        ->set('scheduleMode', 'same')
        ->call('toStep3')
        ->set('sessions.0.days', []) // no days ticked
        ->set('sessions.0.start_time', '20:00')
        ->set('sessions.0.end_time', '22:00')
        ->set('sessions.0.hours', 2)
        ->set('sessions.0.price', 40)
        ->call('create')
        ->assertHasErrors('sessions.0.days');

    expect($venue->courts()->count())->toBe(0);
});

test('the wizard rejects a window that does not divide evenly into slots', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('sport', 'Badminton')
        ->set('count', 1)
        ->call('toStep2')
        ->set('scheduleMode', 'same')
        ->call('toStep3')
        ->set('sessions.0.days', [1])
        ->set('sessions.0.start_time', '20:00')
        ->set('sessions.0.end_time', '23:00') // 3 hours
        ->set('sessions.0.hours', 2)          // doesn't fit
        ->set('sessions.0.price', 40)
        ->call('create')
        ->assertHasErrors();

    expect($venue->courts()->count())->toBe(0);
});

test('clearing the court count no longer crashes and defaults to one court', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('sport', 'Badminton')
        ->set('count', '')         // emptying the field no longer throws PropertyNotFoundException
        ->assertSet('count', null) // it's left blank (so it doesn't fight live typing)
        ->call('toStep2')
        ->assertHasNoErrors()      // and defaults to one court on submit
        ->assertSet('step', 2);

    // Real out-of-range numbers are clamped into 1–50 as they're typed.
    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('count', 999)
        ->assertSet('count', 50)
        ->set('count', 0)
        ->assertSet('count', 1);
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
        ->set('sessions.0.days', [1, 2, 3, 4, 5]) // Mon–Fri
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
        ->set('courtSessions.0.0.days', [1])
        ->set('courtSessions.0.0.start_time', '18:00')
        ->set('courtSessions.0.0.end_time', '19:00')
        ->set('courtSessions.0.0.hours', 1)
        ->set('courtSessions.0.0.price', 30)
        ->set('courtSessions.1.0.days', [2])
        ->set('courtSessions.1.0.start_time', '09:00')
        ->set('courtSessions.1.0.end_time', '10:00')
        ->set('courtSessions.1.0.hours', 1)
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

test('the wizard requires a sport, and clamps the court count to at least one', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('startWizard')
        ->set('sport', '')
        ->set('count', 0)          // below the minimum…
        ->assertSet('count', 1)    // …is clamped to 1 court rather than erroring
        ->call('toStep2')
        ->assertHasErrors('sport') // but a sport is still required to proceed
        ->assertSet('step', 1);
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

test('an owner can add courts to a venue that is still pending approval', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->pending()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->assertSee('Pending admin approval') // informational banner, not a gate
        ->call('startWizard')                 // creating courts is NOT blocked
        ->set('sport', 'Badminton')
        ->set('count', 1)
        ->call('toStep2')
        ->set('scheduleMode', 'same')
        ->call('toStep3')
        ->set('sessions.0.days', [1])
        ->set('sessions.0.start_time', '20:00')
        ->set('sessions.0.end_time', '22:00')
        ->set('sessions.0.hours', 2)
        ->set('sessions.0.price', 40)
        ->call('create')
        ->assertHasNoErrors();

    expect($venue->courts()->count())->toBe(1);
});

test('an owner can close the whole venue on a future date', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->set('closed_date', Carbon::tomorrow()->toDateString())
        ->set('closed_reason', 'Public holiday')
        ->call('addClosedDate')
        ->assertHasNoErrors();

    expect($venue->closedDates()->whereDate('date', Carbon::tomorrow()->toDateString())->count())->toBe(1);
});

test('a past date cannot be closed', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->set('closed_date', Carbon::yesterday()->toDateString())
        ->call('addClosedDate')
        ->assertHasErrors(['closed_date']);

    expect($venue->closedDates()->count())->toBe(0);
});

test('closing the same date twice does not duplicate it', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();
    $date = Carbon::tomorrow()->toDateString();

    $component = Livewire::actingAs($owner)->test(Courts::class, ['venue' => $venue]);
    $component->set('closed_date', $date)->call('addClosedDate');
    $component->set('closed_date', $date)->set('closed_reason', 'Maintenance')->call('addClosedDate');

    expect($venue->closedDates()->whereDate('date', $date)->count())->toBe(1)
        ->and($venue->closedDates()->whereDate('date', $date)->first()->reason)->toBe('Maintenance');
});

test('an owner can reopen a closed date', function () {
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();
    $closed = VenueClosedDate::factory()->for($venue)->create();

    Livewire::actingAs($owner)
        ->test(Courts::class, ['venue' => $venue])
        ->call('removeClosedDate', $closed->id);

    expect(VenueClosedDate::whereKey($closed->id)->exists())->toBeFalse();
});
