<div class="space-y-6 p-6 max-w-4xl mx-auto w-full">
    <flux:heading size="xl">Manage Owners</flux:heading>
    <flux:text>Suspend an owner to immediately hide all their courts from customers.</flux:text>

    @if ($owners->isEmpty())
        <flux:text class="text-zinc-400">No owners yet.</flux:text>
    @else
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 font-medium">Owner</th>
                        <th class="px-4 py-3 font-medium">Venues</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($owners as $owner)
                        <tr wire:key="owner-{{ $owner->id }}">
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $owner->name }}</div>
                                <div class="text-zinc-500">{{ $owner->email }}</div>
                            </td>
                            <td class="px-4 py-3 text-zinc-500">{{ $owner->venues_count }}</td>
                            <td class="px-4 py-3">
                                @if ($owner->is_suspended)
                                    <flux:badge color="red" size="sm">Suspended</flux:badge>
                                @else
                                    <flux:badge color="green" size="sm">Active</flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($owner->is_suspended)
                                    <flux:button size="sm" variant="primary" wire:click="toggleSuspend({{ $owner->id }})">Unsuspend</flux:button>
                                @else
                                    <flux:button size="sm" variant="danger" wire:click="toggleSuspend({{ $owner->id }})" wire:confirm="Suspend this owner? Their courts will be hidden.">Suspend</flux:button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
