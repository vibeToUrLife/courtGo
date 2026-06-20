<div class="space-y-8 p-6 max-w-5xl mx-auto w-full">
    <flux:button size="sm" variant="ghost" :href="route('home')" wire:navigate icon="arrow-left">Back to homepage</flux:button>

    <div class="space-y-1">
        <flux:heading size="xl">Find a Court</flux:heading>
        <flux:text>Browse places you can book, then pick a court inside.</flux:text>
    </div>

    {{-- Search --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <flux:input wire:model.live.debounce.300ms="name" label="Place name" placeholder="e.g. Sunway Hall" autocomplete="new-password" data-no-autofill />
        <x-searchable-select label="Sport" placeholder="Any sport" :options="$sports" wire-model="sport" :live="true" :value="$sport" />
        <x-searchable-select label="State" placeholder="Any state" :options="$states" wire-model="state" :live="true" :value="$state" />
        <flux:input wire:model.live.debounce.300ms="city" label="City" placeholder="e.g. Subang Jaya" autocomplete="new-password" data-no-autofill />
        <flux:input type="date" wire:model.live="date" label="Date" :min="now()->toDateString()" />
    </div>

    {{-- Venues (places) --}}
    @if ($venues->isEmpty())
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-8 text-center">
            <flux:text>No places found. Try a different search.</flux:text>
        </div>
    @else
        <flux:text class="text-sm">
            Showing availability for <strong>{{ $displayDate->isoFormat('ddd, D MMM') }}</strong>
        </flux:text>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($venues as $venue)
                @php($summary = $summaries[$venue->id])
                <a href="{{ route('venues.show', ['venue' => $venue]) }}"
                   wire:navigate wire:key="venue-{{ $venue->id }}"
                   class="flex flex-col overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 hover:border-blue-400 transition">
                    @if ($venue->imageUrl())
                        <img src="{{ $venue->imageUrl() }}" alt="{{ $venue->name }}" class="h-40 w-full object-cover" />
                    @else
                        <div class="flex h-40 w-full items-center justify-center bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                            <flux:icon name="photo" class="size-8" />
                        </div>
                    @endif

                    <div class="flex flex-1 flex-col p-5">
                        <div class="font-semibold text-lg">{{ $venue->name }}</div>
                        <div class="text-sm text-zinc-500">📍 {{ $venue->city }}, {{ $venue->state }}</div>

                        <div class="mt-3 flex flex-wrap gap-1">
                            @foreach ($venue->courts->pluck('sport')->unique() as $sport)
                                <flux:badge size="sm" color="blue">{{ $sport }}</flux:badge>
                            @endforeach
                        </div>

                        <div class="mt-3 flex items-center justify-between text-sm">
                            @if ($summary['price_from'] !== null)
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">from RM{{ number_format($summary['price_from'], 0) }}</span>
                            @else
                                <span></span>
                            @endif

                            @if ($summary['available'] > 0)
                                <span class="text-emerald-600 dark:text-emerald-400">{{ $summary['available'] }} session(s) available</span>
                            @else
                                <span class="text-zinc-400">Fully booked</span>
                            @endif
                        </div>

                        <div class="mt-4">
                            <flux:button size="sm" variant="primary">View courts</flux:button>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div>{{ $venues->links() }}</div>
    @endif
</div>
