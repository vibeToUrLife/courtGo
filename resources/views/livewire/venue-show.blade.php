<div class="space-y-8 p-6 max-w-3xl mx-auto w-full">
    <div class="space-y-1">
        <flux:button size="sm" variant="ghost" :href="route('courts.browse')" wire:navigate icon="arrow-left">Back to search</flux:button>

        @if ($venue->imageUrl())
            <img src="{{ $venue->imageUrl() }}" alt="{{ $venue->name }}" class="mb-3 h-56 w-full rounded-xl object-cover" />
        @endif

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

        {{-- Step 2: pick a time + an available court from the calendar grid --}}
        <div>
            <flux:heading size="lg">2. Pick a time &amp; an available court</flux:heading>

            @if (empty($timeColumns) || $courts->isEmpty())
                <flux:text class="text-zinc-400 mt-2">No times available on this date. Try another day.</flux:text>
            @else
                <flux:text class="text-sm text-zinc-500 mt-1">Click an available slot to book it.</flux:text>

                {{-- Calendar: courts down the left, the owner's time slots across the top. --}}
                <div class="mt-3 overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <table class="border-collapse text-sm">
                        <thead>
                            <tr class="bg-zinc-50 dark:bg-zinc-900">
                                <th class="sticky left-0 z-10 bg-zinc-50 px-3 py-2 text-left font-medium dark:bg-zinc-900">Court</th>
                                @foreach ($timeColumns as $col)
                                    <th class="whitespace-nowrap px-3 py-2 text-center font-medium" title="{{ $col['display'] }}">{{ $col['start'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($courts as $court)
                                <tr class="border-t border-zinc-100 dark:border-zinc-800">
                                    <td class="sticky left-0 z-10 whitespace-nowrap bg-white px-3 py-2 font-medium dark:bg-zinc-950">
                                        {{ $court->name }}
                                    </td>
                                    @foreach ($timeColumns as $col)
                                        @php($cell = $grid[$court->id][$col['key']] ?? null)
                                        <td class="px-1.5 py-1.5 text-center align-middle">
                                            @if ($cell && $cell['state'] === 'available')
                                                <a wire:key="slot-{{ $court->id }}-{{ $cell['session']->id }}"
                                                   href="{{ route('bookings.checkout', ['court' => $court, 'session' => $cell['session'], 'date' => $date]) }}"
                                                   title="{{ $col['display'] }}"
                                                   class="inline-block w-full min-w-20 rounded-lg bg-blue-600 px-3 py-2 text-xs font-medium text-white hover:bg-blue-700">
                                                    RM {{ number_format($cell['session']->price, 0) }}
                                                </a>
                                            @elseif ($cell)
                                                <span class="inline-block w-full min-w-20 rounded-lg bg-zinc-100 px-3 py-2 text-xs text-zinc-400 line-through dark:bg-zinc-800">Booked</span>
                                            @else
                                                <span class="text-zinc-300 dark:text-zinc-600">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
