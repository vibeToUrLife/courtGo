<?php

use App\Enums\UserRole;
use App\Livewire\Owner\Courts\Schedule;
use App\Models\BlockedDate;
use App\Models\Court;
use App\Models\SessionTemplate;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

function makeOwnerCourt(): array
{
    $owner = User::factory()->create(['role' => UserRole::Owner]);
    $venue = Venue::factory()->for($owner, 'owner')->create();
    $court = Court::factory()->for($venue)->create();

    return [$owner, $court];
}

test('an owner can add a weekly session to their court', function () {
    [$owner, $court] = makeOwnerCourt();

    Livewire::actingAs($owner)
        ->test(Schedule::class, ['court' => $court])
        ->set('day_of_week', 1)
        ->set('start_time', '09:00')
        ->set('end_time', '11:00')
        ->set('price', 40)
        ->call('addSession')
        ->assertHasNoErrors();

    expect(SessionTemplate::where('court_id', $court->id)->count())->toBe(1);
});

test('the end time must be after the start time', function () {
    [$owner, $court] = makeOwnerCourt();

    Livewire::actingAs($owner)
        ->test(Schedule::class, ['court' => $court])
        ->set('day_of_week', 1)
        ->set('start_time', '11:00')
        ->set('end_time', '09:00')
        ->set('price', 40)
        ->call('addSession')
        ->assertHasErrors(['end_time']);
});

test('overlapping sessions on the same day are rejected', function () {
    [$owner, $court] = makeOwnerCourt();
    SessionTemplate::factory()->for($court)->create([
        'day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '11:00', 'is_active' => true,
    ]);

    Livewire::actingAs($owner)
        ->test(Schedule::class, ['court' => $court])
        ->set('day_of_week', 1)
        ->set('start_time', '10:00') // overlaps 09:00-11:00
        ->set('end_time', '12:00')
        ->set('price', 40)
        ->call('addSession')
        ->assertHasErrors(['start_time']);

    expect(SessionTemplate::where('court_id', $court->id)->count())->toBe(1);
});

test('adjacent sessions (touching but not overlapping) are allowed', function () {
    [$owner, $court] = makeOwnerCourt();
    SessionTemplate::factory()->for($court)->create([
        'day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '11:00', 'is_active' => true,
    ]);

    Livewire::actingAs($owner)
        ->test(Schedule::class, ['court' => $court])
        ->set('day_of_week', 1)
        ->set('start_time', '11:00') // starts exactly when the other ends
        ->set('end_time', '13:00')
        ->set('price', 40)
        ->call('addSession')
        ->assertHasNoErrors();

    expect(SessionTemplate::where('court_id', $court->id)->count())->toBe(2);
});

test('a session can end at midnight', function () {
    [$owner, $court] = makeOwnerCourt();

    Livewire::actingAs($owner)
        ->test(Schedule::class, ['court' => $court])
        ->set('day_of_week', 5)
        ->set('start_time', '20:00')
        ->set('end_time', '00:00') // midnight — treated as end-of-day
        ->set('price', 40)
        ->call('addSession')
        ->assertHasNoErrors();

    expect(SessionTemplate::where('court_id', $court->id)->count())->toBe(1);
});

test('a slot that runs past midnight is still rejected', function () {
    [$owner, $court] = makeOwnerCourt();

    Livewire::actingAs($owner)
        ->test(Schedule::class, ['court' => $court])
        ->set('day_of_week', 5)
        ->set('start_time', '23:00')
        ->set('end_time', '01:00') // crosses midnight into the next day
        ->set('price', 40)
        ->call('addSession')
        ->assertHasErrors(['end_time']);
});

test('a midnight-ending session still detects overlaps', function () {
    [$owner, $court] = makeOwnerCourt();
    SessionTemplate::factory()->for($court)->create([
        'day_of_week' => 5, 'start_time' => '20:00', 'end_time' => '00:00', 'is_active' => true,
    ]);

    Livewire::actingAs($owner)
        ->test(Schedule::class, ['court' => $court])
        ->set('day_of_week', 5)
        ->set('start_time', '22:00') // inside the 20:00–00:00 slot
        ->set('end_time', '23:00')
        ->set('price', 40)
        ->call('addSession')
        ->assertHasErrors(['start_time']);

    expect(SessionTemplate::where('court_id', $court->id)->count())->toBe(1);
});

test('an owner can delete a session', function () {
    [$owner, $court] = makeOwnerCourt();
    $session = SessionTemplate::factory()->for($court)->create();

    Livewire::actingAs($owner)
        ->test(Schedule::class, ['court' => $court])
        ->call('deleteSession', $session->id);

    expect(SessionTemplate::whereKey($session->id)->exists())->toBeFalse();
});

test('an owner can block a future date', function () {
    [$owner, $court] = makeOwnerCourt();

    Livewire::actingAs($owner)
        ->test(Schedule::class, ['court' => $court])
        ->set('block_date', Carbon::tomorrow()->toDateString())
        ->set('block_reason', 'Public holiday')
        ->call('blockDate')
        ->assertHasNoErrors();

    expect(BlockedDate::where('court_id', $court->id)->count())->toBe(1);
});

test('a past date cannot be blocked', function () {
    [$owner, $court] = makeOwnerCourt();

    Livewire::actingAs($owner)
        ->test(Schedule::class, ['court' => $court])
        ->set('block_date', Carbon::yesterday()->toDateString())
        ->call('blockDate')
        ->assertHasErrors(['block_date']);
});

test('an owner can unblock a date', function () {
    [$owner, $court] = makeOwnerCourt();
    $blocked = BlockedDate::factory()->for($court)->create();

    Livewire::actingAs($owner)
        ->test(Schedule::class, ['court' => $court])
        ->call('unblockDate', $blocked->id);

    expect(BlockedDate::whereKey($blocked->id)->exists())->toBeFalse();
});

test('a stranger cannot open another owners court schedule', function () {
    [$owner, $court] = makeOwnerCourt();
    $stranger = User::factory()->create(['role' => UserRole::Owner]);

    $this->actingAs($stranger)
        ->get(route('owner.courts.schedule', $court))
        ->assertForbidden();
});

test('the schedule page renders for the court owner', function () {
    [$owner, $court] = makeOwnerCourt();

    $this->actingAs($owner)
        ->get(route('owner.courts.schedule', $court))
        ->assertOk()
        ->assertSeeLivewire(Schedule::class);
});
