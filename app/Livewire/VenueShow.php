<?php

namespace App\Livewire;

use App\Models\Venue;
use App\Services\AvailabilityService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Book a Court')]
class VenueShow extends Component
{
    public Venue $venue;

    #[Url]
    public string $date = '';

    public function mount(Venue $venue): void
    {
        $this->venue = $venue;

        if ($this->date === '') {
            $this->date = Carbon::tomorrow()->toDateString();
        }
    }

    public function render()
    {
        $availability = app(AvailabilityService::class);
        $date = Carbon::parse($this->date);

        // Gather every available (court + session) for the chosen date, then group by time slot.
        $offers = collect();
        foreach ($this->venue->courts()->bookable()->orderBy('name')->get() as $court) {
            foreach ($availability->availableSessions($court, $date) as $session) {
                $offers->push(['court' => $court, 'session' => $session]);
            }
        }

        $slots = $offers
            ->groupBy(fn ($o) => substr((string) $o['session']->start_time, 0, 5).'-'.substr((string) $o['session']->end_time, 0, 5))
            ->sortKeys();

        return view('livewire.venue-show', ['timeSlots' => $slots]);
    }
}
