@props([
    'options' => [],
    'placeholder' => 'Select…',
    'name' => null,        // plain <form> field name (for non-Livewire forms)
    'wireModel' => null,   // Livewire property to bind to
    'live' => false,       // update Livewire immediately on selection
    'value' => '',         // initial value
    'label' => null,
])

@php($opts = collect($options)->values()->all())

<div>
    @if ($label)
        <flux:label>{{ $label }}</flux:label>
    @endif

    <div
        wire:ignore
        x-data="{
            open: false,
            search: @js((string) $value),
            selected: @js((string) $value),
            options: @js($opts),
            get filtered() {
                const q = this.search.toLowerCase().trim();
                return q === '' ? this.options : this.options.filter(o => o.toLowerCase().includes(q));
            },
            sync(val) {
                this.selected = val;
                @if ($wireModel) $wire.set(@js($wireModel), val, {{ $live ? 'true' : 'false' }}); @endif
            },
            onInput() {
                this.open = true;
                const m = this.options.find(o => o.toLowerCase() === this.search.toLowerCase().trim());
                if (m) this.sync(m);
                else if (this.search.trim() === '') this.sync('');
            },
            choose(o) { this.search = o; this.open = false; this.sync(o); },
        }"
        x-on:click.outside="open = false"
        x-on:keydown.escape="open = false"
        x-on:clear-search-select.window="search = ''; selected = ''"
        class="relative mt-1"
    >
        <input type="text"
               x-model="search"
               x-on:input="onInput()"
               x-on:focus="open = true"
               placeholder="{{ $placeholder }}"
               autocomplete="off"
               class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" />

        @if ($name)
            <input type="hidden" name="{{ $name }}" x-bind:value="selected" />
        @endif

        <div x-show="open" x-cloak
             class="absolute z-30 mt-1 max-h-56 w-full overflow-auto rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
            <template x-for="o in filtered" :key="o">
                <button type="button" x-on:click="choose(o)"
                        class="block w-full px-3 py-2 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800" x-text="o"></button>
            </template>
            <div x-show="filtered.length === 0" class="px-3 py-2 text-sm text-zinc-400">No matches</div>
        </div>
    </div>

    @if ($wireModel)
        <flux:error name="{{ $wireModel }}" />
    @endif
</div>
