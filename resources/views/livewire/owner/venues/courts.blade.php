<div class="space-y-8 p-6 max-w-4xl mx-auto w-full">
    <div class="space-y-1">
        <flux:button size="sm" variant="ghost" :href="route('owner.venues.index')" wire:navigate icon="arrow-left">
            Back to venues
        </flux:button>
        <flux:heading size="xl">{{ $venue->name }} — Courts</flux:heading>
        <flux:text>{{ $venue->city }}, {{ $venue->state }}</flux:text>
    </div>

    @unless ($venue->isApproved())
        <flux:callout variant="warning" icon="clock">
            <flux:callout.heading>Pending admin approval</flux:callout.heading>
            <flux:callout.text>
                You can add courts and set their schedules now. This venue becomes visible and bookable to
                customers once an admin approves it.
            </flux:callout.text>
        </flux:callout>
    @endunless

    @if (! $showWizard)
        <flux:button variant="primary" icon="plus" wire:click="startWizard">Add courts</flux:button>
    @else
        {{-- ───────────────────────── Add-courts wizard ───────────────────────── --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 space-y-6">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Add courts</flux:heading>
                <flux:text class="text-sm text-zinc-500">Step {{ $step }} of 3</flux:text>
            </div>

            {{-- ── Step 1: sport, how many, naming ── --}}
            @if ($step === 1)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-searchable-select
                        label="Sport"
                        placeholder="Type or pick a sport"
                        :options="[...config('courtgo.sports'), 'Other']"
                        wire-model="sport"
                        :live="true"
                        :value="$sport" />
                    <flux:input type="number" min="1" max="50" wire:model.live="count" label="How many courts?" />
                </div>

                @if ($sport === 'Other')
                    <flux:input wire:model="customSport" label="Sport name" placeholder="Tell us the sport" />
                    <flux:text class="text-sm text-zinc-500">
                        Not a standard category? Add it here, or
                        <a class="underline" href="mailto:{{ config('courtgo.support_email') }}">contact us</a>
                        to have it added as an official category.
                    </flux:text>
                @endif

                <flux:radio.group wire:model.live="namingStyle" label="Name the courts" variant="segmented">
                    <flux:radio value="number" label="Court 1, 2, 3" />
                    <flux:radio value="letter" label="Court A, B, C" />
                </flux:radio.group>

                <div class="rounded-lg bg-zinc-50 dark:bg-zinc-900 p-4">
                    <flux:text class="text-sm font-medium">Preview</flux:text>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($previewNames as $name)
                            <flux:badge size="sm" color="blue">{{ $name }}</flux:badge>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-between">
                    <flux:button variant="ghost" wire:click="cancelWizard">Cancel</flux:button>
                    <flux:button variant="primary" wire:click="toStep2">Next: schedule</flux:button>
                </div>
            @endif

            {{-- ── Step 2: same or different schedule ── --}}
            @if ($step === 2)
                <flux:text>Do all {{ count($previewNames) }} courts run on the same weekly schedule?</flux:text>

                <flux:radio.group wire:model="scheduleMode">
                    <flux:radio value="same" label="Same schedule for every court" description="Set the weekly sessions once and apply to all." />
                    <flux:radio value="different" label="Different schedule per court" description="Set each court's sessions separately in the next step." />
                </flux:radio.group>

                <div class="flex justify-between">
                    <flux:button variant="ghost" wire:click="back">Back</flux:button>
                    <flux:button variant="primary" wire:click="toStep3">Next: sessions</flux:button>
                </div>
            @endif

            {{-- ── Step 3: build the slot schedule(s) ── --}}
            @if ($step === 3)
                <flux:text class="text-sm text-zinc-500">
                    For each row, tick the days, choose a From–Until window and a slot length — we split the window into
                    back-to-back slots (shown in the preview) for every day you tick. Any time without a slot can't be booked.
                </flux:text>

                @if ($scheduleMode === 'same')
                    <div class="space-y-3">
                        <flux:text class="font-medium">Slots (applied to every court)</flux:text>

                        @foreach ($sessions as $i => $row)
                            <div wire:key="session-{{ $i }}" class="space-y-3 rounded-lg border border-zinc-100 dark:border-zinc-800 p-3">
                                @include('partials.day-toggles', ['model' => "sessions.$i.days", 'weekdays' => $weekdays])
                                <div class="grid grid-cols-2 gap-2 lg:grid-cols-4 lg:items-end">
                                    <flux:select wire:model.live="sessions.{{ $i }}.start_time" label="From">
                                        @foreach ($times as $value => $label)<flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>@endforeach
                                    </flux:select>
                                    <flux:select wire:model.live="sessions.{{ $i }}.end_time" label="Until">
                                        @foreach ($endTimes as $value => $label)<flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>@endforeach
                                    </flux:select>
                                    <flux:select wire:model.live="sessions.{{ $i }}.hours" label="Slot length">
                                        @foreach (config('courtgo.slot_lengths') as $value => $label)<flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>@endforeach
                                    </flux:select>
                                    <flux:input type="number" min="0" step="0.01" wire:model.live="sessions.{{ $i }}.price" label="Price/slot (RM)" />
                                </div>
                                <x-window-preview :preview="$this->slotPreview($row['start_time'] ?? '', $row['end_time'] ?? '', $row['hours'] ?? null)" :hours="$row['hours'] ?? ''" />
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="space-y-6">
                        @foreach ($previewNames as $c => $courtName)
                            <div wire:key="court-sched-{{ $c }}" class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 space-y-3">
                                <flux:heading size="sm">{{ $courtName }}</flux:heading>

                                @foreach ($courtSessions[$c] ?? [] as $i => $row)
                                    <div wire:key="court-{{ $c }}-session-{{ $i }}" class="space-y-3 rounded-lg border border-zinc-100 dark:border-zinc-800 p-3">
                                        @include('partials.day-toggles', ['model' => "courtSessions.$c.$i.days", 'weekdays' => $weekdays])
                                        <div class="grid grid-cols-2 gap-2 lg:grid-cols-4 lg:items-end">
                                            <flux:select wire:model.live="courtSessions.{{ $c }}.{{ $i }}.start_time" label="From">
                                                @foreach ($times as $value => $label)<flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>@endforeach
                                            </flux:select>
                                            <flux:select wire:model.live="courtSessions.{{ $c }}.{{ $i }}.end_time" label="Until">
                                                @foreach ($endTimes as $value => $label)<flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>@endforeach
                                            </flux:select>
                                            <flux:select wire:model.live="courtSessions.{{ $c }}.{{ $i }}.hours" label="Slot length">
                                                @foreach (config('courtgo.slot_lengths') as $value => $label)<flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>@endforeach
                                            </flux:select>
                                            <flux:input type="number" min="0" step="0.01" wire:model.live="courtSessions.{{ $c }}.{{ $i }}.price" label="Price/slot (RM)" />
                                        </div>
                                        <x-window-preview :preview="$this->slotPreview($row['start_time'] ?? '', $row['end_time'] ?? '', $row['hours'] ?? null)" :hours="$row['hours'] ?? ''" />
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="flex justify-between">
                    <flux:button variant="ghost" wire:click="back">Back</flux:button>
                    <flux:button variant="primary" wire:click="create">Create {{ count($previewNames) }} court(s)</flux:button>
                </div>
            @endif
        </div>
    @endif

    {{-- ───────────────────────── Existing courts ───────────────────────── --}}
    <div class="space-y-3">
        <flux:heading size="lg">Courts ({{ $courts->count() }})</flux:heading>

        @if ($courts->isEmpty())
            <flux:text>No courts yet. Click “Add courts” to create some.</flux:text>
        @else
            <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-4 py-3 font-medium">Court</th>
                            <th class="px-4 py-3 font-medium">Sport</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($courts as $court)
                            <tr wire:key="court-{{ $court->id }}">
                                <td class="px-4 py-3 font-medium">{{ $court->name }}</td>
                                <td class="px-4 py-3 text-zinc-500">{{ $court->sport }}</td>
                                <td class="px-4 py-3">
                                    @if ($court->is_active)
                                        <flux:badge color="green" size="sm">Open</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">Closed</flux:badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <flux:button size="sm" variant="primary" :href="route('owner.courts.schedule', $court)" wire:navigate>
                                            Schedule
                                        </flux:button>
                                        <flux:button size="sm" variant="ghost" wire:click="toggleActive({{ $court->id }})">
                                            {{ $court->is_active ? 'Close' : 'Open' }}
                                        </flux:button>
                                        <flux:button
                                            size="sm"
                                            variant="danger"
                                            wire:click="deleteCourt({{ $court->id }})"
                                            wire:confirm="Delete this court?"
                                        >
                                            Delete
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ───────────────────────── Venue holidays ───────────────────────── --}}
    <div class="space-y-4">
        <flux:heading size="lg">Holidays / closed dates</flux:heading>
        <flux:text>Close the whole venue on a date (public holiday, maintenance). Every court here is unbookable on these dates.</flux:text>

        <form wire:submit="addClosedDate" class="flex flex-wrap items-end gap-3 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
            <flux:input wire:model="closed_date" type="date" label="Date to close" :min="now()->toDateString()" />
            <flux:input wire:model="closed_reason" label="Reason (optional)" placeholder="Public holiday" />
            <flux:button type="submit" variant="primary">Close this date</flux:button>
        </form>

        @if ($closedDates->isEmpty())
            <flux:text class="text-zinc-400">No closed dates.</flux:text>
        @else
            <div class="flex flex-wrap gap-2">
                @foreach ($closedDates as $closed)
                    <div class="flex items-center gap-2 rounded-lg bg-amber-100 dark:bg-amber-900/40 px-3 py-1.5 text-sm" wire:key="closed-{{ $closed->id }}">
                        <span>{{ $closed->date->format('D, d M Y') }}</span>
                        @if ($closed->reason)
                            <span class="text-zinc-500">({{ $closed->reason }})</span>
                        @endif
                        <button type="button" class="text-red-500 hover:text-red-700" wire:click="removeClosedDate({{ $closed->id }})" wire:confirm="Reopen this date?">&times;</button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
