<?php

namespace App\Livewire\Admin;

use App\Models\SessionTemplate;
use App\Models\Venue;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Venue details')]
class VenueShow extends Component
{
    public Venue $venue;

    public function mount(Venue $venue): void
    {
        $this->venue = $venue;
    }

    /** Tick or untick a verification item (e.g. after checking the uploaded document). */
    public function toggleVerified(string $key): void
    {
        if (! in_array($key, Venue::verificationKeys(), true)) {
            return;
        }

        $items = $this->venue->verified_items ?? [];
        $alreadyVerified = in_array($key, $items, true);

        // Can't verify an item the owner hasn't uploaded a document for.
        if (! $alreadyVerified && ! $this->venue->documents()->where('type', $key)->exists()) {
            return;
        }

        $items = $alreadyVerified
            ? array_values(array_diff($items, [$key]))
            : [...$items, $key];

        $this->venue->update(['verified_items' => $items]);
    }

    /** Approve this venue so it becomes visible and bookable — only once fully verified. */
    public function approve(): void
    {
        if (! $this->venue->isFullyVerified()) {
            return; // gated: every verification item must be ticked first
        }

        $this->venue->update(['approved_at' => now()]);
    }

    public function render()
    {
        $this->venue->load(['owner', 'courts' => fn ($q) => $q->orderBy('name'), 'photos', 'documents']);

        // Price range across all active slots (not gated on bookable, so admins
        // can review pricing even before the venue is approved).
        $prices = SessionTemplate::query()
            ->where('is_active', true)
            ->whereHas('court', fn ($q) => $q->where('venue_id', $this->venue->id))
            ->pluck('price');

        return view('livewire.admin.venue-show', [
            'subscription' => $this->venue->owner->subscription($this->venue->subscriptionType()),
            'priceRange' => $prices->isEmpty() ? null : ['min' => (float) $prices->min(), 'max' => (float) $prices->max()],
            'verificationItems' => config('courtgo.verification'),
            'documents' => $this->venue->documents->groupBy('type'),
        ]);
    }
}
