<?php

namespace App\Livewire;

use App\Concerns\PaysBookingGroups;
use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.customer')]
#[Title('My Bookings')]
class MyBookings extends Component
{
    use PaysBookingGroups;

    /** all | confirmed | awaiting | cancelled */
    #[Url]
    public string $filter = 'all';

    public function render()
    {
        $bookings = auth()->user()->bookings()
            ->with('court.venue')
            ->when($this->filter === 'confirmed', fn ($q) => $q->where('status', BookingStatus::Confirmed->value))
            ->when($this->filter === 'awaiting', fn ($q) => $q->where('status', BookingStatus::Pending->value)
                ->where('hold_expires_at', '>', now()))
            ->when($this->filter === 'cancelled', fn ($q) => $q->where(function ($w) {
                $w->whereIn('status', [BookingStatus::Cancelled->value, BookingStatus::Expired->value])
                    ->orWhere(fn ($p) => $p->where('status', BookingStatus::Pending->value)
                        ->where('hold_expires_at', '<=', now()));
            }))
            ->orderBy('booking_group') // keep slots booked together adjacent…
            ->orderBy('court_id')
            ->orderBy('booking_date')
            ->orderBy('start_time')    // …in time order, so consecutive ones merge
            ->get();

        return view('livewire.my-bookings', ['groups' => $this->groupConsecutive($bookings)]);
    }

    /**
     * Merge back-to-back slots on the same court, date and status into one row,
     * so e.g. four 30-minute slots show as a single 10:00 AM – 12:00 PM block.
     *
     * @param  Collection<int, Booking>  $bookings  ordered by court, date, start time
     * @return array<int, array<string, mixed>>
     */
    private function groupConsecutive(Collection $bookings): array
    {
        $groups = [];
        $current = null;

        foreach ($bookings as $booking) {
            $status = $booking->displayStatus();

            // Only merge slots booked in the SAME action (same booking_group) that
            // are also back-to-back on the same court, date and status.
            $continues = $current
                && $current['group'] !== null
                && $current['group'] === $booking->booking_group
                && $current['court']->id === $booking->court_id
                && $current['date']->isSameDay($booking->booking_date)
                && $current['status'] === $status
                && substr((string) $current['end_time'], 0, 5) === substr((string) $booking->start_time, 0, 5);

            if ($continues) {
                $current['end_time'] = $booking->end_time;
                $current['price'] += (float) $booking->price;
                $current['count']++;
                $current['ids'][] = $booking->id;

                if ($booking->hold_expires_at && (! $current['hold_expires_at'] || $booking->hold_expires_at->lt($current['hold_expires_at']))) {
                    $current['hold_expires_at'] = $booking->hold_expires_at; // soonest expiry wins
                }

                continue;
            }

            if ($current) {
                $groups[] = $current;
            }

            $current = [
                'group' => $booking->booking_group,
                'court' => $booking->court,
                'date' => $booking->booking_date,
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
                'price' => (float) $booking->price,
                'count' => 1,
                'status' => $status,
                'ids' => [$booking->id],
                'hold_expires_at' => $booking->hold_expires_at,
                'booked_at' => $booking->created_at,
            ];
        }

        if ($current) {
            $groups[] = $current;
        }

        // Most recently booked first.
        usort($groups, fn ($a, $b) => $b['booked_at'] <=> $a['booked_at']);

        return $groups;
    }
}
