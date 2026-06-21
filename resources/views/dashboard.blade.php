<x-layouts::app :title="__('Dashboard')">
    @php($user = auth()->user())
    @php($isOwner = $user->role === \App\Enums\UserRole::Owner)

    <div class="p-6 max-w-5xl mx-auto w-full space-y-8">
        <div class="space-y-1">
            <flux:heading size="xl" class="!text-2xl !font-bold tracking-tight">Welcome back, {{ $user->name }} 👋</flux:heading>
            <flux:text>Here's what's happening with your account.</flux:text>
        </div>

        @if ($isOwner)
            @php($venueCount = $user->venues()->count())
            @php($liveCount = \App\Models\Venue::bookable()->where('owner_id', $user->id)->count())
            @php($courtCount = \App\Models\Court::whereHas('venue', fn ($v) => $v->where('owner_id', $user->id))->count())
            @php($upcoming = \App\Models\Booking::where('status', \App\Enums\BookingStatus::Confirmed->value)
                ->whereDate('booking_date', '>=', now()->toDateString())
                ->whereHas('court.venue', fn ($v) => $v->where('owner_id', $user->id))->count())
            @php($earnings = \App\Models\Booking::where('status', \App\Enums\BookingStatus::Confirmed->value)
                ->whereHas('court.venue', fn ($v) => $v->where('owner_id', $user->id))
                ->selectRaw('COALESCE(SUM(price), 0) as total, COUNT(*) as cnt')->first())

            {{-- Featured: earnings (owners keep 100%) --}}
            <div class="rounded-2xl border border-blue-200 bg-gradient-to-br from-blue-50 to-white p-6 dark:border-blue-900 dark:from-blue-950/40 dark:to-zinc-900">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm font-medium text-blue-700 dark:text-blue-300">Total earnings</flux:text>
                    <span class="flex size-9 items-center justify-center rounded-xl bg-blue-600 text-white">
                        <flux:icon name="banknotes" class="size-5" />
                    </span>
                </div>
                <div class="mt-3 text-4xl font-bold tabular-nums text-zinc-900 dark:text-white">RM {{ number_format($earnings->total, 2) }}</div>
                <flux:text class="mt-1 text-sm text-zinc-500">From {{ $earnings->cnt }} confirmed {{ \Illuminate\Support\Str::plural('booking', $earnings->cnt) }} — you keep 100%, CourtGo takes 0% commission.</flux:text>
            </div>

            {{-- KPI stat cards --}}
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach ([
                    ['icon' => 'building-storefront', 'label' => 'Venues', 'value' => $venueCount],
                    ['icon' => 'signal', 'label' => 'Live & bookable', 'value' => $liveCount],
                    ['icon' => 'rectangle-group', 'label' => 'Courts', 'value' => $courtCount],
                    ['icon' => 'ticket', 'label' => 'Upcoming bookings', 'value' => $upcoming],
                ] as $stat)
                    <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-center justify-between">
                            <flux:text class="text-sm text-zinc-500">{{ $stat['label'] }}</flux:text>
                            <flux:icon :name="$stat['icon']" class="size-5 text-blue-600" />
                        </div>
                        <div class="mt-2 text-3xl font-bold tabular-nums text-zinc-900 dark:text-white">{{ $stat['value'] }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Go-live checklist --}}
            @php($venuesNeedingDocs = $user->venues()->whereNull('approved_at')->with('documents')->get()
                ->filter(fn ($v) => ! $v->hasAllDocuments()))
            @php($pendingVenues = $user->venues()->whereNull('approved_at')->count())
            @php($allVenuesSubscribed = $venueCount > 0 && $user->venues->every(fn ($v) => $v->setRelation('owner', $user)->isSubscribed()))
            @php($docsDone = $venueCount > 0 && $venuesNeedingDocs->isEmpty())
            @php($approvedDone = $venueCount > 0 && $pendingVenues === 0)
            @php($bankDone = (bool) $user->connect_onboarded)
            @php($allLive = $venueCount > 0 && $approvedDone && $allVenuesSubscribed && $bankDone)

            @if ($venueCount === 0)
                <div class="rounded-2xl border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700 space-y-3">
                    <flux:icon name="building-storefront" class="mx-auto size-8 text-zinc-300 dark:text-zinc-600" />
                    <flux:heading size="lg">Add your first venue</flux:heading>
                    <flux:text class="text-zinc-500">List a venue, upload its documents and add courts to start taking bookings.</flux:text>
                    <flux:button variant="primary" icon="plus" :href="route('owner.venues.index')" wire:navigate>Add a venue</flux:button>
                </div>
            @elseif ($allLive)
                <flux:callout variant="success" icon="check-circle">
                    <flux:callout.heading>You're live!</flux:callout.heading>
                    <flux:callout.text>All your venues are approved, subscribed and connected — customers can book your courts.</flux:callout.text>
                </flux:callout>
            @else
                <div class="space-y-4 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <div>
                        <flux:heading size="lg">Get your courts live</flux:heading>
                        <flux:text class="text-sm text-zinc-500">Finish these steps so customers can find and book your courts.</flux:text>
                    </div>

                    <ul class="space-y-3">
                        @php($steps = [
                            [
                                'done' => $approvedDone,
                                'label' => 'Verification & approval',
                                'hint' => ! $docsDone
                                    ? 'Upload all documents for: '.$venuesNeedingDocs->pluck('name')->join(', ')
                                    : ($approvedDone ? 'All venues approved.' : 'Documents uploaded — waiting for admin approval.'),
                                'cta' => $docsDone ? null : 'Upload documents',
                                'href' => route('owner.venues.index'),
                            ],
                            [
                                'done' => $allVenuesSubscribed,
                                'label' => 'Subscribe each venue',
                                'hint' => $allVenuesSubscribed ? 'Every venue is subscribed.' : 'Each venue needs its own monthly plan.',
                                'cta' => $allVenuesSubscribed ? null : 'Go to Billing',
                                'href' => route('owner.billing'),
                            ],
                            [
                                'done' => $bankDone,
                                'label' => 'Connect your bank',
                                'hint' => $bankDone ? 'Payouts are connected.' : 'Connect once — booking money goes straight to your bank.',
                                'cta' => $bankDone ? null : 'Connect bank',
                                'href' => route('owner.billing'),
                            ],
                        ])
                        @foreach ($steps as $i => $step)
                            <li class="flex items-start gap-3">
                                @if ($step['done'])
                                    <flux:icon name="check-circle" variant="solid" class="size-6 shrink-0 text-green-600" />
                                @else
                                    <span class="flex size-6 shrink-0 items-center justify-center rounded-full border-2 border-zinc-300 text-xs font-semibold text-zinc-500 dark:border-zinc-600">{{ $i + 1 }}</span>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium {{ $step['done'] ? 'text-zinc-400 line-through dark:text-zinc-500' : 'text-zinc-900 dark:text-white' }}">{{ $step['label'] }}</div>
                                    <div class="text-sm text-zinc-500">{{ $step['hint'] }}</div>
                                </div>
                                @if ($step['cta'])
                                    <flux:button size="sm" variant="primary" :href="$step['href']" wire:navigate>{{ $step['cta'] }}</flux:button>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif

        {{-- Quick actions --}}
        @php($cards = [])
        @if ($user->role === \App\Enums\UserRole::Customer)
            @php($cards = [
                ['title' => 'Find a Court', 'desc' => 'Browse places and book a session.', 'href' => route('courts.browse'), 'icon' => 'magnifying-glass'],
                ['title' => 'My Bookings', 'desc' => 'View and manage your bookings.', 'href' => route('bookings.mine'), 'icon' => 'ticket'],
            ])
        @elseif ($isOwner)
            @php($cards = [
                ['title' => 'My Venues', 'desc' => 'Add venues, courts and schedules.', 'href' => route('owner.venues.index'), 'icon' => 'building-storefront'],
                ['title' => 'Billing & Payouts', 'desc' => 'Subscriptions and bank connection.', 'href' => route('owner.billing'), 'icon' => 'credit-card'],
            ])
        @elseif ($user->role === \App\Enums\UserRole::Admin)
            @php($cards = [
                ['title' => 'Admin Dashboard', 'desc' => 'Platform stats and overview.', 'href' => route('admin.dashboard'), 'icon' => 'chart-bar'],
                ['title' => 'Approve Venues', 'desc' => 'Review and approve new venues.', 'href' => route('admin.venues'), 'icon' => 'building-storefront'],
                ['title' => 'Manage Owners', 'desc' => 'Suspend or unsuspend owners.', 'href' => route('admin.owners'), 'icon' => 'users'],
            ])
        @endif

        @if (! empty($cards))
            <div>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-zinc-500">Quick actions</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($cards as $card)
                        <a href="{{ $card['href'] }}" wire:navigate
                           class="group block rounded-2xl border border-zinc-200 p-6 transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900">
                            <span class="flex size-11 items-center justify-center rounded-xl bg-blue-600/10 text-blue-600 transition group-hover:bg-blue-600 group-hover:text-white">
                                <flux:icon :name="$card['icon']" class="size-6" />
                            </span>
                            <div class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">{{ $card['title'] }}</div>
                            <flux:text>{{ $card['desc'] }}</flux:text>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-layouts::app>
