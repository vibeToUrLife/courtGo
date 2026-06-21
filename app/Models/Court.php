<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Court extends Model
{
    /** @use HasFactory<\Database\Factories\CourtFactory> */
    use HasFactory;

    protected $fillable = [
        'venue_id',
        'name',
        'sport',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * The venue this court belongs to.
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * The weekly recurring sessions for this court.
     */
    public function sessionTemplates(): HasMany
    {
        return $this->hasMany(SessionTemplate::class);
    }

    /**
     * A court can be booked only when it is active, its venue has been approved
     * by an admin, its owner is payout-ready (not suspended + Connect-onboarded),
     * AND the venue has its own active subscription.
     */
    public function isBookable(): bool
    {
        return $this->is_active
            && $this->venue->isApproved()
            && $this->venue->owner->canAcceptBookings()
            && $this->venue->isSubscribed();
    }

    /**
     * Limit to courts customers can actually book: active, in an admin-approved
     * venue whose owner is payout-ready and which has its own active/trialing
     * subscription (one subscription per venue, typed "venue:{id}").
     */
    public function scopeBookable($query)
    {
        // The subscription type encodes the venue id, so the EXISTS subquery must
        // compare against "venue:" + the venue's id (concat differs per driver).
        $driver = $query->getConnection()->getDriverName();
        $venueType = $driver === 'mysql'
            ? "CONCAT('venue:', venues.id)"
            : "('venue:' || venues.id)";

        return $query->where('courts.is_active', true)
            ->whereHas('venue', function ($venue) use ($venueType) {
                $venue->whereNotNull('approved_at')
                    ->whereHas('owner', fn ($owner) => $owner->where('is_suspended', false)->where('connect_onboarded', true))
                    ->whereExists(function ($sub) use ($venueType) {
                        $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                            ->from('subscriptions')
                            ->whereColumn('subscriptions.user_id', 'venues.owner_id')
                            ->whereRaw("subscriptions.type = $venueType")
                            // Mirror Cashier's valid(): active/trialing and not ended,
                            // OR still inside the grace period (ends_at in the future).
                            ->where(function ($q) {
                                $q->where(fn ($a) => $a->whereIn('subscriptions.stripe_status', ['active', 'trialing'])->whereNull('subscriptions.ends_at'))
                                    ->orWhere('subscriptions.ends_at', '>', now());
                            });
                    });
            });
    }

    /**
     * Bookings made against this court.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
