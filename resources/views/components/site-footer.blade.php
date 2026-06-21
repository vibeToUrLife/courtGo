<footer {{ $attributes->merge(['class' => 'border-t border-zinc-200 bg-zinc-50/60 dark:border-zinc-800 dark:bg-zinc-950']) }}>
    <div class="mx-auto max-w-6xl px-6 py-10">
        <div class="flex flex-col gap-8 sm:flex-row sm:items-start sm:justify-between">
            {{-- Brand --}}
            <div class="max-w-sm space-y-3">
                <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-semibold">
                    <span class="flex size-8 items-center justify-center rounded-md bg-blue-600 text-white">
                        <flux:icon name="map-pin" variant="solid" class="size-5" />
                    </span>
                    {{ config('app.name') }}
                </a>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    Book badminton, futsal, tennis and more across Malaysia — pick a time, pay securely, play.
                </p>
            </div>

            {{-- Link groups --}}
            <div class="flex flex-wrap gap-10 sm:gap-16">
                <div class="space-y-2 text-sm">
                    <h3 class="font-semibold text-zinc-900 dark:text-white">Explore</h3>
                    <a href="{{ route('home') }}" class="block text-zinc-500 transition hover:text-blue-600 dark:text-zinc-400 dark:hover:text-blue-400">Home</a>
                    <a href="{{ route('for-business') }}" class="block text-zinc-500 transition hover:text-blue-600 dark:text-zinc-400 dark:hover:text-blue-400">For owners</a>
                </div>
                <div class="space-y-2 text-sm">
                    <h3 class="font-semibold text-zinc-900 dark:text-white">Help</h3>
                    <a href="{{ route('help') }}" wire:navigate class="block text-zinc-500 transition hover:text-blue-600 dark:text-zinc-400 dark:hover:text-blue-400">Help center</a>
                    <a href="{{ route('feedback') }}" wire:navigate class="block text-zinc-500 transition hover:text-blue-600 dark:text-zinc-400 dark:hover:text-blue-400">Send feedback</a>
                </div>
                <div class="space-y-2 text-sm">
                    <h3 class="font-semibold text-zinc-900 dark:text-white">Support</h3>
                    <a href="mailto:{{ config('courtgo.support_email') }}" class="block text-zinc-500 transition hover:text-blue-600 dark:text-zinc-400 dark:hover:text-blue-400">Contact us</a>
                </div>
            </div>
        </div>
    </div>

    <div class="border-t border-zinc-200 dark:border-zinc-800">
        <div class="mx-auto max-w-6xl px-6 py-5 text-center text-xs text-zinc-500 dark:text-zinc-400 sm:text-left">
            &copy; {{ now()->year }} {{ config('app.name') }}. Book courts across Malaysia.
        </div>
    </div>
</footer>
