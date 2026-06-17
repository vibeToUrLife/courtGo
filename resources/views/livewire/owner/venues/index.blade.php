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
            <div>
                <flux:label>State</flux:label>
                <input list="venue-states" wire:model="state" placeholder="Type or pick a state" autocomplete="off"
                       class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                <datalist id="venue-states">
                    @foreach (config('courtgo.states') as $st)<option value="{{ $st }}"></option>@endforeach
                </datalist>
                <flux:error name="state" />
            </div>
        </div>

        {{-- One optional photo of the place, shown to customers. --}}
        <div>
            <flux:label>Photo (optional)</flux:label>
            <input type="file" wire:model="image" accept="image/*"
                   class="mt-1 block w-full text-sm text-zinc-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:text-white hover:file:bg-blue-700 dark:text-zinc-300" />
            <flux:error name="image" />

            <div wire:loading wire:target="image" class="mt-2 text-sm text-zinc-500">Uploading…</div>

            @if ($image)
                <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="mt-2 h-32 w-full max-w-xs rounded-lg object-cover" />
            @endif
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
                                    <div class="flex justify-end gap-2">
                                        <flux:button size="sm" variant="primary" :href="route('owner.venues.courts', $venue)" wire:navigate>
                                            Manage courts
                                        </flux:button>
                                        <flux:modal.trigger name="edit-photo">
                                            <flux:button size="sm" variant="ghost" icon="photo" wire:click="editPhoto({{ $venue->id }})">
                                                Photo
                                            </flux:button>
                                        </flux:modal.trigger>
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

    {{-- Change-photo modal (closes itself after a successful save) --}}
    <flux:modal name="edit-photo" class="max-w-lg" wire:close="cancelEditPhoto" x-on:photo-saved.window="$flux.modal('edit-photo').close()">
        <div class="space-y-4">
            <flux:heading size="lg">Change venue photo</flux:heading>

            <input type="file" wire:model="newImage" accept="image/*"
                   class="block w-full text-sm text-zinc-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:text-white hover:file:bg-blue-700 dark:text-zinc-300" />
            <flux:error name="newImage" />

            <div wire:loading wire:target="newImage" class="text-sm text-zinc-500">Uploading…</div>

            @if ($newImage)
                <img src="{{ $newImage->temporaryUrl() }}" alt="Preview" class="h-40 w-full rounded-lg object-cover" />
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" wire:click="cancelEditPhoto">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="updatePhoto">Save photo</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
