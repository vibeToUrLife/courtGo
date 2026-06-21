<div class="space-y-8 p-6 max-w-3xl mx-auto w-full">
    <div class="space-y-1">
        <flux:button size="sm" variant="ghost" :href="route('owner.venues.courts', $venue)" wire:navigate icon="arrow-left">
            Back to courts
        </flux:button>
        <flux:heading size="xl">{{ $venue->name }} — Profile</flux:heading>
        <flux:text>{{ $venue->city }}, {{ $venue->state }}</flux:text>
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle">
            <flux:callout.text>{{ session('status') }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- Amenities (Livewire — saves automatically when toggled) --}}
    <div class="space-y-3 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <flux:heading size="lg">Amenities</flux:heading>
        <flux:text class="text-sm text-zinc-500">Tick everything your venue offers — changes save automatically.</flux:text>

        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
            @foreach ($allAmenities as $key => $meta)
                <flux:checkbox wire:model.live="amenities" value="{{ $key }}" label="{{ $meta['label'] }}" />
            @endforeach
        </div>
        @error('amenities.*') <flux:text class="text-sm text-red-600">{{ $message }}</flux:text> @enderror
    </div>

    {{-- Venue details (Livewire) --}}
    <div id="venue-details" class="space-y-5 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5"
         x-data
         x-on:profile-error.window="$nextTick(() => { $el.scrollIntoView({ behavior: 'smooth', block: 'start' }); $el.querySelector('[aria-invalid=\'true\'], [data-invalid]')?.focus?.(); })">
        <flux:heading size="lg">Venue details</flux:heading>

        @if ($errors->any())
            <flux:callout variant="danger" icon="exclamation-triangle">
                <flux:callout.heading>Please fix the highlighted fields below.</flux:callout.heading>
            </flux:callout>
        @endif

        {{-- Announcement --}}
        <div class="space-y-2">
            <flux:textarea wire:model="announcement" label="Announcement" placeholder="e.g. Closed 31 Aug for maintenance" rows="2" />
            <div class="flex flex-wrap items-end gap-4">
                <flux:checkbox wire:model.live="announcementActive" label="Show this announcement" />
                <flux:input type="date" wire:model="announcementUntil" label="Hide after (optional)" :min="now()->toDateString()" class="max-w-[12rem]" />
            </div>
        </div>

        <flux:separator variant="subtle" />

        {{-- Opening hours --}}
        <div class="space-y-2">
            <flux:text class="font-medium">Opening hours</flux:text>

            {{-- Bulk set: same hours for every day --}}
            <div class="flex flex-wrap items-end gap-2 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">
                <flux:input type="time" wire:model="bulkOpen" label="From" class="max-w-[8rem]" />
                <flux:input type="time" wire:model="bulkClose" label="Until" class="max-w-[8rem]" />
                <flux:button size="sm" wire:click="applyHoursToAll">Apply to all days</flux:button>
                <flux:button size="sm" variant="ghost" wire:click="closeAllDays">Close all days</flux:button>
            </div>

            @foreach ($weekdays as $dow => $label)
                <div class="flex flex-wrap items-center gap-3" wire:key="oh-{{ $dow }}">
                    <span class="w-24 text-sm">{{ $label }}</span>
                    <flux:checkbox wire:model.live="openingHours.{{ $dow }}.closed" label="Closed" />
                    @unless ($openingHours[$dow]['closed'])
                        <flux:input type="time" wire:model="openingHours.{{ $dow }}.open" class="max-w-[8rem]" />
                        <span class="text-zinc-400">–</span>
                        <flux:input type="time" wire:model="openingHours.{{ $dow }}.close" class="max-w-[8rem]" />
                    @endunless
                </div>
            @endforeach
        </div>

        <flux:separator variant="subtle" />

        <flux:input wire:model="pricingNote" label="Pricing note (optional)" placeholder="e.g. Peak RM45 / off-peak RM30" />

        <flux:textarea wire:model="policy" label="Policy (optional)" placeholder="Cancellation, rules, etc." rows="3" />

        {{-- Contact --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <flux:input wire:model="contactPhone" label="Phone" />
            <flux:input wire:model="contactWhatsapp" label="WhatsApp" placeholder="60123456789" />
            <flux:input wire:model="contactEmail" type="email" label="Email" />
            <flux:input wire:model="contactWebsite" label="Website" placeholder="https://…" />
            <flux:input wire:model="contactInstagram" label="Instagram" placeholder="@handle or link" />
            <flux:input wire:model="contactFacebook" label="Facebook" placeholder="page name or link" />
        </div>

        <flux:button variant="primary" wire:click="saveInfo">Save details</flux:button>
    </div>

    {{-- Cover photo (plain HTTP form → media controller, returns here) --}}
    <div class="space-y-3 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <flux:heading size="lg">Cover photo</flux:heading>
        @if ($venue->imageUrl())
            <img src="{{ $venue->imageUrl() }}" alt="" class="h-40 w-full rounded-lg object-cover" />
        @endif
        <form method="POST" action="{{ route('owner.venues.media.cover', $venue) }}" enctype="multipart/form-data" class="flex items-center gap-3">
            @csrf
            <input type="file" name="photo" accept="image/*" required class="text-sm" />
            <flux:button type="submit" variant="primary" size="sm">Upload cover</flux:button>
        </form>
    </div>

    {{-- Gallery (plain HTTP forms → VenueMediaController) --}}
    <div class="space-y-3 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <flux:heading size="lg">Photo gallery</flux:heading>
        <flux:text class="text-sm text-zinc-500">Up to 12 photos of your courts and facility.</flux:text>

        @if ($photos->isNotEmpty())
            <div class="grid grid-cols-3 gap-2 sm:grid-cols-4">
                @foreach ($photos as $photo)
                    <div class="relative" wire:key="photo-{{ $photo->id }}">
                        <img src="{{ $photo->imageUrl() }}" alt="" class="h-24 w-full rounded-lg object-cover" />
                        <form method="POST" action="{{ route('owner.venues.media.photos.destroy', [$venue, $photo]) }}" class="absolute right-1 top-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-full bg-black/60 px-2 text-xs text-white hover:bg-black/80">&times;</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($photos->count() < 12)
            <form method="POST" action="{{ route('owner.venues.media.photos.store', $venue) }}" enctype="multipart/form-data" class="space-y-1">
                @csrf
                <div class="flex items-center gap-3">
                    <input type="file" name="photos[]" accept="image/*" multiple required class="text-sm" />
                    <flux:button type="submit" variant="primary" size="sm">Add photos</flux:button>
                </div>
                <flux:text class="text-xs text-zinc-400">Pick several at once — up to {{ 12 - $photos->count() }} more.</flux:text>
            </form>
        @else
            <flux:text class="text-sm text-zinc-400">Gallery is full (12 photos).</flux:text>
        @endif
    </div>

    {{-- Venue layout / floor plan (plain HTTP form → media controller) --}}
    <div class="space-y-3 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <flux:heading size="lg">Venue layout</flux:heading>
        <flux:text class="text-sm text-zinc-500">A floor-plan or layout image of your venue (optional).</flux:text>
        @if ($venue->layoutImageUrl())
            <img src="{{ $venue->layoutImageUrl() }}" alt="" class="max-h-64 w-full rounded-lg object-contain" />
        @endif
        <form method="POST" action="{{ route('owner.venues.media.layout', $venue) }}" enctype="multipart/form-data" class="flex items-center gap-3">
            @csrf
            <input type="file" name="photo" accept="image/*" required class="text-sm" />
            <flux:button type="submit" variant="primary" size="sm">Upload layout</flux:button>
        </form>
    </div>
</div>
