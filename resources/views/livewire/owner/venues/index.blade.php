<div class="space-y-6 p-6 max-w-4xl mx-auto w-full">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="space-y-1">
            <flux:heading size="xl">My Venues</flux:heading>
            <flux:text>The places you run. Each venue can hold several courts.</flux:text>
        </div>
        <flux:button variant="primary" :icon="$showForm ? 'x-mark' : 'plus'" wire:click="toggleForm">
            {{ $showForm ? 'Cancel' : 'Add venue' }}
        </flux:button>
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle">
            <flux:callout.text>{{ session('status') }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- Create venue form — collapsed until "Add venue" is clicked --}}
    @if ($showForm)
        <form wire:submit="save" class="space-y-4 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
            <flux:heading size="lg">Add a new venue</flux:heading>

            <flux:input wire:model="name" label="Venue name" placeholder="Sunway Badminton Hall" autocomplete="new-password" data-no-autofill />
            <flux:textarea wire:model="description" label="Description (optional)" placeholder="A short description of this place" />
            <flux:input wire:model="address" label="Address" placeholder="Jalan PJS 11, Bandar Sunway" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="city" label="City" placeholder="Subang Jaya" autocomplete="new-password" data-no-autofill />
                <x-searchable-select label="State" placeholder="Type or pick a state"
                    :options="config('courtgo.states')" wire-model="state" :value="$state" wire:key="venue-state-select" />
            </div>

            <flux:text class="text-sm text-zinc-500">You can add photos after creating the venue.</flux:text>

            <flux:button type="submit" variant="primary">Add venue</flux:button>
        </form>
    @endif

    {{-- Venue list — hidden while the add-venue form is open --}}
    @unless ($showForm)
    <div class="space-y-3">
        <flux:heading size="lg">Your venues ({{ $venues->count() }})</flux:heading>

        @if ($venues->isEmpty())
            <flux:text>You haven't added any venues yet. Add your first one above.</flux:text>
        @else
            <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-4 py-3 font-medium">Venue</th>
                            <th class="px-4 py-3 font-medium">Location</th>
                            <th class="px-4 py-3 font-medium">Courts</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($venues as $venue)
                            <tr wire:key="venue-{{ $venue->id }}">
                                <td class="px-4 py-3 font-medium">
                                    <div class="flex items-center gap-3">
                                        @if ($venue->imageUrl())
                                            <img src="{{ $venue->imageUrl() }}" alt="" class="h-10 w-10 rounded object-cover" />
                                        @endif
                                        {{ $venue->name }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-zinc-500">{{ $venue->city }}, {{ $venue->state }}</td>
                                <td class="px-4 py-3 text-zinc-500">{{ $venue->courts_count }}</td>
                                <td class="px-4 py-3">
                                    @if ($venue->isApproved())
                                        <flux:badge color="green" size="sm">Approved</flux:badge>
                                    @elseif ($venue->needsChanges())
                                        <a href="{{ route('owner.venues.profile', $venue) }}#verification" wire:navigate class="inline-block">
                                            <flux:badge color="red" size="sm">Rejected — see reason</flux:badge>
                                        </a>
                                    @elseif (! $venue->hasAllDocuments())
                                        <a href="{{ route('owner.venues.profile', $venue) }}#verification" wire:navigate class="inline-block">
                                            <flux:badge color="red" size="sm">Upload documents</flux:badge>
                                        </a>
                                    @else
                                        <flux:badge color="amber" size="sm">Pending approval</flux:badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <flux:button size="sm" variant="primary" :href="route('owner.venues.courts', $venue)" wire:navigate>
                                            Manage courts
                                        </flux:button>
                                        <flux:button size="sm" variant="ghost" icon="identification" :href="route('owner.venues.profile', $venue)" wire:navigate>
                                            Profile
                                        </flux:button>
                                        <flux:button
                                            size="sm"
                                            variant="danger"
                                            wire:click="delete({{ $venue->id }})"
                                            wire:confirm="Delete this venue and all of its courts? This cannot be undone."
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
    @endunless
</div>
