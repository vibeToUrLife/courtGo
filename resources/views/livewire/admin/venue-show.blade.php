<div class="p-6 max-w-5xl mx-auto w-full space-y-6">
    <flux:button size="sm" variant="ghost" :href="route('admin.venues')" wire:navigate icon="arrow-left">Back to venues</flux:button>

    {{-- Header: name, location, status, and the approve action --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="space-y-1">
            <flux:heading size="xl">{{ $venue->name }}</flux:heading>
            <flux:text class="text-zinc-500">{{ $venue->address ? $venue->address.', ' : '' }}{{ $venue->city }}, {{ $venue->state }}</flux:text>
            <div class="flex flex-wrap gap-2 pt-1">
                @if ($venue->isApproved())
                    <flux:badge color="green" size="sm">Approved</flux:badge>
                @else
                    <flux:badge color="amber" size="sm">Pending approval</flux:badge>
                @endif

                @if ($subscription && $subscription->valid())
                    @if ($subscription->onGracePeriod())
                        <flux:badge color="amber" size="sm">Subscription cancels {{ $subscription->ends_at->format('d M Y') }}</flux:badge>
                    @else
                        <flux:badge color="green" size="sm">Subscribed</flux:badge>
                    @endif
                @else
                    <flux:badge color="zinc" size="sm">No subscription</flux:badge>
                @endif
            </div>
        </div>

        @unless ($venue->isApproved())
            <div class="text-right">
                <flux:button variant="primary" wire:click="approve" :disabled="! $venue->isFullyVerified()"
                    wire:confirm="Approve this venue? Customers will be able to find and book it.">
                    Approve venue
                </flux:button>
                @unless ($venue->isFullyVerified())
                    <flux:text class="mt-1 text-xs text-zinc-500">Verify all {{ count($verificationItems) }} items below first ({{ $venue->verifiedCount() }}/{{ count($verificationItems) }} done).</flux:text>
                @endunless
            </div>
        @endunless
    </div>

    {{-- Verification checklist: tick each item (after checking the document) to unlock approval --}}
    @unless ($venue->isApproved())
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 space-y-3">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Verification</flux:heading>
                <flux:badge :color="$venue->isFullyVerified() ? 'green' : 'amber'" size="sm">
                    {{ $venue->verifiedCount() }}/{{ count($verificationItems) }} verified
                </flux:badge>
            </div>
            <flux:text class="text-sm text-zinc-500">Check each uploaded document, then tick it. Approval unlocks once all are ticked. (Payout identity/bank is handled separately by Stripe.)</flux:text>

            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($verificationItems as $key => $item)
                    <div class="flex flex-wrap items-start justify-between gap-3 py-3" wire:key="ver-{{ $key }}">
                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="flex items-center gap-2">
                                @if ($venue->isItemVerified($key))
                                    <flux:icon name="check-circle" variant="solid" class="size-5 text-green-600" />
                                @else
                                    <flux:icon name="exclamation-circle" class="size-5 text-amber-500" />
                                @endif
                                <flux:text class="font-medium">{{ $item['label'] }}</flux:text>
                            </div>
                            <flux:text class="text-sm text-zinc-500">{{ $item['admin_hint'] }}</flux:text>

                            @if (! empty($documents[$key]))
                                <div class="flex flex-wrap gap-3 pt-1 text-sm">
                                    @foreach ($documents[$key] as $doc)
                                        <a href="{{ route('venue-documents.show', $doc) }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline dark:text-blue-400" wire:key="adoc-{{ $doc->id }}">
                                            📄 {{ $doc->original_name }}
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <flux:text class="pt-1 text-sm text-red-500">No document uploaded by the owner yet.</flux:text>
                            @endif
                        </div>

                        <flux:button size="sm" :variant="$venue->isItemVerified($key) ? 'filled' : 'primary'"
                            :disabled="! $venue->isItemVerified($key) && empty($documents[$key])"
                            wire:click="toggleVerified('{{ $key }}')">
                            {{ $venue->isItemVerified($key) ? 'Verified ✓' : 'Mark verified' }}
                        </flux:button>
                    </div>
                @endforeach
            </div>
        </div>
    @endunless

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 lg:items-start">
        {{-- LEFT: images --}}
        <div class="space-y-4">
            @if ($venue->imageUrl())
                <img src="{{ $venue->imageUrl() }}" alt="{{ $venue->name }}" class="h-48 w-full rounded-xl object-cover" />
            @else
                <div class="flex h-48 w-full items-center justify-center rounded-xl bg-zinc-100 text-sm text-zinc-400 dark:bg-zinc-800">No cover image</div>
            @endif

            @if ($venue->photos->isNotEmpty())
                <div>
                    <flux:text class="text-sm font-medium">Gallery ({{ $venue->photos->count() }})</flux:text>
                    <div class="mt-2 grid grid-cols-3 gap-2">
                        @foreach ($venue->photos as $photo)
                            <a href="{{ $photo->imageUrl() }}" target="_blank" rel="noopener noreferrer" wire:key="ap-{{ $photo->id }}">
                                <img src="{{ $photo->imageUrl() }}" alt="" class="h-20 w-full rounded-lg object-cover" />
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($venue->layoutImageUrl())
                <div>
                    <flux:text class="text-sm font-medium">Venue layout</flux:text>
                    <a href="{{ $venue->layoutImageUrl() }}" target="_blank" rel="noopener noreferrer">
                        <img src="{{ $venue->layoutImageUrl() }}" alt="" class="mt-2 max-h-48 w-full rounded-lg object-contain" />
                    </a>
                </div>
            @endif
        </div>

        {{-- RIGHT: owner, courts, profile --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Owner & account --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 space-y-3">
                <flux:heading size="lg">Owner &amp; account</flux:heading>
                <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-zinc-500">Owner</dt><dd class="font-medium">{{ $venue->owner->name }}</dd></div>
                    <div><dt class="text-zinc-500">Email</dt><dd>{{ $venue->owner->email }}</dd></div>
                    <div><dt class="text-zinc-500">Bank connected (payouts)</dt><dd>{{ $venue->owner->connect_onboarded ? 'Yes' : 'No' }}</dd></div>
                    <div><dt class="text-zinc-500">Owner suspended</dt><dd>{{ $venue->owner->is_suspended ? 'Yes' : 'No' }}</dd></div>
                    @if ($venue->owner->business_registration_number)
                        <div><dt class="text-zinc-500">Business reg. no.</dt><dd>{{ $venue->owner->business_registration_number }}</dd></div>
                    @endif
                    <div><dt class="text-zinc-500">Added</dt><dd>{{ $venue->created_at->format('d M Y') }}</dd></div>
                </dl>
            </div>

            {{-- Courts --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 space-y-3">
                <flux:heading size="lg">Courts ({{ $venue->courts->count() }})</flux:heading>
                @if ($venue->courts->isEmpty())
                    <flux:text class="text-zinc-400">No courts added yet.</flux:text>
                @else
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($venue->courts as $court)
                            <div class="flex items-center justify-between gap-3 py-2 text-sm" wire:key="ac-{{ $court->id }}">
                                <div><span class="font-medium">{{ $court->name }}</span> <span class="text-zinc-500">· {{ $court->sport }}</span></div>
                                @if ($court->is_active)
                                    <flux:badge color="green" size="sm">Active</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">Inactive</flux:badge>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Venue profile information --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 space-y-4 text-sm">
                <flux:heading size="lg">Venue information</flux:heading>

                @if ($venue->description)
                    <div>
                        <flux:text class="font-medium">Description</flux:text>
                        <div class="whitespace-pre-line text-zinc-500">{{ $venue->description }}</div>
                    </div>
                @endif

                @if ($venue->announcement)
                    <div>
                        <flux:text class="font-medium">Announcement {{ $venue->announcementVisible() ? '(showing to customers)' : '(hidden)' }}</flux:text>
                        <div class="text-zinc-500">{{ $venue->announcement }}</div>
                    </div>
                @endif

                <x-venue-map-link :venue="$venue" />

                {{-- Opening hours --}}
                @if ($venue->opening_hours)
                    <div class="space-y-1">
                        <flux:text class="font-medium">Opening hours</flux:text>
                        @foreach (config('courtgo.weekdays') as $dow => $label)
                            @php($h = $venue->opening_hours[$dow] ?? null)
                            <div class="flex justify-between gap-4">
                                <span class="text-zinc-500">{{ $label }}</span>
                                <span>
                                    @if ($h && empty($h['closed']) && ! empty($h['open']) && ! empty($h['close']))
                                        {{ \Illuminate\Support\Carbon::parse($h['open'])->format('g:i A') }} – {{ \Illuminate\Support\Carbon::parse($h['close'])->format('g:i A') }}
                                    @else
                                        Closed
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Pricing --}}
                @if ($priceRange)
                    <div>
                        <flux:text class="font-medium">Pricing</flux:text>
                        <div>RM {{ number_format(floor($priceRange['min']), 0) }}@if (ceil($priceRange['max']) != floor($priceRange['min'])) – RM {{ number_format(ceil($priceRange['max']), 0) }}@endif <span class="text-zinc-400">per slot</span></div>
                        @if ($venue->pricing_note)
                            <div class="text-zinc-500">{{ $venue->pricing_note }}</div>
                        @endif
                    </div>
                @elseif ($venue->pricing_note)
                    <div>
                        <flux:text class="font-medium">Pricing</flux:text>
                        <div class="text-zinc-500">{{ $venue->pricing_note }}</div>
                    </div>
                @endif

                {{-- Amenities --}}
                @php($amenityList = $venue->amenityLabels())
                @if (! empty($amenityList))
                    <div>
                        <flux:text class="font-medium">Amenities</flux:text>
                        <div class="mt-1 flex flex-wrap gap-2">
                            @foreach ($amenityList as $amenity)
                                <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-3 py-1 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                    <flux:icon :name="$amenity['icon']" class="size-3.5" />
                                    {{ $amenity['label'] }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Policy --}}
                @if ($venue->policy)
                    <div>
                        <flux:text class="font-medium">Policy</flux:text>
                        <div class="whitespace-pre-line text-zinc-500">{{ $venue->policy }}</div>
                    </div>
                @endif

                {{-- Contact --}}
                @php($hasContact = $venue->contact_phone || $venue->contact_whatsapp || $venue->contact_email || $venue->contact_website || $venue->contact_instagram || $venue->contact_facebook)
                @if ($hasContact)
                    <div class="space-y-1 [&_a]:text-blue-600 dark:[&_a]:text-blue-400 [&_a]:hover:underline">
                        <flux:text class="font-medium">Contact</flux:text>
                        @if ($venue->contact_phone)
                            <div><a href="tel:{{ $venue->contact_phone }}">📞 {{ $venue->contact_phone }}</a></div>
                        @endif
                        @if ($venue->contact_whatsapp)
                            <div><a href="https://wa.me/{{ preg_replace('/\D/', '', $venue->contact_whatsapp) }}" target="_blank" rel="noopener noreferrer">WhatsApp: {{ $venue->contact_whatsapp }}</a></div>
                        @endif
                        @if ($venue->contact_email)
                            <div><a href="mailto:{{ $venue->contact_email }}">✉️ {{ $venue->contact_email }}</a></div>
                        @endif
                        @if ($venue->contact_website)
                            <div><a href="{{ $venue->contact_website }}" target="_blank" rel="noopener noreferrer">{{ $venue->contact_website }}</a></div>
                        @endif
                        @if ($venue->contact_instagram)
                            <div>Instagram: {{ $venue->contact_instagram }}</div>
                        @endif
                        @if ($venue->contact_facebook)
                            <div>Facebook: {{ $venue->contact_facebook }}</div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
