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
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Clean up the uploaded image when a venue is deleted.
     */
    protected static function booted(): void
    {
        static::deleting(function (Venue $venue) {
            if ($venue->image_path) {
                Storage::disk('public')->delete($venue->image_path);
            }
        });
    }

    /**
     * Public URL of the venue's image, or null if it has none.
     */
    public function imageUrl(): ?string
    {
        return $this->image_path
            ? Storage::disk('public')->url($this->image_path)
            : null;
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
     * Dates the whole venue is closed (holidays, maintenance) — every court is
     * unbookable on these dates.
     */
    public function closedDates(): HasMany
    {
        return $this->hasMany(VenueClosedDate::class);
    }

    /**
     * Whether an admin has approved this venue to be visible to customers.
     */
    public function isApproved(): bool
    {
        return ! is_null($this->approved_at);
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
