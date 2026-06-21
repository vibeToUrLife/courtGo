<x-layouts::app :title="__('Dashboard')">
    @php($user = auth()->user())

    <div class="p-6 max-w-4xl mx-auto w-full space-y-8">
        <div class="space-y-1">
            <flux:heading size="xl">Welcome back, {{ $user->name }} 👋</flux:heading>
            <flux:text>Here's what you can do on CourtGo.</flux:text>
        </div>

        @php($cards = [])

        @if ($user->role === \App\Enums\UserRole::Customer)
            @php($cards = [
                ['title' => 'Find a Court', 'desc' => 'Browse places and book a session.', 'href' => route('courts.browse'), 'icon' => 'magnifying-glass'],
                ['title' => 'My Bookings', 'desc' => 'View and manage your bookings.', 'href' => route('bookings.mine'), 'icon' => 'ticket'],
            ])
        @elseif ($user->role === \App\Enums\UserRole::Owner)
            @php($cards = [
                ['title' => 'My Venues', 'desc' => 'Add venues, courts and schedules.', 'href' => route('owner.venues.index'), 'icon' => 'building-storefront'],
                ['title' => 'Billing & Payouts', 'desc' => 'Subscription and bank connection.', 'href' => route('owner.billing'), 'icon' => 'credit-card'],
            ])
        @elseif ($user->role === \App\Enums\UserRole::Admin)
            @php($cards = [
                ['title' => 'Admin Dashboard', 'desc' => 'Platform stats and overview.', 'href' => route('admin.dashboard'), 'icon' => 'chart-bar'],
                ['title' => 'Approve Venues', 'desc' => 'Review and approve new venues.', 'href' => route('admin.venues'), 'icon' => 'building-storefront'],
                ['title' => 'Manage Owners', 'desc' => 'Suspend or unsuspend owners.', 'href' => route('admin.owners'), 'icon' => 'users'],
            ])
        @endif

        @if ($user->role === \App\Enums\UserRole::Owner)
            @php($venuesNeedingDocs = $user->venues()->whereNull('approved_at')->with('documents')->get()
                ->filter(fn ($v) => ! $v->hasAllDocuments()))
            @php($pendingVenues = $user->venues()->whereNull('approved_at')->count())
            @php($allVenuesSubscribed = $user->venues->every(fn ($v) => $v->setRelation('owner', $user)->isSubscribed()))

            {{-- Most important: documents missing → venue can't be approved or go live --}}
            @if ($venuesNeedingDocs->isNotEmpty())
                <flux:callout variant="danger" icon="exclamation-triangle">
                    <flux:callout.heading>Upload your verification documents to go live</flux:callout.heading>
                    <flux:callout.text>
                        {{ $venuesNeedingDocs->count() }} {{ \Illuminate\Support\Str::plural('venue', $venuesNeedingDocs->count()) }}
                        ({{ $venuesNeedingDocs->pluck('name')->join(', ') }})
                        still {{ $venuesNeedingDocs->count() === 1 ? 'needs' : 'need' }} documents (SSM, premises, council licence, address proof).
                        A venue can't be approved or booked until all of them are uploaded in its
                        <a class="underline" href="{{ route('owner.venues.index') }}">venue Profile</a>.
                    </flux:callout.text>
                </flux:callout>
            @endif

            @unless ($user->canAcceptBookings() && $allVenuesSubscribed)
                <flux:callout variant="warning" icon="exclamation-triangle">
                    <flux:callout.text>
                        <strong>Your courts aren't live yet.</strong>
                        Connect your bank and subscribe each venue in
                        <a class="underline" href="{{ route('owner.billing') }}">Billing</a> so customers can book.
                    </flux:callout.text>
                </flux:callout>
            @endunless

            @if ($pendingVenues > 0)
                <flux:callout variant="warning" icon="clock">
                    <flux:callout.text>
                        <strong>{{ $pendingVenues }} {{ \Illuminate\Support\Str::plural('venue', $pendingVenues) }} pending admin approval.</strong>
                        You can add courts and schedules now — each venue becomes visible to customers once its documents are uploaded and an admin approves it.
                    </flux:callout.text>
                </flux:callout>
            @endif
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach ($cards as $card)
                <a href="{{ $card['href'] }}" wire:navigate
                   class="block rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 hover:border-blue-400 transition">
                    <flux:icon :name="$card['icon']" class="size-6 text-blue-500" />
                    <div class="mt-3 font-semibold text-lg">{{ $card['title'] }}</div>
                    <flux:text>{{ $card['desc'] }}</flux:text>
                </a>
            @endforeach
        </div>
    </div>
</x-layouts::app>
