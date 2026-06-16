<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Exceptions\SlotUnavailableException;
use App\Models\Booking;
use App\Models\SessionTemplate;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function __construct(private AvailabilityService $availability)
    {
    }

    /**
     * Reserve a session by creating a short-lived "pending" hold.
     * The customer then pays; the booking is confirmed by the payment webhook.
     *
     * @throws SlotUnavailableException
     */
    public function reserve(User $customer, SessionTemplate $session, Carbon $date): Booking
    {
        $date = $date->copy()->startOfDay();
        $court = $session->court;

        if (! $court->isBookable()) {
            throw new SlotUnavailableException('This court is not open for booking yet.');
        }

        // The chosen date must be one where this session is actually available.
        if (! $this->availability->availableSessions($court, $date)->contains('id', $session->id)) {
            throw new SlotUnavailableException('That session is not available on the chosen date.');
        }

        try {
            return DB::transaction(function () use ($customer, $session, $court, $date) {
                // Serialise concurrent reservers for this exact slot.
                $court->bookings()
                    ->whereDate('booking_date', $date->toDateString())
                    ->where('start_time', $session->start_time)
                    ->lockForUpdate()
                    ->get();

                // Free any expired-but-not-yet-swept hold on this exact slot so it can be re-booked
                // (the every-minute sweep may not have run yet). This clears it from the unique index.
                $court->bookings()
                    ->whereDate('booking_date', $date->toDateString())
                    ->where('start_time', $session->start_time)
                    ->where('status', BookingStatus::Pending->value)
                    ->where('hold_expires_at', '<=', now())
                    ->update(['status' => BookingStatus::Expired->value]);

                return Booking::create([
                    'customer_id' => $customer->id,
                    'court_id' => $court->id,
                    'session_template_id' => $session->id,
                    'booking_date' => $date->toDateString(),
                    'start_time' => $session->start_time,
                    'end_time' => $session->end_time,
                    'price' => $session->price,
                    'status' => BookingStatus::Pending,
                    'payment_status' => 'unpaid',
                    'hold_expires_at' => Carbon::now()->addMinutes(10),
                ]);
            });
        } catch (UniqueConstraintViolationException $e) {
            // The unique index caught a race — someone confirmed/held it first.
            throw new SlotUnavailableException('Someone just booked this slot. Please pick another.');
        }
    }
}
