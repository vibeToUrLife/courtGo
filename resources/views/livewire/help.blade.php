@php
    $players = [
        ['q' => "I paid but my booking still says “Awaiting payment”.", 'a' => "It normally confirms the moment you return from the payment page. If it still shows awaiting, refresh <strong>My Bookings</strong> — confirmation can take a few seconds. If it's still unpaid after a few minutes, send us your booking details and we'll sort it out."],
        ['q' => "How do I book a court?", 'a' => "Open <strong>Find a court</strong>, choose a venue, pick a date, then tap the time slots you want and press <strong>Book &amp; pay</strong>."],
        ['q' => "Can I book several slots at once?", 'a' => "Yes — select multiple slots on the venue page and pay for them together in one checkout."],
        ['q' => "How do I cancel or get a refund?", 'a' => "Cancellation and refunds are set by each venue. Check the venue's <strong>Policy</strong> section, and contact the venue directly — their phone/WhatsApp is on the venue page."],
        ['q' => "My booking isn't showing in My Bookings.", 'a' => "Check the status tabs at the top of My Bookings (it may be under <em>Awaiting</em> or <em>Cancelled</em>), and make sure you're logged into the same account you booked with."],
        ['q' => "The slot I wanted got released before I paid.", 'a' => "Slots are held for a short time while you pay. If the hold expires, just reopen the venue and pick the slot again."],
    ];

    $owners = [
        ['q' => "Why aren't my courts live / bookable?", 'a' => "A venue goes live only when all three are done: (1) it's <strong>approved</strong> by an admin — upload every verification document on the venue Profile; (2) that venue has an <strong>active subscription</strong>; and (3) you've <strong>connected your bank</strong>. Check the Billing page — each venue needs its own subscription."],
        ['q' => "I uploaded my documents but the venue is still pending.", 'a' => "An admin reviews them, and you'll get an email when it's approved or needs changes. If a document was rejected you'll see the reason on the venue Profile — fix it and re-upload, and it goes back into the review queue automatically."],
        ['q' => "My subscription still shows “Not subscribed” after I paid.", 'a' => "It updates when you return from Stripe; if not, reopen the <strong>Billing</strong> page. Remember each venue is subscribed separately."],
        ['q' => "How do I cancel or manage a subscription?", 'a' => "Billing → <strong>Manage</strong> opens the Stripe portal. If you cancel at the end of the period, the venue stays live until the date shown on the Billing page."],
        ['q' => "How do I get paid?", 'a' => "Connect your bank once in <strong>Billing</strong> (handled securely by Stripe). Booking money goes straight to your bank — CourtGo takes 0% commission on bookings."],
        ['q' => "How do I set my courts, schedule and prices?", 'a' => "My Venues → <strong>Manage courts</strong>: add each court, then add weekly session times, each with its own length and price."],
    ];
@endphp

<div class="space-y-8">
    <div class="space-y-1">
        <flux:button size="sm" variant="ghost" :href="route('home')" wire:navigate icon="arrow-left">Back to homepage</flux:button>
        <flux:heading size="xl" class="!text-2xl !font-bold tracking-tight">Help center</flux:heading>
        <flux:text>Quick answers and troubleshooting for players and venue owners.</flux:text>
    </div>

    @foreach ([['title' => 'For players', 'icon' => 'user', 'items' => $players], ['title' => 'For venue owners', 'icon' => 'building-storefront', 'items' => $owners]] as $group)
        <section class="space-y-3">
            <h2 class="flex items-center gap-2 text-lg font-semibold text-zinc-900 dark:text-white">
                <flux:icon :name="$group['icon']" class="size-5 text-blue-600" /> {{ $group['title'] }}
            </h2>
            <div class="divide-y divide-zinc-100 overflow-hidden rounded-2xl border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-700">
                @foreach ($group['items'] as $qa)
                    <details class="group">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-4 font-medium text-zinc-900 transition hover:bg-zinc-50 dark:text-white dark:hover:bg-zinc-900">
                            <span>{{ $qa['q'] }}</span>
                            <flux:icon name="chevron-down" class="size-5 shrink-0 text-zinc-400 transition group-open:rotate-180" />
                        </summary>
                        <div class="px-4 pb-4 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{!! $qa['a'] !!}</div>
                    </details>
                @endforeach
            </div>
        </section>
    @endforeach

    {{-- Still stuck → feedback / email --}}
    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-6 text-center dark:border-blue-900 dark:bg-blue-950/30">
        <flux:heading size="lg">Still stuck?</flux:heading>
        <flux:text class="text-zinc-600 dark:text-zinc-400">Tell us what's wrong and we'll help you out.</flux:text>
        <div class="mt-4 flex flex-wrap justify-center gap-2">
            <flux:button variant="primary" icon="chat-bubble-left-right" :href="route('feedback')" wire:navigate>Send feedback</flux:button>
            <flux:button variant="ghost" href="mailto:{{ config('courtgo.support_email') }}">Email us</flux:button>
        </div>
    </div>
</div>
