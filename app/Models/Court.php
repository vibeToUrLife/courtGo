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
     * by an admin, AND its owner is able to accept bookings (active subscription
     * + completed Connect onboarding).
     */
    public function isBookable(): bool
    {
        return $this->is_active
            && $this->venue->isApproved()
            && $this->venue->owner->canAcceptBookings();
    }

    /**
     * Limit to courts customers can actually book: active, in an admin-approved
     * venue, with a live owner (Connect-onboarded + an active/trialing subscription).
     */
    public function scopeBookable($query)
    {
        return $query->where('is_active', true)
            ->whereHas('venue', fn ($venue) => $venue->whereNotNull('approved_at'))
            ->whereHas('venue.owner', function ($owner) {
                $owner->where('is_suspended', false)
                    ->where('connect_onboarded', true)
                    ->whereHas('subscriptions', function ($sub) {
                        $sub->where('type', 'default')->whereIn('stripe_status', ['active', 'trialing']);
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
