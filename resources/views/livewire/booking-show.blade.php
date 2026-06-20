<div class="space-y-6 p-6 max-w-2xl mx-auto w-full">
    <flux:button size="sm" variant="ghost" :href="route('bookings.mine')" wire:navigate icon="arrow-left">Back to my bookings</flux:button>

    @if (session('booking_error'))
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.text>{{ session('booking_error') }}</flux:callout.text>
        </flux:callout>
    @endif

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 space-y-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <flux:heading size="lg">{{ $venue->name }}</flux:heading>
                <flux:text>{{ $court->name }} · {{ $court->sport }}</flux:text>
            </div>
            @if ($status === 'confirmed')
                <flux:badge color="green">Confirmed</flux:badge>
            @elseif ($status === 'awaiting')
                <flux:badge color="amber">Awaiting payment</flux:badge>
            @elseif ($status === 'expired')
                <flux:badge color="zinc">Expired</flux:badge>
            @else
                <flux:badge color="zinc">Cancelled</flux:badge>
            @endif
        </div>

        <flux:separator variant="subtle" />

        <div class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
            <div>
                <div class="text-zinc-500">Date</div>
                <div class="font-medium">{{ $date->format('l, d M Y') }}</div>
            </div>
            <div>
                <div class="text-zinc-500">Time</div>
                <div class="font-medium">
                    {{ \Illuminate\Support\Carbon::parse($startTime)->format('g:i A') }} – {{ \Illuminate\Support\Carbon::parse($endTime)->format('g:i A') }}
                </div>
            </div>
            <div class="sm:col-span-2">
                <div class="text-zinc-500">Location</div>
                <div class="font-medium">📍 {{ $venue->address }}, {{ $venue->city }}, {{ $venue->state }}</div>
            </div>
            <div>
                <div class="text-zinc-500">Total paid</div>
                <div class="font-medium">RM {{ number_format($total, 2) }}</div>
            </div>
        </div>

        {{-- Slot breakdown when the booking covers more than one slot --}}
        @if (count($bookingSlots) > 1)
            <div>
                <flux:text class="text-sm font-medium">Slots ({{ count($bookingSlots) }})</flux:text>
                <ul class="mt-2 space-y-1 text-sm">
                    @foreach ($bookingSlots as $slot)
                        <li class="flex justify-between gap-4">
                            <span>{{ \Illuminate\Support\Carbon::parse($slot->start_time)->format('g:i A') }} – {{ \Illuminate\Support\Carbon::parse($slot->end_time)->format('g:i A') }}</span>
                            <span class="text-zinc-500">RM {{ number_format($slot->price, 2) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($status === 'awaiting')
            <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 p-4 space-y-3">
                @if ($holdExpiresAt)
                    <flux:text class="text-sm text-amber-700 dark:text-amber-400">
                        Pay before {{ $holdExpiresAt->format('g:i A') }} or the slot is released.
                    </flux:text>
                @endif
                <flux:button variant="primary" wire:click="payGroup({{ json_encode($ids) }})" wire:loading.attr="disabled" wire:target="payGroup">
                    Continue payment
                </flux:button>
            </div>
        @endif
    </div>
</div>
