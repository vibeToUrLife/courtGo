<?php

namespace App\Livewire\Owner\Venues;

use App\Models\Venue;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('My Venues')]
class Index extends Component
{
    use AuthorizesRequests, WithFileUploads;

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

    /** One optional photo of the place (shown to customers). */
    #[Validate('nullable|image|max:2048')]
    public $image;

    /** Editing the photo of an existing venue. */
    public ?int $editingVenueId = null;
    public $newImage;

    public function save(): void
    {
        $validated = $this->validate();

        // Keep state within the curated list (the dropdown enforces this in the UI).
        $this->validate(['state' => ['required', Rule::in(config('courtgo.states'))]]);

        $data = collect($validated)->except('image')->all();

        if ($this->image) {
            $data['image_path'] = $this->image->store('venues', 'public');
        }

        auth()->user()->venues()->create($data);

        $this->reset('name', 'description', 'address', 'city', 'state', 'image');
    }

    public function delete(int $venueId): void
    {
        $venue = Venue::findOrFail($venueId);

        $this->authorize('delete', $venue);

        $venue->delete();
    }

    /** Open the "change photo" panel for one of the owner's venues. */
    public function editPhoto(int $venueId): void
    {
        $venue = Venue::findOrFail($venueId);
        $this->authorize('update', $venue);

        $this->reset('newImage');
        $this->editingVenueId = $venueId;
    }

    public function cancelEditPhoto(): void
    {
        $this->reset('editingVenueId', 'newImage');
    }

    /** Replace the venue's photo, removing the old file. */
    public function updatePhoto(): void
    {
        $venue = Venue::findOrFail($this->editingVenueId);
        $this->authorize('update', $venue);

        $this->validate(['newImage' => 'required|image|max:2048']);

        if ($venue->image_path) {
            Storage::disk('public')->delete($venue->image_path);
        }

        $venue->update(['image_path' => $this->newImage->store('venues', 'public')]);

        $this->reset('editingVenueId', 'newImage');
    }

    public function render()
    {
        return view('livewire.owner.venues.index', [
            'venues' => auth()->user()->venues()->withCount('courts')->latest()->get(),
        ]);
    }
}
