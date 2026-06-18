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

    // Add-session form
    public int $day_of_week = 1;
    public string $start_time = '09:00';
    public string $end_time = '11:00';
    public $price = 40;

    // Block-date form
    public string $block_date = '';
    public string $block_reason = '';

    public function mount(Court $court): void
    {
        // Only the owner of the court's venue may edit its schedule.
        $this->authorize('update', $court->venue);

        $this->court = $court;
    }

    public function addSession(): void
    {
        $validated = $this->validate([
            'day_of_week' => 'required|integer|between:0,6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'price' => 'required|numeric|min:0',
        ]);

        // A 00:00 end means midnight (end-of-day); any other end must be later.
        $start = $this->slotMinutes($validated['start_time']);
        $end = $this->slotMinutes($validated['end_time'], isEnd: true);

        if ($end <= $start) {
            throw ValidationException::withMessages([
                'end_time' => 'End time must be after start time.',
            ]);
        }

        // Reject sessions that overlap an existing active session on the same weekday
        // (compared in minutes so a midnight end is handled correctly).
        $overlaps = $this->court->sessionTemplates()
            ->where('is_active', true)
            ->where('day_of_week', $validated['day_of_week'])
            ->get()
            ->contains(fn ($existing) => $start < $this->slotMinutes((string) $existing->end_time, isEnd: true)
                && $end > $this->slotMinutes((string) $existing->start_time));

        if ($overlaps) {
            throw ValidationException::withMessages([
                'start_time' => 'This overlaps a session you already have on that day.',
            ]);
        }

        $this->court->sessionTemplates()->create($validated + ['is_active' => true]);

        $this->reset('start_time', 'end_time', 'price');
        $this->start_time = '09:00';
        $this->end_time = '11:00';
        $this->price = 40;
    }

    public function deleteSession(int $sessionId): void
    {
        $this->court->sessionTemplates()->whereKey($sessionId)->delete();
    }

    public function blockDate(): void
    {
        $validated = $this->validate([
            'block_date' => 'required|date|after_or_equal:today',
            'block_reason' => 'nullable|string|max:255',
        ]);

        $this->court->blockedDates()->updateOrCreate(
            ['date' => $validated['block_date']],
            ['reason' => $validated['block_reason'] ?: null],
        );

        $this->reset('block_date', 'block_reason');
    }

    public function unblockDate(int $blockedDateId): void
    {
        $this->court->blockedDates()->whereKey($blockedDateId)->delete();
    }

    public function render()
    {
        return view('livewire.owner.courts.schedule', [
            'sessionsByDay' => $this->court->sessionTemplates()
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get()
                ->groupBy('day_of_week'),
            'blockedDates' => $this->court->blockedDates()->orderBy('date')->get(),
            'days' => self::DAYS,
        ]);
    }
}
