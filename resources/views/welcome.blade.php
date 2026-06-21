<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">

        {{-- Top navigation (shared with the customer-facing pages) --}}
        <x-site-header />

        {{-- Hero banner: an image band with the headline overlaid, and the search
             card pulled up to overlap its lower edge. --}}
        <section>
            <div class="relative isolate overflow-hidden">
                <img src="{{ asset('images/hero-banner.svg') }}" alt=""
                     class="absolute inset-0 -z-20 h-full w-full object-cover" />
                {{-- Scrim for legible white text over any banner --}}
                <div class="absolute inset-0 -z-10 bg-gradient-to-b from-blue-950/70 via-blue-900/55 to-blue-950/85"></div>

                <div class="mx-auto max-w-6xl px-6 pb-24 pt-16 text-center sm:pb-32 sm:pt-24">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/30 bg-white/10 px-3 py-1 text-sm text-white backdrop-blur">
                        <span class="size-2 rounded-full bg-emerald-400"></span>
                        Sports court booking, made simple
                    </span>

                    <h1 class="mx-auto mt-6 max-w-3xl text-4xl font-bold tracking-tight text-white sm:text-6xl">
                        Book your next game in seconds.
                    </h1>

                    <p class="mx-auto mt-6 max-w-2xl text-lg text-blue-100">
                        Find badminton, futsal, tennis and more across Malaysia. Pick a time, book a court,
                        and pay securely — no phone calls, no waiting.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm font-medium text-white/90">
                        <span class="inline-flex items-center gap-2"><flux:icon name="shield-check" class="size-5 text-emerald-300" /> Secure payment</span>
                        <span class="inline-flex items-center gap-2"><flux:icon name="bolt" class="size-5 text-emerald-300" /> Instant confirmation</span>
                        <span class="inline-flex items-center gap-2"><flux:icon name="credit-card" class="size-5 text-emerald-300" /> Card or FPX</span>
                    </div>
                </div>
            </div>

            {{-- Search bar: sport + location + date → Find a Court --}}
            <div class="relative z-10 mx-auto -mt-12 mb-4 max-w-4xl px-6">
                <form method="GET" action="{{ route('courts.browse') }}"
                      class="grid grid-cols-1 gap-3 rounded-2xl border border-zinc-200 bg-white p-4 text-left shadow-xl sm:grid-cols-2 sm:items-end lg:grid-cols-[1fr_1fr_1fr_1fr_auto] dark:border-zinc-800 dark:bg-zinc-900">
                    <div>
                        <span class="mb-1 block text-xs font-medium text-zinc-500">Sport</span>
                        <x-searchable-select name="sport" placeholder="Any sport" :options="$sports" />
                    </div>

                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-zinc-500">City</span>
                        <input type="text" name="city" placeholder="e.g. Subang Jaya" autocomplete="new-password" data-no-autofill
                               class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                    </label>

                    <div>
                        <span class="mb-1 block text-xs font-medium text-zinc-500">State</span>
                        <x-searchable-select name="state" placeholder="Any state" :options="$states" />
                    </div>

                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-zinc-500">Date</span>
                        <input type="date" name="date" min="{{ now()->toDateString() }}"
                               class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                    </label>

                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-6 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                        <flux:icon name="magnifying-glass" class="size-4" /> Find a court
                    </button>
                </form>

                @guest
                    <p class="mt-3 text-center text-xs text-zinc-500">Log in or sign up to see live availability and book your slot.</p>
                    <p class="mt-4 text-center text-sm text-zinc-500">
                        Own a venue?
                        <a href="{{ route('for-business') }}" class="font-medium text-blue-600 hover:underline dark:text-blue-400">List your venue →</a>
                    </p>
                @endguest
            </div>
        </section>

        {{-- Browse by sport — popular shortcuts, with a "Show all" toggle for every category. --}}
        @if ($sports->isNotEmpty())
            <section x-data="{ showAll: false }" class="mx-auto max-w-6xl px-6 pb-8">
                <h2 class="text-center text-sm font-semibold uppercase tracking-wide text-zinc-500">Browse by sport</h2>

                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    @foreach ($sports as $s)
                        <a href="{{ route('courts.browse', ['sport' => $s]) }}"
                           @if (! $popularSports->contains($s)) x-show="showAll" x-cloak @endif
                           class="flex items-center gap-2 rounded-full border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 hover:border-blue-400 hover:text-blue-600 dark:border-zinc-800 dark:text-zinc-300 dark:hover:text-blue-400">
                            <x-sport-icon :sport="$s" class="size-5 shrink-0 text-blue-600 dark:text-blue-400" />
                            {{ $s }}
                        </a>
                    @endforeach
                </div>

                <div class="mt-6 text-center">
                    <button type="button" x-on:click="showAll = ! showAll"
                            class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
                            x-text="showAll ? '{{ __('Show fewer') }}' : '{{ __('Show all sports') }}'"></button>
                </div>
            </section>
        @endif

        {{-- How it works (for players) --}}
        <section class="border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900/40">
            <div class="mx-auto max-w-6xl px-6 py-20">
                <h2 class="text-center text-3xl font-bold">How it works</h2>
                <p class="mt-3 text-center text-zinc-600 dark:text-zinc-400">Three steps from "I want to play" to "we're on the court."</p>

                <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-3">
                    @foreach ([
                        ['icon' => 'magnifying-glass', 'title' => '1. Find a venue', 'desc' => 'Browse courts near you and filter by sport and city.'],
                        ['icon' => 'calendar-days', 'title' => '2. Pick your time', 'desc' => 'See real-time availability and choose a session that fits.'],
                        ['icon' => 'credit-card', 'title' => '3. Book & pay', 'desc' => 'Pay securely online — your slot is locked in instantly.'],
                    ] as $step)
                        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                            <span class="flex size-11 items-center justify-center rounded-xl bg-blue-600/10 text-blue-600 dark:text-blue-400">
                                <flux:icon :name="$step['icon']" class="size-6" />
                            </span>
                            <h3 class="mt-4 text-lg font-semibold">{{ $step['title'] }}</h3>
                            <p class="mt-2 text-zinc-600 dark:text-zinc-400">{{ $step['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- For court owners --}}
        <section class="mx-auto max-w-6xl px-6 py-20">
            <div class="grid grid-cols-1 items-center gap-10 lg:grid-cols-2">
                <div>
                    <span class="text-sm font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">For court owners</span>
                    <h2 class="mt-3 text-3xl font-bold">Fill your courts. Keep 100% of every booking.</h2>
                    <p class="mt-4 text-zinc-600 dark:text-zinc-400">
                        List your venue, set your weekly schedule and prices, and let customers book and pay online.
                        We charge a simple monthly subscription — never a cut of your bookings.
                    </p>
                    <div class="mt-8">
                        <a href="{{ route('for-business') }}"
                           class="inline-block rounded-lg bg-blue-600 px-6 py-3 text-base font-medium text-white hover:bg-blue-700">
                            List your venue
                        </a>
                    </div>
                </div>

                <ul class="space-y-4">
                    @foreach ([
                        ['title' => '0% booking commission', 'desc' => 'Payments go straight to your bank — we never take a percentage.'],
                        ['title' => 'Your schedule, your prices', 'desc' => 'Set recurring weekly sessions, each with its own length and price.'],
                        ['title' => 'Secure payouts via Stripe', 'desc' => 'Connect your bank once and get paid automatically for every booking.'],
                    ] as $benefit)
                        <li class="flex gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                            <flux:icon name="check-circle" class="mt-0.5 size-6 shrink-0 text-emerald-500" />
                            <div>
                                <div class="font-semibold">{{ $benefit['title'] }}</div>
                                <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $benefit['desc'] }}</div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- Closing call to action --}}
        <section class="border-t border-zinc-200 dark:border-zinc-800">
            <div class="mx-auto max-w-6xl px-6 py-16">
                <div class="overflow-hidden rounded-3xl bg-gradient-to-br from-blue-600 to-blue-800 px-8 py-14 text-center shadow-lg">
                    <h2 class="text-3xl font-bold text-white sm:text-4xl">Ready to play?</h2>
                    <p class="mx-auto mt-3 max-w-xl text-blue-100">Find an available court near you and lock in your slot in seconds.</p>
                    <div class="mt-8">
                        <a href="{{ route('courts.browse') }}" wire:navigate
                           class="inline-flex items-center gap-2 rounded-lg bg-white px-6 py-3 text-base font-semibold text-blue-700 transition hover:bg-blue-50">
                            <flux:icon name="magnifying-glass" class="size-5" /> Find a court
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- Footer --}}
        <x-site-footer />

        @fluxScripts
    </body>
</html>
