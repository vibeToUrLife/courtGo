<?php

namespace App\Livewire\Owner\Venues;

use App\Models\Venue;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Manage Courts')]
class Courts extends Component
{
    use AuthorizesRequests;

    public Venue $venue;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255')]
    public string $sport = '';

    #[Validate('boolean')]
    public bool $is_active = true;

    public function mount(Venue $venue): void
    {
        // Only the venue's owner may manage its courts.
        $this->authorize('update', $venue);

        $this->venue = $venue;
    }

    public function addCourt(): void
    {
        $validated = $this->validate();

        $this->venue->courts()->create($validated);

        $this->reset('name', 'sport');
        $this->is_active = true;
    }

    public function toggleActive(int $courtId): void
    {
        $court = $this->venue->courts()->findOrFail($courtId);

        $court->update(['is_active' => ! $court->is_active]);
    }

    public function deleteCourt(int $courtId): void
    {
        $court = $this->venue->courts()->findOrFail($courtId);

        $court->delete();
    }

    public function render()
    {
        return view('livewire.owner.venues.courts', [
            'courts' => $this->venue->courts()->latest()->get(),
        ]);
    }
}
