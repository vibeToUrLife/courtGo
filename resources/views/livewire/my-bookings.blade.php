<div class="space-y-6 p-6 max-w-3xl mx-auto w-full">
    <flux:button size="sm" variant="ghost" :href="route('home')" wire:navigate icon="arrow-left">Back to homepage</flux:button>

    <div class="flex items-end justify-between gap-4 flex-wrap">
        <flux:heading size="xl">My Bookings</flux:heading>
        <div class="flex items-end gap-2 flex-wrap">
            <flux:select wire:model.live="filter" class="max-w-xs" label="Status">
                <flux:select.option value="all">All bookings</flux:select.option>
                <flux:select.option value="confirmed">Confirmed</flux:select.option>
                <flux:select.option value="awaiting">Awaiting payment</flux:select.option>
                <flux:select.option value="cancelled">Cancelled / expired</flux:select.option>
            </flux:select>
            <flux:input wire:model.live="date" type="date" label="Date" class="max-w-[10rem]" />
            @if ($date)
                <flux:button size="sm" variant="ghost" wire:click="clearDate">Clear</flux:button>
            @endif
        </div>
    </div>

    @if (session('booking_confirmed'))
        <flux:callout variant="success" icon="check-circle">
            <flux:callout.text>Booking confirmed! See it below.</flux:callout.text>
        </flux:callout>
    @endif
    @if (session('booking_error'))
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.text>{{ session('booking_error') }}</flux:callout.text>
        </flux:callout>
    @endif

    @if (empty($todayGroups) && empty($otherGroups))
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-8 text-center space-y-3">
            <flux:text>No bookings here.</flux:text>
            <flux:button variant="primary" :href="route('courts.browse')" wire:navigate>Find a court</flux:button>
        </div>
    @else
        @if (! empty($todayGroups))
            <div class="space-y-3">
                <flux:heading size="lg">Booked today</flux:heading>
                @foreach ($todayGroups as $group)
                    @include('partials.booking-group-card', ['group' => $group])
                @endforeach
            </div>
        @endif

        @if (! empty($otherGroups))
            <div class="space-y-3">
                <flux:heading size="lg">{{ empty($todayGroups) ? 'Bookings' : 'Booked earlier' }}</flux:heading>
                @foreach ($otherGroups as $group)
                    @include('partials.booking-group-card', ['group' => $group])
                @endforeach
            </div>
        @endif
    @endif
</div>
