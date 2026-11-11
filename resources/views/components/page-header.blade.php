@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->class('flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between') }}>
    <div>
        <flux:heading size="xl">{{ $title }}</flux:heading>
        @if ($subtitle)
            <flux:subheading>{{ $subtitle }}</flux:subheading>
        @endif
    </div>

    @if ($slot->isNotEmpty())
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
