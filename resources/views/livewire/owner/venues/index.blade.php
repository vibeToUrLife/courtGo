<div class="space-y-8 p-6 max-w-4xl mx-auto w-full">
    <flux:heading size="xl">My Venues</flux:heading>
    <flux:text>Add the places you run. Each venue can hold several courts.</flux:text>

    {{-- Create venue form --}}
    <form wire:submit="save" class="space-y-4 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <flux:heading size="lg">Add a new venue</flux:heading>

        <flux:input wire:model="name" label="Venue name" placeholder="Sunway Badminton Hall" />
        <flux:textarea wire:model="description" label="Description (optional)" placeholder="A short description of this place" />
        <flux:input wire:model="address" label="Address" placeholder="Jalan PJS 11, Bandar Sunway" />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <flux:input wire:model="city" label="City" placeholder="Subang Jaya" />
            <flux:input wire:model="state" label="State" placeholder="Selangor" />
        </div>

        <flux:button type="submit" variant="primary">Add venue</flux:button>
    </form>

    {{-- Venue list --}}
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
                            <th class="px-4 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($venues as $venue)
                            <tr wire:key="venue-{{ $venue->id }}">
                                <td class="px-4 py-3 font-medium">{{ $venue->name }}</td>
                                <td class="px-4 py-3 text-zinc-500">{{ $venue->city }}, {{ $venue->state }}</td>
                                <td class="px-4 py-3 text-zinc-500">{{ $venue->courts_count }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <flux:button size="sm" variant="primary" :href="route('owner.venues.courts', $venue)" wire:navigate>
                                            Manage courts
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
</div>
