<div class="space-y-8 p-6 max-w-3xl mx-auto w-full">
    <div class="space-y-1">
        <flux:heading size="xl">Billing &amp; Payouts</flux:heading>
        <flux:text>Subscribe and connect your bank so your courts can go live for booking.</flux:text>
    </div>

    {{-- Go-live status banner --}}
    @if ($canAcceptBookings)
        <div class="rounded-xl border border-green-300 bg-green-50 dark:bg-green-900/30 dark:border-green-800 p-4">
            <flux:heading size="lg">✅ Your courts are live!</flux:heading>
            <flux:text>You're subscribed and your bank is connected. Customers can book and pay you.</flux:text>
        </div>
    @else
        <div class="rounded-xl border border-amber-300 bg-amber-50 dark:bg-amber-900/30 dark:border-amber-800 p-4">
            <flux:heading size="lg">⚠️ Your courts aren't live yet</flux:heading>
            <flux:text>Finish both steps below so customers can book your courts.</flux:text>
        </div>
    @endif

    @if (session('stripe_error'))
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.text>{{ session('stripe_error') }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- Step 1: Subscription --}}
    <div class="space-y-3 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">1. Monthly subscription</flux:heading>
            @if ($subscribed)
                <flux:badge color="green">Active</flux:badge>
            @else
                <flux:badge color="zinc">Not subscribed</flux:badge>
            @endif
        </div>
        <flux:text>A monthly plan to list your courts on CourtGo.</flux:text>
        @if ($subscribed)
            <flux:button variant="ghost" :href="route('owner.billing.portal')" wire:navigate>Manage subscription</flux:button>
        @else
            <flux:button variant="primary" href="{{ route('owner.billing.subscribe') }}">Subscribe now</flux:button>
        @endif
    </div>

    {{-- Step 2: Bank / payouts (Stripe Connect) --}}
    <div class="space-y-4 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">2. Connect your bank (payouts)</flux:heading>
            @if ($onboarded)
                <flux:badge color="green">Connected</flux:badge>
            @else
                <flux:badge color="zinc">Not connected</flux:badge>
            @endif
        </div>
        <flux:text>Booking money goes straight to your bank. Stripe handles the verification — we never see your bank details.</flux:text>

        <form wire:submit="saveBrn" class="space-y-3">
            <flux:input wire:model="business_registration_number" label="Business Registration Number (BRN)" placeholder="e.g. 202301234567" description="Required by Stripe for FPX (Malaysian online banking) payouts." />
            <flux:button type="submit" variant="ghost" size="sm">Save BRN</flux:button>
            @if (session('brn_saved'))
                <flux:text class="text-green-600">Saved.</flux:text>
            @endif
        </form>

        @if ($onboarded)
            <flux:button variant="ghost" href="{{ route('owner.connect.redirect') }}">Update bank details</flux:button>
        @else
            <flux:button variant="primary" href="{{ route('owner.connect.redirect') }}">Connect bank</flux:button>
        @endif
    </div>

    <flux:text class="text-zinc-400 text-sm">
        Everything runs in Stripe <b>test mode</b> while building — no real money. See <code>docs/STRIPE-SETUP.md</code>.
    </flux:text>
</div>
