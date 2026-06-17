<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomeTest extends TestCase
{
    use RefreshDatabase;

    /** Create one bookable court (live owner) for the given sport. */
    private function liveCourt(string $sport): void
    {
        $owner = User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => true]);
        $owner->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_'.uniqid(),
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'quantity' => 1,
        ]);
        $venue = Venue::factory()->for($owner, 'owner')->create();
        Court::factory()->for($venue)->create(['is_active' => true, 'sport' => $sport]);
    }

    public function test_landing_page_shows_courtgo_branding_to_guests(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Book your next game in seconds.')
            ->assertSee('For court owners')
            ->assertSee('Get started')
            ->assertDontSee('Laravel has an incredibly rich ecosystem');
    }

    public function test_landing_page_has_a_search_bar_and_sport_tiles(): void
    {
        $this->liveCourt('Badminton');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Find a court')    // the search button
            ->assertSee('Browse by sport') // the tiles heading
            ->assertSee('Badminton')       // a real, bookable sport tile
            ->assertSee('for-business');   // link to the owner marketing page
    }

    public function test_sport_tiles_are_hidden_when_no_sports_are_bookable(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Browse by sport');
    }

    public function test_landing_page_shows_dashboard_link_to_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Go to dashboard');
    }
}
