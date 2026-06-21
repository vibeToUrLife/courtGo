<?php

use App\Livewire\Feedback;
use App\Models\Feedback as FeedbackModel;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

test('the help center renders with troubleshooting for players and owners', function () {
    $this->get(route('help'))
        ->assertOk()
        ->assertSee('For players')
        ->assertSee('For venue owners')
        ->assertSee("Why aren't my courts live"); // an owner troubleshooting entry
});

test('the feedback page renders', function () {
    $this->get(route('feedback'))->assertOk()->assertSee('Send us feedback');
});

test('a guest can submit feedback and it is stored', function () {
    Mail::fake();

    Livewire::test(Feedback::class)
        ->set('category', 'bug')
        ->set('email', 'player@example.com')
        ->set('message', 'The booking grid is hard to read on my phone.')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('sent', true);

    $fb = FeedbackModel::first();
    expect($fb)->not->toBeNull()
        ->and($fb->category)->toBe('bug')
        ->and($fb->email)->toBe('player@example.com')
        ->and($fb->user_id)->toBeNull();
});

test('feedback requires a long-enough message', function () {
    Livewire::test(Feedback::class)
        ->set('email', 'x@example.com')
        ->set('message', 'hi')
        ->call('submit')
        ->assertHasErrors('message');

    expect(FeedbackModel::count())->toBe(0);
});

test('a logged-in user submitting feedback records their id', function () {
    Mail::fake();
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(Feedback::class)
        ->set('message', 'Love the app, would like a dark map view.')
        ->call('submit')
        ->assertHasNoErrors();

    expect(FeedbackModel::first()->user_id)->toBe($user->id);
});

test('the footer links to the help center and feedback', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Help center')
        ->assertSee('Send feedback');
});
