<div class="space-y-6">
    <div class="space-y-1">
        <flux:button size="sm" variant="ghost" :href="route('help')" wire:navigate icon="arrow-left">Help center</flux:button>
        <flux:heading size="xl" class="!text-2xl !font-bold tracking-tight">Send us feedback</flux:heading>
        <flux:text>Found a bug, stuck on something, or have an idea? Tell us — we read everything.</flux:text>
    </div>

    @if ($sent)
        <div class="flex flex-col items-center gap-3 rounded-2xl border border-green-200 bg-green-50 p-10 text-center dark:border-green-900 dark:bg-green-950/30">
            <flux:icon name="check-circle" class="size-10 text-green-600" />
            <flux:heading size="lg">Thanks for the feedback!</flux:heading>
            <flux:text class="text-zinc-600 dark:text-zinc-400">We've received your message and will look into it.</flux:text>
            <div class="flex flex-wrap justify-center gap-2 pt-2">
                <flux:button variant="ghost" wire:click="sendAnother">Send another</flux:button>
                <flux:button variant="primary" :href="route('help')" wire:navigate>Back to help</flux:button>
            </div>
        </div>
    @else
        <form wire:submit="submit" class="space-y-5 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:select wire:model="category" label="What's this about?">
                @foreach ($categories as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="email" type="email" label="Your email" placeholder="you@example.com" description="So we can reply if needed." />

            <flux:textarea wire:model="message" label="Your message" rows="5" placeholder="Tell us what happened, or what you'd like to see…" />

            <flux:button type="submit" variant="primary" icon="paper-airplane" wire:loading.attr="disabled" wire:target="submit">Send feedback</flux:button>
        </form>
    @endif
</div>
