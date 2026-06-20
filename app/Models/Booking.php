<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    /** @use HasFactory<\Database\Factories\BookingFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'court_id',
        'session_template_id',
        'booking_group',
        'booking_date',
        'start_time',
        'end_time',
        'price',
        'status',
        'payment_status',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'hold_expires_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'price' => 'decimal:2',
            'status' => BookingStatus::class,
            'hold_expires_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function sessionTemplate(): BelongsTo
    {
        return $this->belongsTo(SessionTemplate::class);
    }

    /** Pending and the payment hold is still valid — customer can still pay. */
    public function awaitingPayment(): bool
    {
        return $this->status === BookingStatus::Pending
            && $this->hold_expires_at
            && $this->hold_expires_at->isFuture();
    }

    /** Pending but the hold window has passed — effectively expired. */
    public function holdExpired(): bool
    {
        return $this->status === BookingStatus::Pending
            && $this->hold_expires_at
            && $this->hold_expires_at->isPast();
    }

    /** Which display bucket this booking falls in: confirmed | awaiting | expired | cancelled. */
    public function displayStatus(): string
    {
        return match (true) {
            $this->status === BookingStatus::Confirmed => 'confirmed',
            $this->awaitingPayment() => 'awaiting',
            $this->holdExpired() || $this->status === BookingStatus::Expired => 'expired',
            default => 'cancelled',
        };
    }
}
