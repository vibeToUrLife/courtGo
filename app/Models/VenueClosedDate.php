<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VenueClosedDate extends Model
{
    /** @use HasFactory<\Database\Factories\VenueClosedDateFactory> */
    use HasFactory;

    protected $fillable = [
        'venue_id',
        'date',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * The venue this closed date (holiday) belongs to. Closing a date here
     * closes every court in the venue for that day.
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
