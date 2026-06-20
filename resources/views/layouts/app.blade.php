<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}

        <x-site-footer class="mt-10" />
    </flux:main>
</x-layouts::app.sidebar>
