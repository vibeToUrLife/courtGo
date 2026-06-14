<div class="space-y-6 p-6 max-w-5xl mx-auto w-full">
    <flux:heading size="xl">Admin Dashboard</flux:heading>
    <flux:text>An overview of the whole CourtGo platform.</flux:text>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
        @php($stats = [
            ['label' => 'Owners', 'value' => $ownerCount],
            ['label' => 'Customers', 'value' => $customerCount],
            ['label' => 'Active subscriptions', 'value' => $activeSubscriptions],
            ['label' => 'Venues', 'value' => $venueCount],
            ['label' => 'Courts', 'value' => $courtCount],
            ['label' => 'Confirmed bookings', 'value' => $confirmedBookings],
        ])
        @foreach ($stats as $stat)
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
                <div class="text-3xl font-bold">{{ $stat['value'] }}</div>
                <div class="text-sm text-zinc-500 mt-1">{{ $stat['label'] }}</div>
            </div>
        @endforeach
    </div>

    <flux:button variant="primary" :href="route('admin.owners')" wire:navigate>Manage owners</flux:button>
</div>
