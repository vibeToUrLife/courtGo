<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_customers_are_redirected_from_the_dashboard_to_home(): void
    {
        $this->actingAs(User::factory()->create()); // customer by default

        $this->get(route('dashboard'))->assertRedirect(route('home'));
    }

    public function test_owner_dashboard_renders_and_warns_when_not_live(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Owner]);
        $this->actingAs($owner);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('My Venues')
            ->assertSee('subscribe each venue');
    }

    public function test_connected_owner_with_an_unsubscribed_venue_is_still_warned(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => true]);
        Venue::factory()->for($owner, 'owner')->create(); // approved, but not subscribed
        $this->actingAs($owner);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('subscribe each venue');
    }

    public function test_admin_dashboard_renders(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Admin Dashboard');
    }
}
