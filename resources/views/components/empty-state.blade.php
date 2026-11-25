@props([
    'title',
    'description' => null,
    'cta' => null,
    'href' => null,
    'variant' => 'page',
])

@php
    $compact = $variant === 'table';
@endphp

<div {{ $attributes->merge([
    'class' => $compact
        ? 'flex flex-col items-center justify-center px-4 py-8 text-center'
        : 'flex flex-col items-center justify-center rounded-xl border border-dashed border-border bg-surface-subtle px-6 py-12 text-center',
]) }}>
    @isset($icon)
        {{ $icon }}
    @else
        <flux:icon.inbox class="{{ $compact ? 'size-6' : 'size-8' }} text-zinc-400" />
    @endisset

    <flux:heading :size="$compact ? 'sm' : 'md'" class="mt-3">{{ $title }}</flux:heading>

    @if ($description)
        <flux:text class="mt-1 max-w-md text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</flux:text>
    @endif

    @isset($actions)
        <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
            {{ $actions }}
        </div>
    @elseif ($cta && $href)
        <flux:button class="mt-4" variant="primary" :href="$href" wire:navigate>
            {{ $cta }}
        </flux:button>
    @endif
</div>
