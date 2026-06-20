<?php

namespace App\Livewire\Owner\Venues;

use App\Concerns\HandlesScheduleTimes;
use App\Models\Venue;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Manage Courts')]
class Courts extends Component
{
    use AuthorizesRequests;
    use HandlesScheduleTimes;

    /** 0 = Sunday … 6 = Saturday (matches Carbon's dayOfWeek). */
    public const DAYS = [
        0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
        4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday',
    ];

    public Venue $venue;

    // --- Add-courts wizard ---
    public bool $showWizard = false;
    public int $step = 1;

    // Step 1: basics + auto-naming
    public string $sport = '';
    public string $customSport = ''; // used when $sport === 'Other'
    public ?int $count = 1; // nullable so emptying the field becomes null, not an error
    public string $namingStyle = 'number'; // number | letter
    public string $prefix = 'Court';       // the name owners type; numbers/letters are appended

    // Step 2: do all courts share one schedule?
    public string $scheduleMode = 'same'; // same | different

    // Step 3: schedule rows. "same" uses $sessions; "different" uses $courtSessions[courtIndex].
    public array $sessions = [];
    public array $courtSessions = [];

    // Venue holidays — a closed date hides every court in this venue that day.
    public string $closed_date = '';
    public string $closed_reason = '';

    public function mount(Venue $venue): void
    {
        // Only the venue's owner may manage its courts.
        $this->authorize('update', $venue);

        $this->venue = $venue;
    }

    // ---------------------------------------------------------------- wizard

    public function startWizard(): void
    {
        $this->resetWizard();
        $this->showWizard = true;
    }

    public function cancelWizard(): void
    {
        $this->resetWizard();
        $this->showWizard = false;
    }

    private function resetWizard(): void
    {
        $this->reset([
            'step', 'sport', 'customSport', 'count', 'namingStyle', 'prefix',
            'scheduleMode', 'sessions', 'courtSessions',
        ]);
    }

    /**
     * Keep the court count within bounds as it's typed. A cleared field (null)
     * is left blank so it doesn't fight live typing — it defaults to 1 court on
     * submit. Real numbers are clamped to 1–50. Being nullable also means an
     * empty field no longer leaves the typed property unset (which broke the page).
     */
    public function updated(string $property): void
    {
        // Clear a stale slot warning as soon as the owner edits a schedule row —
        // the live preview already reflects whether the window now divides evenly.
        if (str_starts_with($property, 'sessions') || str_starts_with($property, 'courtSessions')) {
            $this->resetValidation();
        }
    }

    public function updatedCount(): void
    {
        if ($this->count !== null) {
            $this->count = max(1, min($this->count, 50));
        }
    }

    /** The sport to save — the chosen list value, or the custom name when "Other". */
    public function effectiveSport(): string
    {
        return $this->sport === 'Other' ? trim($this->customSport) : $this->sport;
    }

    /** The court names that will be created, e.g. ["Court 1", "Court 2"] or ["Court A", "Court B"]. */
    public function generatedNames(): array
    {
        $names = [];
        $count = max(0, min((int) $this->count, 50)); // (int) null → 0 while the field is empty
        $prefix = trim($this->prefix);

        for ($i = 0; $i < $count; $i++) {
            $label = $this->namingStyle === 'letter'
                ? chr(ord('A') + $i)   // A, B, C …
                : (string) ($i + 1);   // 1, 2, 3 …

            $names[] = trim(($prefix !== '' ? $prefix.' ' : '').$label);
        }

        return $names;
    }

    public function toStep2(): void
    {
        // Normalise first so a whitespace-only custom sport fails required_if.
        $this->customSport = trim($this->customSport);

        $this->validate([
            'sport' => ['required', 'string', Rule::in([...config('courtgo.sports'), 'Other'])],
            'customSport' => 'required_if:sport,Other|nullable|string|max:255',
            'count' => 'required|integer|min:1|max:50',
            'namingStyle' => 'required|in:number,letter',
        ]);

        if ($this->namingStyle === 'letter' && $this->count > 26) {
            throw ValidationException::withMessages([
                'count' => 'Letter naming supports up to 26 courts (A–Z). Choose numbers for more.',
            ]);
        }

        $this->step = 2;
    }

    public function toStep3(): void
    {
        $this->validate(['scheduleMode' => 'required|in:same,different']);

        if ($this->scheduleMode === 'same') {
            if (empty($this->sessions)) {
                $this->sessions = [$this->blankSession()];
            }
        } else {
            // Keep rows the owner already entered; only seed a blank for courts
            // that have none, and drop any schedules beyond the current count.
            for ($i = 0; $i < $this->count; $i++) {
                if (empty($this->courtSessions[$i])) {
                    $this->courtSessions[$i] = [$this->blankSession()];
                }
            }
            $this->courtSessions = array_slice($this->courtSessions, 0, $this->count, true);
        }

        $this->step = 3;
    }

    public function back(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    private function blankSession(): array
    {
        // A time window split into slots, applied to the ticked days (default Mon–Fri).
        return ['days' => [1, 2, 3, 4, 5], 'start_time' => '20:00', 'end_time' => '22:00', 'hours' => 2, 'price' => 40];
    }

    public function addSession(): void
    {
        $this->sessions[] = $this->blankSession();
    }

    public function removeSession(int $index): void
    {
        unset($this->sessions[$index]);
        $this->sessions = array_values($this->sessions);
    }

    public function addCourtSession(int $courtIndex): void
    {
        $this->courtSessions[$courtIndex][] = $this->blankSession();
    }

    public function removeCourtSession(int $courtIndex, int $index): void
    {
        unset($this->courtSessions[$courtIndex][$index]);
        $this->courtSessions[$courtIndex] = array_values($this->courtSessions[$courtIndex]);
    }

    public function create(): void
    {
        $this->validate($this->scheduleMode === 'same'
            ? [
                'sessions.*.days' => 'required|array|min:1',
                'sessions.*.days.*' => 'integer|between:0,6',
                'sessions.*.start_time' => 'required|date_format:H:i',
                'sessions.*.end_time' => 'required|date_format:H:i',
                'sessions.*.hours' => 'required|numeric|min:0.5|max:24',
                'sessions.*.price' => 'required|numeric|min:0',
            ]
            : [
                'courtSessions.*.*.days' => 'required|array|min:1',
                'courtSessions.*.*.days.*' => 'integer|between:0,6',
                'courtSessions.*.*.start_time' => 'required|date_format:H:i',
                'courtSessions.*.*.end_time' => 'required|date_format:H:i',
                'courtSessions.*.*.hours' => 'required|numeric|min:0.5|max:24',
                'courtSessions.*.*.price' => 'required|numeric|min:0',
            ], [
                'sessions.*.days.required' => 'Pick at least one day for each slot row.',
                'sessions.*.days.min' => 'Pick at least one day for each slot row.',
                'courtSessions.*.*.days.required' => 'Pick at least one day for each slot row.',
                'courtSessions.*.*.days.min' => 'Pick at least one day for each slot row.',
            ]);

        $names = $this->generatedNames();

        // Resolve each court's schedule rows.
        $schedules = [];
        foreach ($names as $i => $name) {
            $schedules[$i] = $this->scheduleMode === 'same'
                ? $this->sessions
                : ($this->courtSessions[$i] ?? []);

            // A court must have at least one slot, or it would exist but never be bookable.
            if (empty($schedules[$i])) {
                throw ValidationException::withMessages([
                    $this->scheduleMode === 'same' ? 'sessions' : "courtSessions.$i" => "Add at least one slot for {$name}.",
                ]);
            }

            $this->validateSchedule($schedules[$i], $i);
        }

        DB::transaction(function () use ($names, $schedules) {
            foreach ($names as $i => $name) {
                $court = $this->venue->courts()->create([
                    'name' => $name,
                    'sport' => $this->effectiveSport(),
                    'is_active' => true,
                ]);

                // Each row's window is split into equal slots, created for every
                // day in its range. (validateSchedule already proved it divides evenly.)
                foreach ($schedules[$i] as $row) {
                    $slots = $this->buildSlots($row['start_time'], $row['end_time'], (float) $row['hours']);

                    foreach ($this->selectedDays($row) as $day) {
                        foreach ($slots as $slot) {
                            $court->sessionTemplates()->create([
                                'day_of_week' => $day,
                                'start_time' => $slot['start'],
                                'end_time' => $slot['end'],
                                'price' => $row['price'],
                                'is_active' => true,
                            ]);
                        }
                    }
                }
            }
        });

        $this->cancelWizard();
    }

    /** The weekdays (0=Sun … 6=Sat) a row's slots apply to. */
    private function selectedDays(array $row): array
    {
        return collect($row['days'] ?? [])
            ->map(fn ($d) => (int) $d)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /** End-after-start and no overlapping slots on any shared day within one court's schedule. */
    private function validateSchedule(array $rows, int $courtIndex): void
    {
        $byDay = [];

        foreach ($rows as $j => $row) {
            // A 00:00 end means midnight (end-of-day); any other end must be later.
            if ($this->slotMinutes($row['end_time'], isEnd: true) <= $this->slotMinutes($row['start_time'])) {
                throw ValidationException::withMessages([
                    $this->fieldKey($courtIndex, $j, 'end_time') => 'End time must be after start time.',
                ]);
            }

            // The window must split evenly into slots of the chosen length.
            $slots = $this->buildSlots($row['start_time'], $row['end_time'], (float) ($row['hours'] ?? 0));

            if ($slots === null) {
                $label = $this->slotLengthLabel((float) ($row['hours'] ?? 0));

                throw ValidationException::withMessages([
                    $this->fieldKey($courtIndex, $j, 'hours') => "That time range doesn't divide evenly into {$label} slots.",
                ]);
            }

            // None of the generated slots may overlap another on the same day.
            foreach ($this->selectedDays($row) as $day) {
                foreach ($slots as $slot) {
                    $start = $this->slotMinutes($slot['start']);
                    $end = $this->slotMinutes($slot['end'], isEnd: true);

                    foreach ($byDay[$day] ?? [] as $existing) {
                        if ($start < $existing['end'] && $end > $existing['start']) {
                            throw ValidationException::withMessages([
                                $this->fieldKey($courtIndex, $j, 'start_time') => 'These slots overlap another slot on the same day.',
                            ]);
                        }
                    }

                    $byDay[$day][] = ['start' => $start, 'end' => $end];
                }
            }
        }
    }

    private function fieldKey(int $courtIndex, int $j, string $field): string
    {
        return $this->scheduleMode === 'same'
            ? "sessions.$j.$field"
            : "courtSessions.$courtIndex.$j.$field";
    }

    // ---------------------------------------------------- managing existing

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

    // ------------------------------------------------------ venue holidays

    /** Close the whole venue (every court) on a date — e.g. a public holiday. */
    public function addClosedDate(): void
    {
        $validated = $this->validate([
            'closed_date' => 'required|date|after_or_equal:today',
            'closed_reason' => 'nullable|string|max:255',
        ], [], ['closed_date' => 'date', 'closed_reason' => 'reason']);

        $date = Carbon::parse($validated['closed_date'])->toDateString();

        // A venue can only be closed once per date. The date cast serialises with a
        // time component, so match existing rows with whereDate (not an exact value).
        // Update the reason if the date is already closed.
        $existing = $this->venue->closedDates()->whereDate('date', $date)->first();

        if ($existing) {
            $existing->update(['reason' => $validated['closed_reason'] ?: null]);
        } else {
            $this->venue->closedDates()->create([
                'date' => $date,
                'reason' => $validated['closed_reason'] ?: null,
            ]);
        }

        $this->reset('closed_date', 'closed_reason');
    }

    public function removeClosedDate(int $closedDateId): void
    {
        $this->venue->closedDates()->whereKey($closedDateId)->delete();
    }

    public function render()
    {
        return view('livewire.owner.venues.courts', [
            'courts' => $this->venue->courts()->latest()->get(),
            'closedDates' => $this->venue->closedDates()->orderBy('date')->get(),
            'weekdays' => config('courtgo.weekdays'),
            'times' => $this->timeOptions(),
            'endTimes' => $this->endTimeOptions(),
            'previewNames' => $this->generatedNames(),
        ]);
    }
}
