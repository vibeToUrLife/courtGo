{{-- Shared top navigation for the homepage and customer-facing pages
     (Find a Court, My Bookings). Role-aware: customers get browse + bookings
     links, owners/admins get a dashboard link; everyone gets a profile menu.
     Guests get For owners / Log in / Get started. --}}
<header class="border-b border-zinc-200 dark:border-zinc-800">
    <nav class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-semibold">
            <span class="flex size-8 items-center justify-center rounded-md bg-blue-600 text-white">
                <flux:icon name="map-pin" variant="solid" class="size-5" />
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
                {{-- Text nav links: hidden on mobile (they live in the profile menu there). --}}
                @if (auth()->user()->role === \App\Enums\UserRole::Customer)
                    <a href="{{ route('courts.browse') }}" wire:navigate
                       class="hidden rounded-lg px-3 py-2 text-sm font-medium text-zinc-700 hover:text-zinc-900 sm:inline-block dark:text-zinc-300 dark:hover:text-white">
                        Find a court
                    </a>
                    <a href="{{ route('bookings.mine') }}" wire:navigate
                       class="hidden rounded-lg px-3 py-2 text-sm font-medium text-zinc-700 hover:text-zinc-900 sm:inline-block dark:text-zinc-300 dark:hover:text-white">
                        My bookings
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" wire:navigate
                       class="hidden rounded-lg px-3 py-2 text-sm font-medium text-zinc-700 hover:text-zinc-900 sm:inline-block dark:text-zinc-300 dark:hover:text-white">
                        Go to dashboard
                    </a>
                @endif

                {{-- Profile menu (top-right) --}}
                <flux:dropdown position="bottom" align="end">
                    <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()" icon:trailing="chevron-down" />
                    <flux:menu>
                        {{-- On mobile the top-nav links are hidden, so surface them here. --}}
                        @if (auth()->user()->role === \App\Enums\UserRole::Customer)
                            <flux:menu.item :href="route('courts.browse')" icon="magnifying-glass" wire:navigate class="sm:hidden">{{ __('Find a court') }}</flux:menu.item>
                            <flux:menu.item :href="route('bookings.mine')" icon="ticket" wire:navigate class="sm:hidden">{{ __('My bookings') }}</flux:menu.item>
                        @else
                            <flux:menu.item :href="route('dashboard')" icon="home" wire:navigate class="sm:hidden">{{ __('Go to dashboard') }}</flux:menu.item>
                        @endif
                        <flux:menu.separator class="sm:hidden" />
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Profile') }}</flux:menu.item>
                        <flux:menu.separator />
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">
                                {{ __('Log out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
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
