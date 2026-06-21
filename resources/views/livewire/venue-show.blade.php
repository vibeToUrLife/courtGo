<div class="p-6 max-w-6xl mx-auto w-full space-y-4">
    <flux:button size="sm" variant="ghost" :href="route('courts.browse')" wire:navigate icon="arrow-left">Back to search</flux:button>

    @if ($venue->announcementVisible())
        <flux:callout variant="warning" icon="megaphone">
            <flux:callout.text>{{ $venue->announcement }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- Full-width cover banner --}}
    @if ($venue->imageUrl())
        <img src="{{ $venue->imageUrl() }}" alt="{{ $venue->name }}" class="h-52 w-full rounded-2xl object-cover sm:h-72" />
    @endif

    {{-- Two columns on large screens: venue details on the left, booking on the
         right. Stacks back to a single top-down column on small screens. --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 lg:items-start">
        {{-- LEFT: venue details (sticks while the calendar on the right scrolls) --}}
        <div class="space-y-3 lg:sticky lg:top-6">
            <flux:heading size="xl">{{ $venue->name }}</flux:heading>
            <x-venue-map-link :venue="$venue" class="text-sm" />
            @if ($venue->description)
                <flux:text class="text-zinc-500 dark:text-zinc-400">{{ $venue->description }}</flux:text>
            @endif

            {{-- Photo gallery --}}
            @if ($venue->photos->isNotEmpty())
                <div class="grid grid-cols-3 gap-2">
                    @foreach ($venue->photos as $photo)
                        <a href="{{ $photo->imageUrl() }}" target="_blank" rel="noopener noreferrer" wire:key="vp-{{ $photo->id }}">
                            <img src="{{ $photo->imageUrl() }}" alt="" class="h-20 w-full rounded-lg object-cover" />
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Amenities --}}
            @php($amenityList = $venue->amenityLabels())
            @if (! empty($amenityList))
                <div class="flex flex-wrap gap-2">
                    @foreach ($amenityList as $amenity)
                        <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-3 py-1 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                            <flux:icon :name="$amenity['icon']" class="size-3.5" />
                            {{ $amenity['label'] }}
                        </span>
                    @endforeach
                </div>
            @endif

            {{-- Directions --}}
            <x-venue-directions :venue="$venue" />

            {{-- Opening hours --}}
            @if ($venue->opening_hours)
                <div class="space-y-1 text-sm">
                    <flux:text class="font-medium">Opening hours</flux:text>
                    @foreach (config('courtgo.weekdays') as $dow => $label)
                        @php($h = $venue->opening_hours[$dow] ?? null)
                        <div class="flex justify-between gap-4">
                            <span class="text-zinc-500">{{ $label }}</span>
                            <span>
                                @if ($h && empty($h['closed']) && ! empty($h['open']) && ! empty($h['close']))
                                    {{ \Illuminate\Support\Carbon::parse($h['open'])->format('g:i A') }} – {{ \Illuminate\Support\Carbon::parse($h['close'])->format('g:i A') }}
                                @else
                                    Closed
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Pricing --}}
            @php($range = $venue->priceRange())
            @if ($range)
                <div class="text-sm">
                    <flux:text class="font-medium">Pricing</flux:text>
                    <div>RM {{ number_format(floor($range['min']), 0) }}@if (ceil($range['max']) != floor($range['min'])) – RM {{ number_format(ceil($range['max']), 0) }}@endif <span class="text-zinc-400">per slot</span></div>
                    @if ($venue->pricing_note)
                        <div class="text-zinc-500">{{ $venue->pricing_note }}</div>
                    @endif
                </div>
            @endif

            {{-- Policy --}}
            @if ($venue->policy)
                <div class="text-sm">
                    <flux:text class="font-medium">Policy</flux:text>
                    <div class="whitespace-pre-line text-zinc-500">{{ $venue->policy }}</div>
                </div>
            @endif

            {{-- Contact --}}
            @php($hasContact = $venue->contact_phone || $venue->contact_whatsapp || $venue->contact_email || $venue->contact_website || $venue->contact_instagram || $venue->contact_facebook)
            @if ($hasContact)
                <div class="space-y-2 text-sm">
                    <flux:text class="font-medium">Contact</flux:text>
                    <x-venue-contact :venue="$venue" />
                </div>
            @endif

            {{-- Venue layout --}}
            @if ($venue->layoutImageUrl())
                <div class="text-sm">
                    <flux:text class="font-medium">Venue layout</flux:text>
                    <a href="{{ $venue->layoutImageUrl() }}" target="_blank" rel="noopener noreferrer">
                        <img src="{{ $venue->layoutImageUrl() }}" alt="" class="mt-1 max-h-48 w-full rounded-lg object-contain" />
                    </a>
                </div>
            @endif
        </div>

        {{-- RIGHT: choose a sport, date and time. min-w-0 lets this 2/3 track
             shrink so the wide time-grid scrolls inside its own box instead of
             stretching the whole page. --}}
        <div class="space-y-5 lg:col-span-2 min-w-0">
            @if (session('booking_error'))
                <flux:callout variant="danger" icon="exclamation-triangle">
                    <flux:callout.text>{{ session('booking_error') }}</flux:callout.text>
                </flux:callout>
            @endif

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 space-y-5">
                {{-- Sport picker — only when this venue offers more than one sport. --}}
                @if (count($sports) > 1)
                    <div>
                        <flux:text class="text-sm font-medium">Sport</flux:text>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($sports as $s)
                                <flux:button size="sm" :variant="$s === $sport ? 'primary' : 'ghost'" wire:click="selectSport('{{ $s }}')">
                                    {{ $s }}
                                </flux:button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Step 1: pick a date --}}
                <flux:input type="date" wire:model.live="date" label="1. Choose a date" :min="now()->toDateString()" />

                {{-- Step 2: pick a time + an available court (only after a date is chosen) --}}
                <div>
                    <flux:heading size="lg">2. Pick a time &amp; an available court</flux:heading>

                    @if ($date === '')
                        <flux:text class="text-zinc-400 mt-2">Choose a date above to see the available times.</flux:text>
                    @elseif (empty($timeColumns) || $courts->isEmpty())
                        <flux:text class="text-zinc-400 mt-2">No times available on this date. Try another day.</flux:text>
                    @else
                        <flux:text class="text-sm text-zinc-500 mt-1">Tap the slots you want — then book and pay for them all at once.</flux:text>

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
                                                        @php($slotKey = $court->id.'-'.$cell['session']->id)
                                                        @php($isSelected = in_array($slotKey, $selected, true))
                                                        <button type="button" wire:key="slot-{{ $slotKey }}"
                                                           wire:click="toggleSlot({{ $court->id }}, {{ $cell['session']->id }})"
                                                           title="{{ $col['display'] }}"
                                                           aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                                                           aria-label="{{ $court->name }}, {{ $col['display'] }}, RM {{ number_format($cell['session']->price, 0) }}{{ $isSelected ? ', selected' : '' }}"
                                                           class="inline-block w-full min-w-20 rounded-lg px-3 py-2 text-xs font-medium text-white {{ $isSelected ? 'bg-blue-800 ring-2 ring-blue-400 ring-offset-1 dark:ring-offset-zinc-950' : 'bg-blue-600 hover:bg-blue-700' }}">
                                                            {{ $isSelected ? '✓ ' : '' }}RM {{ number_format($cell['session']->price, 0) }}
                                                        </button>
                                                    @elseif ($cell)
                                                        <span aria-label="{{ $court->name }}, {{ $col['display'] }}, unavailable"
                                                              class="inline-block w-full min-w-20 rounded-lg bg-zinc-100 px-3 py-2 text-xs text-zinc-500 line-through dark:bg-zinc-800 dark:text-zinc-400">Booked</span>
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

                        {{-- Selected slots → pay for all at once --}}
                        @if ($selectedSummary->isNotEmpty())
                            <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30">
                                <flux:heading size="sm">Selected slots ({{ $selectedSummary->count() }})</flux:heading>
                                <ul class="mt-2 space-y-1 text-sm">
                                    @foreach ($selectedSummary as $item)
                                        <li class="flex justify-between gap-4">
                                            <span>{{ $item['court'] }} · {{ $item['time'] }}</span>
                                            <span class="text-zinc-600 dark:text-zinc-300">RM {{ number_format($item['price'], 2) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="mt-3 flex flex-wrap items-center justify-between gap-3 border-t border-blue-200 pt-3 dark:border-blue-900">
                                    <span class="font-semibold">Total: RM {{ number_format($selectedTotal, 2) }}</span>
                                    <flux:button variant="primary" wire:click="checkout" wire:loading.attr="disabled" wire:target="checkout">
                                        Book &amp; pay
                                    </flux:button>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
