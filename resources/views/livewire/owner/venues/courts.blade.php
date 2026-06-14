<div class="space-y-8 p-6 max-w-4xl mx-auto w-full">
    <div class="space-y-1">
        <flux:button size="sm" variant="ghost" :href="route('owner.venues.index')" wire:navigate icon="arrow-left">
            Back to venues
        </flux:button>
        <flux:heading size="xl">{{ $venue->name }} — Courts</flux:heading>
        <flux:text>{{ $venue->city }}, {{ $venue->state }}</flux:text>
    </div>

    {{-- Add court form --}}
    <form wire:submit="addCourt" class="space-y-4 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <flux:heading size="lg">Add a court</flux:heading>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <flux:input wire:model="name" label="Court name" placeholder="Court 1" />
            <flux:input wire:model="sport" label="Sport" placeholder="Badminton" />
        </div>

        <flux:switch wire:model="is_active" label="Open for booking" />

        <flux:button type="submit" variant="primary">Add court</flux:button>
    </form>

    {{-- Court list --}}
    <div class="space-y-3">
        <flux:heading size="lg">Courts ({{ $courts->count() }})</flux:heading>

        @if ($courts->isEmpty())
            <flux:text>No courts yet. Add your first one above.</flux:text>
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
</div>
