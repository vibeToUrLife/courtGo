<?php

namespace App\Livewire\Owner\Courts;

use App\Concerns\HandlesScheduleTimes;
use App\Models\Court;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Court Schedule')]
class Schedule extends Component
{
    use AuthorizesRequests;
    use HandlesScheduleTimes;

    /** 0 = Sunday … 6 = Saturday (matches Carbon's dayOfWeek). */
    public const DAYS = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    public Court $court;

    // Add-session form (no default start/end — the owner fills them in).
    public int $day_of_week = 1;
    public string $start_time = '';
    public string $end_time = '';
    public $hours_per_slot = 1; // length of each bookable slot, in hours
    public $price = 40;

    public function mount(Court $court): void
    {
        // Only the owner of the court's venue may edit its schedule.
        $this->authorize('update', $court->venue);

        $this->court = $court;
    }

    public function updated(string $property): void
    {
        // Clear a stale time / "doesn't divide evenly" warning as soon as the
        // owner adjusts the window or slot length — the live preview already
        // reflects the new result.
        if (in_array($property, ['start_time', 'end_time', 'hours_per_slot'], true)) {
            $this->resetValidation();
        }
    }

    public function addSession(): void
    {
        $validated = $this->validate([
            'day_of_week' => 'required|integer|between:0,6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'hours_per_slot' => 'required|numeric|min:0.5|max:24',
            'price' => 'required|numeric|min:0',
        ]);

        // A 00:00 end means midnight (end-of-day); any other end must be later.
        if ($this->slotMinutes($validated['end_time'], isEnd: true) <= $this->slotMinutes($validated['start_time'])) {
            throw ValidationException::withMessages([
                'end_time' => 'End time must be after start time.',
            ]);
        }

        // Split the window into back-to-back slots of the chosen length.
        $slots = $this->buildSlots($validated['start_time'], $validated['end_time'], (float) $validated['hours_per_slot']);

        if ($slots === null) {
            $label = $this->slotLengthLabel((float) $validated['hours_per_slot']);

            throw ValidationException::withMessages([
                'hours_per_slot' => "That time range doesn't divide evenly into {$label} slots.",
            ]);
        }

        // No generated slot may overlap a session that already exists on that day
        // (compared in minutes so a midnight end is handled correctly).
        $existing = $this->court->sessionTemplates()
            ->where('is_active', true)
            ->where('day_of_week', $validated['day_of_week'])
            ->get();

        foreach ($slots as $slot) {
            $start = $this->slotMinutes($slot['start']);
            $end = $this->slotMinutes($slot['end'], isEnd: true);

            $clashes = $existing->contains(fn ($s) => $start < $this->slotMinutes((string) $s->end_time, isEnd: true)
                && $end > $this->slotMinutes((string) $s->start_time));

            if ($clashes) {
                throw ValidationException::withMessages([
                    'start_time' => "Slot {$slot['start']}–{$slot['end']} overlaps a session you already have on that day.",
                ]);
            }
        }

        foreach ($slots as $slot) {
            $this->court->sessionTemplates()->create([
                'day_of_week' => $validated['day_of_week'],
                'start_time' => $slot['start'],
                'end_time' => $slot['end'],
                'price' => $validated['price'],
                'is_active' => true,
            ]);
        }

        $this->reset('start_time', 'end_time');
    }

    public function deleteSession(int $sessionId): void
    {
        $this->court->sessionTemplates()->whereKey($sessionId)->delete();
    }

    public function render()
    {
        return view('livewire.owner.courts.schedule', [
            'sessionsByDay' => $this->court->sessionTemplates()
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get()
                ->groupBy('day_of_week'),
            'days' => config('courtgo.weekdays'), // Monday-first display order
            'times' => $this->timeOptions(),
            'endTimes' => $this->endTimeOptions(),
            'preview' => $this->slotPreview($this->start_time, $this->end_time, $this->hours_per_slot),
        ]);
    }
}
