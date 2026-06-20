<footer {{ $attributes->merge(['class' => 'border-t border-zinc-200 dark:border-zinc-800']) }}>
    <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-6 py-8 text-sm text-zinc-500 sm:flex-row">
        <div class="flex items-center gap-2">
            <span class="flex size-6 items-center justify-center rounded bg-blue-600 text-white">
                <flux:icon name="map-pin" class="size-4" />
            </span>
            <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ config('app.name') }}</span>
        </div>
        <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1">
            <a href="{{ route('home') }}" class="hover:text-zinc-700 dark:hover:text-zinc-300">Home</a>
            <a href="{{ route('for-business') }}" class="hover:text-zinc-700 dark:hover:text-zinc-300">For owners</a>
            <a href="mailto:{{ config('courtgo.support_email') }}" class="hover:text-zinc-700 dark:hover:text-zinc-300">Contact</a>
            <span>&copy; {{ now()->year }} {{ config('app.name') }}. Book courts across Malaysia.</span>
        </div>
    </div>
</footer>
