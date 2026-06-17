<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => 'For Business'])
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
                    <a href="{{ route('home') }}"
                       class="hidden rounded-lg px-4 py-2 text-sm font-medium text-zinc-700 hover:text-zinc-900 sm:inline-block dark:text-zinc-300 dark:hover:text-white">
                        For players
                    </a>
                    @auth
                        <a href="{{ route('dashboard') }}" wire:navigate
                           class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                            Go to dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" wire:navigate
                           class="rounded-lg px-4 py-2 text-sm font-medium text-zinc-700 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white">
                            Log in
                        </a>
                        <a href="{{ route('register', ['as' => 'owner']) }}" wire:navigate
                           class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                            List your venue
                        </a>
                    @endauth
                </div>
            </nav>
        </header>

        {{-- Hero --}}
        <section class="mx-auto max-w-6xl px-6 py-20 text-center sm:py-28">
            <span class="inline-flex items-center gap-2 rounded-full border border-zinc-200 px-3 py-1 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
                <span class="size-2 rounded-full bg-emerald-500"></span>
                {{ config('app.name') }} for Business
            </span>

            <h1 class="mx-auto mt-6 max-w-3xl text-4xl font-bold tracking-tight sm:text-6xl">
                Grow your venue. Keep every ringgit.
            </h1>

            <p class="mx-auto mt-6 max-w-2xl text-lg text-zinc-600 dark:text-zinc-400">
                List your courts on {{ config('app.name') }}, set your own schedule and prices, and let players
                book and pay online. You keep 100% of every booking — we just charge a simple monthly subscription.
            </p>

            <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ route('register', ['as' => 'owner']) }}" wire:navigate
                   class="w-full rounded-lg bg-blue-600 px-6 py-3 text-base font-medium text-white hover:bg-blue-700 sm:w-auto">
                    Create your owner account
                </a>
                <a href="{{ route('login') }}" wire:navigate
                   class="w-full rounded-lg border border-zinc-300 px-6 py-3 text-base font-medium text-zinc-800 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-900 sm:w-auto">
                    I already have an account
                </a>
            </div>
        </section>

        {{-- Benefits --}}
        <section class="border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900/40">
            <div class="mx-auto max-w-6xl px-6 py-20">
                <h2 class="text-center text-3xl font-bold">Everything you need to fill your courts</h2>

                <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        ['icon' => 'banknotes', 'title' => '0% booking commission', 'desc' => 'We never take a cut of your bookings. Players pay you directly — you keep the full amount.'],
                        ['icon' => 'calendar-days', 'title' => 'Your schedule, your prices', 'desc' => 'Set recurring weekly sessions for each court, every one with its own length and price.'],
                        ['icon' => 'credit-card', 'title' => 'Secure payouts via Stripe', 'desc' => 'Connect your bank once. Booking payments land in your account automatically.'],
                        ['icon' => 'chart-bar', 'title' => 'Owner dashboard', 'desc' => 'Manage venues, courts and schedules, and see your bookings — all in one place.'],
                        ['icon' => 'shield-check', 'title' => 'Double-booking protection', 'desc' => 'Slots are locked the moment a player pays, so the same court is never sold twice.'],
                        ['icon' => 'globe-alt', 'title' => 'Get discovered', 'desc' => 'Your venue appears in search the minute you go live, so new players can find and book you.'],
                    ] as $benefit)
                        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                            <span class="flex size-11 items-center justify-center rounded-xl bg-blue-600/10 text-blue-600 dark:text-blue-400">
                                <flux:icon :name="$benefit['icon']" class="size-6" />
                            </span>
                            <h3 class="mt-4 text-lg font-semibold">{{ $benefit['title'] }}</h3>
                            <p class="mt-2 text-zinc-600 dark:text-zinc-400">{{ $benefit['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- How it works for owners --}}
        <section class="mx-auto max-w-6xl px-6 py-20">
            <h2 class="text-center text-3xl font-bold">Up and running in four steps</h2>

            <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['n' => '1', 'title' => 'Create your account', 'desc' => 'Sign up as an owner — it takes a minute.'],
                    ['n' => '2', 'title' => 'Add your venue & courts', 'desc' => 'List each court and the sport it’s for.'],
                    ['n' => '3', 'title' => 'Set your schedule', 'desc' => 'Add weekly sessions with your own prices.'],
                    ['n' => '4', 'title' => 'Connect your bank & go live', 'desc' => 'Subscribe, link Stripe, and start taking bookings.'],
                ] as $step)
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="flex size-9 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white">{{ $step['n'] }}</span>
                        <h3 class="mt-4 font-semibold">{{ $step['title'] }}</h3>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $step['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Closing CTA --}}
        <section class="border-t border-zinc-200 dark:border-zinc-800">
            <div class="mx-auto max-w-3xl px-6 py-20 text-center">
                <h2 class="text-3xl font-bold">Ready to fill your courts?</h2>
                <p class="mt-4 text-zinc-600 dark:text-zinc-400">Join {{ config('app.name') }} and start taking online bookings today.</p>
                <div class="mt-8">
                    <a href="{{ route('register', ['as' => 'owner']) }}" wire:navigate
                       class="inline-block rounded-lg bg-blue-600 px-8 py-3 text-base font-medium text-white hover:bg-blue-700">
                        List your venue
                    </a>
                </div>
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
                    <a href="{{ route('home') }}" class="hover:text-zinc-700 dark:hover:text-zinc-300">For players</a>
                    <span>&copy; {{ now()->year }} {{ config('app.name') }}.</span>
                </div>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>
