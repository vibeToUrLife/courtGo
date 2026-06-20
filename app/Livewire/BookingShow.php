<?php

namespace App\Livewire;

use App\Concerns\PaysBookingGroups;
use App\Models\Booking;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.customer')]
#[Title('Booking details')]
class BookingShow extends Component
{
    use PaysBookingGroups;

    public Booking $booking;

    public function mount(Booking $booking): void
    {
        abort_unless($booking->customer_id === auth()->id(), 403);

        $this->booking = $booking;
    }

    public function render()
    {
        // The block of slots this booking belongs to: everything booked together
        // (same booking_group) on the same court and date, in time order.
        $slots = Booking::query()
            ->where('customer_id', auth()->id())
            ->where('court_id', $this->booking->court_id)
            ->whereDate('booking_date', $this->booking->booking_date->toDateString())
            ->when(
                $this->booking->booking_group,
                fn ($q) => $q->where('booking_group', $this->booking->booking_group),
                fn ($q) => $q->whereKey($this->booking->id),
            )
            ->orderBy('start_time')
            ->with('court.venue')
            ->get();

        $first = $slots->first();
        $last = $slots->last();

        return view('livewire.booking-show', [
            'venue' => $first->court->venue,
            'court' => $first->court,
            'date' => $first->booking_date,
            'startTime' => $first->start_time,
            'endTime' => $last->end_time,
            'bookingSlots' => $slots,
            'total' => (float) $slots->sum('price'),
            'status' => $first->displayStatus(),
            'holdExpiresAt' => $first->awaitingPayment() ? $first->hold_expires_at : null,
            'ids' => $slots->pluck('id')->all(),
        ]);
    }
}
