{{-- One booking row (a group of slots booked together). Expects $group. --}}
<div class="flex items-center justify-between gap-4 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 transition hover:border-blue-400" wire:key="group-{{ $group['ids'][0] }}">
    <a href="{{ route('bookings.show', $group['ids'][0]) }}" wire:navigate class="flex-1">
        <div class="font-medium">{{ $group['court']->venue->name }} — {{ $group['court']->name }}</div>
        <div class="text-sm text-zinc-500">
            {{ $group['date']->format('D, d M Y') }} ·
            {{ \Illuminate\Support\Carbon::parse($group['start_time'])->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse($group['end_time'])->format('g:i A') }}
        </div>
        <div class="text-sm text-zinc-500">
            RM {{ number_format($group['price'], 2) }}@if ($group['count'] > 1) <span class="text-zinc-400">· {{ $group['count'] }} slots</span>@endif
        </div>
        @if ($group['status'] === 'awaiting' && $group['hold_expires_at'])
            <div class="text-xs text-amber-600 mt-1">
                Pay before {{ $group['hold_expires_at']->format('g:i A') }} or the slot is released.
            </div>
        @endif
    </a>
    <div class="flex flex-col items-end gap-2">
        @if ($group['status'] === 'confirmed')
            <flux:badge color="green">Confirmed</flux:badge>
        @elseif ($group['status'] === 'awaiting')
            <flux:badge color="amber">Awaiting payment</flux:badge>
            <flux:button size="sm" variant="primary" wire:click="payGroup({{ json_encode($group['ids']) }})" wire:loading.attr="disabled">
                Continue payment
            </flux:button>
        @elseif ($group['status'] === 'expired')
            <flux:badge color="zinc">Expired</flux:badge>
        @else
            <flux:badge color="zinc">Cancelled</flux:badge>
        @endif
    </div>
</div>
