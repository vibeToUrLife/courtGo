<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Venue extends Model
{
    /** @use HasFactory<\Database\Factories\VenueFactory> */
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'address',
        'city',
        'state',
        'image_path',
        'approved_at',
        'amenities',
        'announcement',
        'announcement_active',
        'announcement_until',
        'opening_hours',
        'pricing_note',
        'policy',
        'contact_phone',
        'contact_whatsapp',
        'contact_email',
        'contact_website',
        'contact_instagram',
        'contact_facebook',
        'layout_image_path',
        'verified_items',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'amenities' => 'array',
            'announcement_active' => 'boolean',
            'announcement_until' => 'date',
            'opening_hours' => 'array',
            'verified_items' => 'array',
        ];
    }

    /**
     * Clean up uploaded images when a venue is deleted.
     */
    protected static function booted(): void
    {
        static::deleting(function (Venue $venue) {
            foreach ([$venue->image_path, $venue->layout_image_path] as $path) {
                if ($path) {
                    Storage::disk('public')->delete($path);
                }
            }

            // Remove gallery + verification files (the DB cascade deletes the
            // rows, not the files).
            $venue->photos->each->delete();
            $venue->documents->each->delete();
        });
    }

    /**
     * Public URL of the venue's cover image, or null if it has none.
     */
    public function imageUrl(): ?string
    {
        return $this->image_path
            ? Storage::disk('public')->url($this->image_path)
            : null;
    }

    /**
     * Public URL of the venue's layout (floor-plan) image, or null if it has none.
     */
    public function layoutImageUrl(): ?string
    {
        return $this->layout_image_path
            ? Storage::disk('public')->url($this->layout_image_path)
            : null;
    }

    /**
     * Whether the announcement should be shown to customers: it's switched on,
     * has text, and hasn't passed its optional "hide after" date.
     */
    public function announcementVisible(): bool
    {
        return $this->announcement_active
            && filled($this->announcement)
            && (is_null($this->announcement_until) || ! today()->gt($this->announcement_until));
    }

    /**
     * The cheapest and dearest active slot price across this venue's courts,
     * or null when there are no priced slots yet.
     *
     * @return array{min: float, max: float}|null
     */
    public function priceRange(): ?array
    {
        // Only count slots customers can actually book, so the advertised range
        // matches the booking grid (nothing shown for a non-bookable venue).
        $prices = SessionTemplate::query()
            ->where('is_active', true)
            ->whereHas('court', fn ($q) => $q->where('venue_id', $this->id)->bookable())
            ->pluck('price');

        if ($prices->isEmpty()) {
            return null;
        }

        return ['min' => (float) $prices->min(), 'max' => (float) $prices->max()];
    }

    /**
     * The owner (a user) who runs this venue.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * The courts inside this venue.
     */
    public function courts(): HasMany
    {
        return $this->hasMany(Court::class);
    }

    /**
     * Gallery photos shown on the venue page, in display order.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(VenuePhoto::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Verification documents the owner uploaded for admin review.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(VenueDocument::class)->latest();
    }

    /** All verification keys an admin must tick before this venue can be approved. */
    public static function verificationKeys(): array
    {
        return array_keys(config('courtgo.verification'));
    }

    /** Whether an admin has ticked a given verification item. */
    public function isItemVerified(string $key): bool
    {
        return in_array($key, $this->verified_items ?? [], true);
    }

    /** Whether every required verification item has been ticked off. */
    public function isFullyVerified(): bool
    {
        return empty(array_diff(self::verificationKeys(), $this->verified_items ?? []));
    }

    /** How many of the required verification items are ticked (for progress display). */
    public function verifiedCount(): int
    {
        return count(array_intersect(self::verificationKeys(), $this->verified_items ?? []));
    }

    /** Verification document types the owner has uploaded at least one file for. */
    public function uploadedDocumentTypes(): array
    {
        return $this->documents->pluck('type')->unique()->values()->all();
    }

    /** Required verification documents the owner still needs to upload. */
    public function missingDocumentTypes(): array
    {
        return array_values(array_diff(self::verificationKeys(), $this->uploadedDocumentTypes()));
    }

    /** Whether the owner has uploaded every required verification document. */
    public function hasAllDocuments(): bool
    {
        return empty($this->missingDocumentTypes());
    }

    /**
     * Dates the whole venue is closed (holidays, maintenance) — every court is
     * unbookable on these dates.
     */
    public function closedDates(): HasMany
    {
        return $this->hasMany(VenueClosedDate::class);
    }

    /**
     * The ticked amenities resolved to their config entries, in config order.
     * Unknown/removed keys are dropped.
     *
     * @return array<int, array{key: string, label: string, icon: string}>
     */
    public function amenityLabels(): array
    {
        $chosen = $this->amenities ?? [];

        return collect(config('courtgo.amenities'))
            ->filter(fn ($meta, $key) => in_array($key, $chosen, true))
            ->map(fn ($meta, $key) => ['key' => $key, 'label' => $meta['label'], 'icon' => $meta['icon']])
            ->values()
            ->all();
    }

    /**
     * Whether an admin has approved this venue to be visible to customers.
     */
    public function isApproved(): bool
    {
        return ! is_null($this->approved_at);
    }

    /** The owner's Cashier subscription name for THIS venue (one sub per venue). */
    public function subscriptionType(): string
    {
        return 'venue:'.$this->id;
    }

    /** Whether this venue has its own active (or trialing) subscription. */
    public function isSubscribed(): bool
    {
        return $this->owner->subscribed($this->subscriptionType());
    }

    /**
     * Limit to venues an admin has approved.
     */
    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    /**
     * Limit to venues customers can book: the venue itself is approved AND it
     * has at least one bookable court (active + live owner).
     */
    public function scopeBookable($query)
    {
        return $query->approved()
            ->whereHas('courts', fn ($court) => $court->bookable());
    }
}
