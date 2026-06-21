<?php

namespace App\Livewire\Admin;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;
use Laravel\Cashier\Subscription;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Admin Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.admin.dashboard', [
            'ownerCount' => User::where('role', UserRole::Owner->value)->count(),
            'customerCount' => User::where('role', UserRole::Customer->value)->count(),
            'venueCount' => Venue::count(),
            'courtCount' => Court::count(),
            'confirmedBookings' => Booking::where('status', BookingStatus::Confirmed->value)->count(),
            // Subscriptions are per venue (typed "venue:{id}").
            'activeSubscriptions' => Subscription::where('type', 'like', 'venue:%')
                ->whereIn('stripe_status', ['active', 'trialing'])->count(),
        ]);
    }
}
