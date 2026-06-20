<?php

namespace App\Livewire;

use App\Enums\BookingStatus;
use App\Exceptions\SlotUnavailableException;
use App\Models\SessionTemplate;
use App\Models\Venue;
use App\Services\AvailabilityService;
use App\Services\BookingPaymentService;
use App\Services\BookingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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

    /** Selected slot keys, "courtId-sessionId". */
    public array $selected = [];

    public function mount(Venue $venue): void
    {
        $this->venue = $venue;

        if ($this->date === '') {
            $this->date = Carbon::tomorrow()->toDateString();
        }
    }

    /** Changing the date clears the selection — the slots differ. */
    public function updatedDate(): void
    {
        $this->selected = [];
    }

    public function toggleSlot(int $courtId, int $sessionId): void
    {
        $key = $courtId.'-'.$sessionId;

        $this->selected = in_array($key, $this->selected, true)
            ? array_values(array_diff($this->selected, [$key]))
            : [...$this->selected, $key];
    }

    /** Reserve every selected slot (all-or-nothing) and pay for them in one go. */
    public function checkout(BookingService $bookings, BookingPaymentService $payments)
    {
        $sessions = $this->selectedSessions();

        if ($sessions->isEmpty()) {
            return null;
        }

        try {
            $created = $bookings->reserveMany(auth()->user(), $sessions, Carbon::parse($this->date));
        } catch (SlotUnavailableException $e) {
            $this->selected = [];
            session()->flash('booking_error', $e->getMessage());

            return null;
        }

        // Real Stripe Checkout: one payment for all the slots. away() because
        // we're sending the customer to an external (Stripe) URL.
        if (config('cashier.secret')) {
            return redirect()->away($payments->checkoutUrlForBookings(
                $created,
                route('bookings.cart.success'),
                route('bookings.cart.cancel', ['bookings' => collect($created)->pluck('id')->implode(',')]),
            ));
        }

        // Demo mode (no Stripe keys): confirm all so the flow can be tried.
        foreach ($created as $booking) {
            $booking->update(['status' => BookingStatus::Confirmed, 'payment_status' => 'paid', 'processed_at' => now()]);
        }

        return redirect()->route('bookings.mine')->with('booking_confirmed', true);
    }

    /** The currently selected sessions, validated to belong to this venue. */
    private function selectedSessions(): Collection
    {
        $sessionIds = collect($this->selected)->map(fn ($key) => (int) explode('-', $key)[1]);

        return SessionTemplate::query()
            ->whereIn('id', $sessionIds)
            ->whereHas('court', fn ($q) => $q->where('venue_id', $this->venue->id))
            ->with('court')
            ->get();
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

        $selectedSessions = $this->selectedSessions();

        return view('livewire.venue-show', [
            'courts' => $courts,
            'timeColumns' => $timeColumns,
            'grid' => $grid,
            'selectedSummary' => $selectedSessions->map(fn ($s) => [
                'court' => $s->court->name,
                'time' => Carbon::parse($s->start_time)->format('g:i A').'–'.Carbon::parse($s->end_time)->format('g:i A'),
                'price' => (float) $s->price,
            ]),
            'selectedTotal' => (float) $selectedSessions->sum('price'),
        ]);
    }

    /** A stable "HH:MM-HH:MM" key for a session's time slot. */
    private function slotLabel($session): string
    {
        return substr((string) $session->start_time, 0, 5).'-'.substr((string) $session->end_time, 0, 5);
    }
}
