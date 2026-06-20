<?php

namespace App\Concerns;

use App\Enums\BookingStatus;
use App\Services\BookingPaymentService;

trait PaysBookingGroups
{
    /** Pay (or resume payment) for every still-held slot in a group, in one checkout. */
    public function payGroup(array $ids, BookingPaymentService $payments)
    {
        $bookings = auth()->user()->bookings()
            ->whereIn('id', $ids)
            ->where('status', BookingStatus::Pending->value)
            ->where('hold_expires_at', '>', now())
            ->get();

        if ($bookings->isEmpty()) {
            session()->flash('booking_error', 'These holds have expired.');

            return null;
        }

        if (config('cashier.secret')) {
            return redirect()->away($payments->checkoutUrlForBookings(
                $bookings,
                route('bookings.cart.success'),
                route('bookings.cart.cancel', ['bookings' => $bookings->pluck('id')->implode(',')]),
            ));
        }

        foreach ($bookings as $booking) {
            $booking->update(['status' => BookingStatus::Confirmed, 'payment_status' => 'paid', 'processed_at' => now()]);
        }

        return redirect()->route('bookings.mine')->with('booking_confirmed', true);
    }
}
