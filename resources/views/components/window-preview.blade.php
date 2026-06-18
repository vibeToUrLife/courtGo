@props(['preview' => ['state' => 'empty', 'slots' => []], 'hours' => ''])

{{-- Shows the slots a window+duration would create, styled like the court-name
     preview (boxed "Preview" + blue badges), or a "doesn't match" hint.
     Named window-preview, not slot-preview — an "x-slot…" name collides with
     Blade's <x-slot> directive. --}}
@if ($preview['state'] === 'ok')
    <div class="rounded-lg bg-zinc-50 dark:bg-zinc-900 p-4">
        <flux:text class="text-sm font-medium">Preview — {{ count($preview['slots']) }} slot{{ count($preview['slots']) === 1 ? '' : 's' }}</flux:text>
        <div class="mt-2 flex flex-wrap gap-2">
            @foreach ($preview['slots'] as $slot)
                <flux:badge size="sm" color="blue">
                    {{ \Illuminate\Support\Carbon::parse($slot['start'])->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse($slot['end'])->format('g:i A') }}
                </flux:badge>
            @endforeach
        </div>
    </div>
@elseif ($preview['state'] === 'mismatch')
    <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 p-4">
        <flux:text class="text-sm font-medium text-amber-700 dark:text-amber-400">
            ⚠ This time range doesn't divide evenly into equal slots — adjust the window or the slot length.
        </flux:text>
    </div>
@endif
