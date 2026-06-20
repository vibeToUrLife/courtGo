<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="flex min-h-screen flex-col bg-white text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        {{-- Customer-facing pages (Find a Court, My Bookings) share the homepage
             header instead of the owner/admin sidebar — they read as a continuation
             of the public site, not an app dashboard. --}}
        <x-site-header />

        {{-- flex-1 pushes the footer to the bottom of the viewport on short pages.
             Pages that already center/pad their own root (Browse, My Bookings) leave
             $mainClass empty; pages that don't (settings) pass padding/width in. --}}
        <main class="flex-1 {{ $mainClass ?? '' }}">
            {{ $slot }}
        </main>

        <x-site-footer />

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
