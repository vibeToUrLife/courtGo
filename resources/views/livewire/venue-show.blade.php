<div class="space-y-8 p-6 max-w-3xl mx-auto w-full">
    <div class="space-y-1">
        <flux:button size="sm" variant="ghost" :href="route('courts.browse')" wire:navigate icon="arrow-left">Back to search</flux:button>
        <flux:heading size="xl">{{ $venue->name }}</flux:heading>
        <flux:text>📍 {{ $venue->address }}, {{ $venue->city }}, {{ $venue->state }}</flux:text>
        @if ($venue->description)
            <flux:text class="text-zinc-500">{{ $venue->description }}</flux:text>
        @endif
    </div>

    @if (session('booking_error'))
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.text>{{ session('booking_error') }}</flux:callout.text>
        </flux:callout>
    @endif

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 space-y-5">
        {{-- Step 1: pick a date --}}
        <flux:input type="date" wire:model.live="date" label="1. Choose a date" :min="now()->toDateString()" />

        {{-- Step 2: pick a time, then an available court --}}
        <div>
            <flux:heading size="lg">2. Pick a time &amp; an available court</flux:heading>

            @if ($timeSlots->isEmpty())
                <flux:text class="text-zinc-400 mt-2">No times available on this date. Try another day.</flux:text>
            @else
                <div class="mt-3 space-y-4">
                    @foreach ($timeSlots as $label => $offers)
                        @php($first = $offers->first()['session'])
                        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                            <div class="font-medium">
                                {{ \Illuminate\Support\Carbon::parse($first->start_time)->format('g:i A') }}
                                – {{ \Illuminate\Support\Carbon::parse($first->end_time)->format('g:i A') }}
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($offers as $offer)
                                    <flux:button size="sm" variant="primary" wire:key="slot-{{ $offer['court']->id }}-{{ $offer['session']->id }}"
                                        href="{{ route('bookings.checkout', ['court' => $offer['court'], 'session' => $offer['session'], 'date' => $date]) }}">
                                        {{ $offer['court']->name }} · RM {{ number_format($offer['session']->price, 2) }}
                                    </flux:button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
