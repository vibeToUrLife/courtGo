<?php

namespace App\Livewire\Owner\Venues;

use App\Models\Venue;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('My Venues')]
class Index extends Component
{
    use AuthorizesRequests;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    #[Validate('required|string|max:255')]
    public string $address = '';

    #[Validate('required|string|max:255')]
    public string $city = '';

    #[Validate('required|string|max:255')]
    public string $state = '';

    public function save(): void
    {
        $validated = $this->validate();

        auth()->user()->venues()->create($validated);

        $this->reset('name', 'description', 'address', 'city', 'state');
    }

    public function delete(int $venueId): void
    {
        $venue = Venue::findOrFail($venueId);

        $this->authorize('delete', $venue);

        $venue->delete();
    }

    public function render()
    {
        return view('livewire.owner.venues.index', [
            'venues' => auth()->user()->venues()->withCount('courts')->latest()->get(),
        ]);
    }
}
