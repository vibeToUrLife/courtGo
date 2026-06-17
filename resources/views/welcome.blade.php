<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">

        {{-- Top navigation --}}
        <header class="border-b border-zinc-200 dark:border-zinc-800">
            <nav class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-semibold">
                    <span class="flex size-8 items-center justify-center rounded-md bg-blue-600 text-white">
                        <flux:icon name="map-pin" class="size-5" />
                    </span>
                    {{ config('app.name') }}
                </a>

                <div class="flex items-center gap-1 sm:gap-3">
                    @guest
                        <a href="{{ route('for-business') }}"
                           class="hidden rounded-lg px-4 py-2 text-sm font-medium text-zinc-700 hover:text-zinc-900 sm:inline-block dark:text-zinc-300 dark:hover:text-white">
                            For owners
                        </a>
                    @endguest
                    @auth
                        @if (auth()->user()->role === \App\Enums\UserRole::Customer)
                            <a href="{{ route('bookings.mine') }}" wire:navigate
                               class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                My bookings
                            </a>
                        @else
                            <a href="{{ route('dashboard') }}" wire:navigate
                               class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                Go to dashboard
                            </a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" wire:navigate
                           class="rounded-lg px-4 py-2 text-sm font-medium text-zinc-700 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white">
                            Log in
                        </a>
                        <a href="{{ route('register') }}" wire:navigate
                           class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                            Get started
                        </a>
                    @endauth
                </div>
            </nav>
        </header>

        {{-- Hero --}}
        <section class="mx-auto max-w-6xl px-6 py-16 text-center sm:py-24">
            <span class="inline-flex items-center gap-2 rounded-full border border-zinc-200 px-3 py-1 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
                <span class="size-2 rounded-full bg-emerald-500"></span>
                Sports court booking, made simple
            </span>

            <h1 class="mx-auto mt-6 max-w-3xl text-4xl font-bold tracking-tight sm:text-6xl">
                Book your next game in seconds.
            </h1>

            <p class="mx-auto mt-6 max-w-2xl text-lg text-zinc-600 dark:text-zinc-400">
                Find badminton, futsal, tennis and more across Malaysia. Pick a time, book a court,
                and pay securely — no phone calls, no waiting.
            </p>

            {{-- Search bar: sport + location + date → Find a Court --}}
            <form method="GET" action="{{ route('courts.browse') }}"
                  class="mx-auto mt-10 grid max-w-4xl grid-cols-1 gap-3 rounded-2xl border border-zinc-200 bg-white p-4 text-left shadow-sm sm:grid-cols-2 sm:items-end lg:grid-cols-[1fr_1fr_1fr_1fr_auto] dark:border-zinc-800 dark:bg-zinc-900">
                <div>
                    <span class="mb-1 block text-xs font-medium text-zinc-500">Sport</span>
                    <x-searchable-select name="sport" placeholder="Any sport" :options="$sports" />
                </div>

                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-zinc-500">City</span>
                    <input type="text" name="city" placeholder="e.g. Subang Jaya"
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
                        class="rounded-lg bg-blue-600 px-6 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    Find a court
                </button>
            </form>

            @guest
                <p class="mt-3 text-xs text-zinc-500">Log in or sign up to see live availability and book your slot.</p>
                <p class="mt-4 text-sm text-zinc-500">
                    Own a venue?
                    <a href="{{ route('for-business') }}" class="font-medium text-blue-600 hover:underline dark:text-blue-400">List your venue →</a>
                </p>
            @endguest
        </section>

        {{-- Browse by sport — a few popular shortcuts; the full list is searchable in Find a Court. --}}
        @if ($popularSports->isNotEmpty())
            @php($sportIcons = [
                'Badminton' => 'sparkles', 'Futsal' => 'trophy', 'Football' => 'trophy',
                'Pickleball' => 'sparkles', 'Tennis' => 'trophy', 'Basketball' => 'trophy',
                'Table Tennis' => 'sparkles', 'Squash' => 'trophy',
            ])
            <section class="mx-auto max-w-6xl px-6 pb-8">
                <h2 class="text-center text-sm font-semibold uppercase tracking-wide text-zinc-500">Browse by sport</h2>
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    @foreach ($popularSports as $s)
                        <a href="{{ route('courts.browse', ['sport' => $s]) }}"
                           class="flex items-center gap-2 rounded-full border border-zinc-200 px-4 py-2 text-sm font-medium hover:border-blue-400 hover:text-blue-600 dark:border-zinc-800 dark:hover:text-blue-400">
                            <flux:icon :name="$sportIcons[$s] ?? 'trophy'" class="size-4 text-blue-500" />
                            {{ $s }}
                        </a>
                    @endforeach
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

        {{-- Footer --}}
        <footer class="border-t border-zinc-200 dark:border-zinc-800">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-6 py-8 text-sm text-zinc-500 sm:flex-row">
                <div class="flex items-center gap-2">
                    <span class="flex size-6 items-center justify-center rounded bg-blue-600 text-white">
                        <flux:icon name="map-pin" class="size-4" />
                    </span>
                    <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ config('app.name') }}</span>
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('for-business') }}" class="hover:text-zinc-700 dark:hover:text-zinc-300">For owners</a>
                    <span>&copy; {{ now()->year }} {{ config('app.name') }}. Book courts across Malaysia.</span>
                </div>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>
