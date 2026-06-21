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
        <div class="space-y-5 lg:sticky lg:top-6">
            {{-- Header: name, location, and quick facts at a glance --}}
            @php($range = $venue->priceRange())
            <div class="space-y-3">
                <div class="space-y-1">
                    <flux:heading size="xl" class="!text-2xl !font-bold tracking-tight">{{ $venue->name }}</flux:heading>
                    <x-venue-map-link :venue="$venue" class="text-sm" />
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if ($range)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">
                            <flux:icon name="banknotes" class="size-4" />
                            From RM {{ number_format(floor($range['min']), 0) }}
                        </span>
                    @endif

                    @if ($venue->opening_hours)
                        @if ($venue->isOpenNow())
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-700 dark:bg-green-950/50 dark:text-green-400">
                                <span class="size-1.5 rounded-full bg-green-500"></span> Open now
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1 text-sm font-medium text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                <span class="size-1.5 rounded-full bg-zinc-400"></span> Closed now
                            </span>
                        @endif
                    @endif

                    @foreach ($sports as $s)
                        <span class="inline-flex items-center rounded-full bg-zinc-100 px-3 py-1 text-sm text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $s }}</span>
                    @endforeach
                </div>

                @if ($venue->description)
                    <flux:text class="text-zinc-600 dark:text-zinc-400">{{ $venue->description }}</flux:text>
                @endif
            </div>

            {{-- Photo gallery --}}
            @if ($venue->photos->isNotEmpty())
                <div class="grid grid-cols-3 gap-2">
                    @foreach ($venue->photos as $photo)
                        <a href="{{ $photo->imageUrl() }}" target="_blank" rel="noopener noreferrer" wire:key="vp-{{ $photo->id }}"
                           class="group overflow-hidden rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                            <img src="{{ $photo->imageUrl() }}" alt="{{ $venue->name }} photo" loading="lazy"
                                 class="h-24 w-full object-cover transition duration-200 group-hover:scale-105" />
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Info card: scannable sections, each with an icon header --}}
            <div class="overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-800">
                {{-- Amenities --}}
                @php($amenityList = $venue->amenityLabels())
                @if (! empty($amenityList))
                    <section class="space-y-3 p-4">
                        <h3 class="flex items-center gap-2 text-sm font-semibold text-zinc-900 dark:text-white">
                            <flux:icon name="sparkles" class="size-4 text-blue-600" /> Amenities
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($amenityList as $amenity)
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                    <flux:icon :name="$amenity['icon']" class="size-3.5 text-zinc-500 dark:text-zinc-400" />
                                    {{ $amenity['label'] }}
                                </span>
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- Opening hours --}}
                @if ($venue->opening_hours)
                    <section class="space-y-2 p-4">
                        <div class="flex items-center justify-between">
                            <h3 class="flex items-center gap-2 text-sm font-semibold text-zinc-900 dark:text-white">
                                <flux:icon name="clock" class="size-4 text-blue-600" /> Opening hours
                            </h3>
                            @if ($venue->isOpenNow())
                                <span class="text-xs font-medium text-green-600 dark:text-green-400">Open now</span>
                            @else
                                <span class="text-xs font-medium text-zinc-400">Closed now</span>
                            @endif
                        </div>
                        <dl class="text-sm tabular-nums">
                            @foreach (config('courtgo.weekdays') as $dow => $label)
                                @php($h = $venue->opening_hours[$dow] ?? null)
                                @php($isToday = now()->dayOfWeek === $dow)
                                <div class="flex justify-between gap-4 rounded-md px-2 py-1 {{ $isToday ? 'bg-blue-50 font-semibold text-blue-700 dark:bg-blue-950/40 dark:text-blue-300' : '' }}">
                                    <dt class="{{ $isToday ? '' : 'text-zinc-500 dark:text-zinc-400' }}">{{ $label }}{{ $isToday ? ' · Today' : '' }}</dt>
                                    <dd>
                                        @if ($h && empty($h['closed']) && ! empty($h['open']) && ! empty($h['close']))
                                            {{ \Illuminate\Support\Carbon::parse($h['open'])->format('g:i A') }} – {{ \Illuminate\Support\Carbon::parse($h['close'])->format('g:i A') }}
                                        @else
                                            <span class="{{ $isToday ? '' : 'text-zinc-400 dark:text-zinc-500' }}">Closed</span>
                                        @endif
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                @endif

                {{-- Pricing --}}
                @if ($range)
                    <section class="space-y-1 p-4">
                        <h3 class="flex items-center gap-2 text-sm font-semibold text-zinc-900 dark:text-white">
                            <flux:icon name="banknotes" class="size-4 text-blue-600" /> Pricing
                        </h3>
                        <p class="text-lg font-bold tabular-nums text-zinc-900 dark:text-white">
                            RM {{ number_format(floor($range['min']), 0) }}@if (ceil($range['max']) != floor($range['min']))<span class="text-zinc-400"> – </span>RM {{ number_format(ceil($range['max']), 0) }}@endif
                            <span class="text-sm font-normal text-zinc-400">/ slot</span>
                        </p>
                        @if ($venue->pricing_note)
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $venue->pricing_note }}</p>
                        @endif
                    </section>
                @endif

                {{-- Getting there --}}
                <section class="space-y-2 p-4">
                    <h3 class="flex items-center gap-2 text-sm font-semibold text-zinc-900 dark:text-white">
                        <flux:icon name="map-pin" class="size-4 text-blue-600" /> Getting there
                    </h3>
                    <x-venue-directions :venue="$venue" />
                </section>

                {{-- Policy --}}
                @if ($venue->policy)
                    <section class="space-y-1.5 p-4">
                        <h3 class="flex items-center gap-2 text-sm font-semibold text-zinc-900 dark:text-white">
                            <flux:icon name="shield-check" class="size-4 text-blue-600" /> Venue policy
                        </h3>
                        <p class="whitespace-pre-line text-sm text-zinc-500 dark:text-zinc-400">{{ $venue->policy }}</p>
                    </section>
                @endif

                {{-- Contact --}}
                @php($hasContact = $venue->contact_phone || $venue->contact_whatsapp || $venue->contact_email || $venue->contact_website || $venue->contact_instagram || $venue->contact_facebook)
                @if ($hasContact)
                    <section class="space-y-2.5 p-4">
                        <h3 class="flex items-center gap-2 text-sm font-semibold text-zinc-900 dark:text-white">
                            <flux:icon name="phone" class="size-4 text-blue-600" /> Contact
                        </h3>
                        <x-venue-contact :venue="$venue" />
                    </section>
                @endif

                {{-- Venue layout --}}
                @if ($venue->layoutImageUrl())
                    <section class="space-y-2 p-4">
                        <h3 class="flex items-center gap-2 text-sm font-semibold text-zinc-900 dark:text-white">
                            <flux:icon name="map" class="size-4 text-blue-600" /> Venue layout
                        </h3>
                        <a href="{{ $venue->layoutImageUrl() }}" target="_blank" rel="noopener noreferrer"
                           class="block overflow-hidden rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                            <img src="{{ $venue->layoutImageUrl() }}" alt="{{ $venue->name }} layout" loading="lazy"
                                 class="max-h-48 w-full bg-zinc-50 object-contain dark:bg-zinc-900" />
                        </a>
                    </section>
                @endif
            </div>
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
