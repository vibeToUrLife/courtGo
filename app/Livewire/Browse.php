<?php

namespace App\Livewire;

use App\Models\Court;
use App\Models\Venue;
use App\Services\AvailabilityService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Find a Court')]
class Browse extends Component
{
    use WithPagination;

    #[Url]
    public string $name = '';

    #[Url]
    public string $sport = '';

    #[Url]
    public string $city = '';

    #[Url]
    public string $date = '';

    /** Jump back to the first page whenever a filter changes. */
    public function updated(string $property): void
    {
        if (in_array($property, ['name', 'sport', 'city', 'date'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        // Distinct sports across bookable courts — used to populate the dropdown.
        $sports = Court::query()->bookable()->orderBy('sport')->pluck('sport')->unique()->values();

        // Which day to show availability for. Defaults to tomorrow (matching the
        // venue page) and falls back gracefully if a bad date is in the URL.
        $date = rescue(
            fn () => $this->date !== '' ? Carbon::parse($this->date)->startOfDay() : Carbon::tomorrow(),
            Carbon::tomorrow(),
            report: false
        );

        $venues = Venue::query()
            ->bookable()
            ->when($this->name, fn ($q) => $q->where('name', 'like', '%'.$this->name.'%'))
            ->when($this->city, fn ($q) => $q->where('city', 'like', '%'.$this->city.'%'))
            ->when($this->sport, fn ($q) => $q->whereHas(
                'courts',
                fn ($c) => $c->bookable()->where('sport', $this->sport)
            ))
            ->with(['courts' => fn ($q) => $q->bookable()
                ->with(['sessionTemplates' => fn ($s) => $s->where('is_active', true)])])
            ->orderBy('name')
            ->paginate(12);

        // Per-venue summary: cheapest session price and how many sessions are
        // actually bookable on the chosen date (live availability). Only the
        // current page of venues is summarised, so the query cost is bounded.
        $availability = app(AvailabilityService::class);
        $summaries = [];
        foreach ($venues as $venue) {
            $available = 0;
            foreach ($venue->courts as $court) {
                $available += $availability->availableSessions($court, $date)->count();
            }

            $summaries[$venue->id] = [
                'price_from' => $venue->courts->flatMap->sessionTemplates->min('price'),
                'available' => $available,
            ];
        }

        return view('livewire.browse', [
            'venues' => $venues,
            'sports' => $sports,
            'summaries' => $summaries,
            'displayDate' => $date, // Carbon — named to avoid clashing with the $date URL property
        ]);
    }
}
