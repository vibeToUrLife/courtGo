<?php

namespace App\Livewire;

use App\Models\Venue;
use App\Services\AvailabilityService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.customer')]
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
        $weekday = $date->dayOfWeek;

        $courts = $this->venue->courts()->bookable()->orderBy('name')->get();

        // Build a calendar grid: rows = time slots, columns = courts. Each cell is
        // bookable (available) or shown as taken, so customers click a free slot.
        $grid = [];      // court_id => [slot label => ['state' => ..., 'session' => ...]]
        $rowStarts = []; // slot label => start "HH:MM", for chronological row order

        foreach ($courts as $court) {
            $available = $availability->availableSessions($court, $date)
                ->keyBy(fn ($s) => $this->slotLabel($s));

            $scheduled = $court->sessionTemplates()
                ->where('is_active', true)
                ->where('day_of_week', $weekday)
                ->orderBy('start_time')
                ->get();

            foreach ($scheduled as $session) {
                $label = $this->slotLabel($session);
                $rowStarts[$label] = substr((string) $session->start_time, 0, 5);
                $grid[$court->id][$label] = [
                    'state' => $available->has($label) ? 'available' : 'taken',
                    'session' => $session,
                ];
            }
        }

        asort($rowStarts); // chronological by start time

        $timeColumns = [];
        foreach (array_keys($rowStarts) as $label) {
            [$start, $end] = explode('-', $label);
            $timeColumns[] = [
                'key' => $label,
                'start' => Carbon::parse($start)->format('g:i A'),                                       // compact column header
                'display' => Carbon::parse($start)->format('g:i A').' – '.Carbon::parse($end)->format('g:i A'),
            ];
        }

        return view('livewire.venue-show', [
            'courts' => $courts,
            'timeColumns' => $timeColumns,
            'grid' => $grid,
        ]);
    }

    /** A stable "HH:MM-HH:MM" key for a session's time slot. */
    private function slotLabel($session): string
    {
        return substr((string) $session->start_time, 0, 5).'-'.substr((string) $session->end_time, 0, 5);
    }
}
