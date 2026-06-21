<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Court;
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
            ->assertSee('Add your first venue'); // no venues yet → prompt to add one
    }

    public function test_connected_owner_with_an_unsubscribed_venue_is_still_warned(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Owner, 'connect_onboarded' => true]);
        Venue::factory()->for($owner, 'owner')->create(); // approved, but not subscribed
        $this->actingAs($owner);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Get your courts live')
            ->assertSee('Subscribe each venue'); // the still-incomplete step
    }

    public function test_owner_dashboard_shows_total_earnings_from_confirmed_bookings(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Owner]);
        $court = Court::factory()->for(Venue::factory()->for($owner, 'owner')->create())->create();

        Booking::factory()->for($court)->create(['status' => BookingStatus::Confirmed, 'price' => 40, 'booking_date' => '2026-07-01']);
        Booking::factory()->for($court)->create(['status' => BookingStatus::Confirmed, 'price' => 25, 'booking_date' => '2026-07-02']);
        Booking::factory()->for($court)->pending()->create(['price' => 99, 'booking_date' => '2026-07-03']); // not counted

        $this->actingAs($owner)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total earnings')
            ->assertSee('RM 65.00'); // 40 + 25, pending excluded
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
