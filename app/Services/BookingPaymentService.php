<?php

namespace App\Services;

use App\Models\Booking;
use Laravel\Cashier\Cashier;

/**
 * Creates the Stripe Checkout session for a booking — a DESTINATION CHARGE that
 * sends the money to the court owner's connected account (0% platform fee, so
 * application_fee_amount is omitted). Calls Stripe, so it's exercised manually.
 */
class BookingPaymentService
{
    public function checkoutUrl(Booking $booking, string $successUrl, string $cancelUrl): string
    {
        return $this->checkoutUrlForBookings([$booking], $successUrl, $cancelUrl);
    }

    /**
     * One Stripe Checkout session paying for several bookings at once — one line
     * item per slot, a single destination charge to the venue owner. All bookings
     * must belong to the same venue/owner (they always do: one venue page).
     *
     * @param  iterable<int, Booking>  $bookings
     */
    public function checkoutUrlForBookings(iterable $bookings, string $successUrl, string $cancelUrl): string
    {
        $bookings = collect($bookings)->values();
        $bookings->each->loadMissing('court.venue.owner');
        $ownerAccountId = $bookings->first()->court->venue->owner->stripe_connect_account_id;
        $ids = $bookings->pluck('id')->implode(',');

        $lineItems = $bookings->map(fn (Booking $booking) => [
            'price_data' => [
                'currency' => 'myr',
                'unit_amount' => (int) round(((float) $booking->price) * 100), // sen
                'product_data' => [
                    'name' => $booking->court->venue->name.' — '.$booking->court->name,
                    'description' => $booking->booking_date->format('D, d M Y')
                        .' '.substr((string) $booking->start_time, 0, 5)
                        .'–'.substr((string) $booking->end_time, 0, 5),
                ],
            ],
            'quantity' => 1,
        ])->all();

        $session = Cashier::stripe()->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => $lineItems,
            'payment_intent_data' => [
                // Destination charge to the owner; 0% platform fee → no application_fee_amount.
                'transfer_data' => ['destination' => $ownerAccountId],
                'metadata' => ['booking_ids' => $ids],
            ],
            'metadata' => ['booking_ids' => $ids],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        $bookings->each(fn (Booking $booking) => $booking->update(['stripe_checkout_session_id' => $session->id]));

        return $session->url;
    }
}
