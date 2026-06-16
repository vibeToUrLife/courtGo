<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_shows_courtgo_branding_to_guests(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Book your next game in seconds.')
            ->assertSee('For court owners')
            ->assertSee('Get started')
            ->assertDontSee('Laravel has an incredibly rich ecosystem');
    }

    public function test_landing_page_shows_dashboard_link_to_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Go to dashboard');
    }
}
