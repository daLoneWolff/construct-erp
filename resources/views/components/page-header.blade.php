@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-end justify-between gap-4']) }}>
    <div class="min-w-0 space-y-1">
        <flux:heading size="xl" class="text-balance">{{ $title }}</flux:heading>

        @if ($description)
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</flux:text>
        @elseif (trim($slot) !== '')
            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $slot }}</div>
        @endif
    </div>

    @isset($actions)
        <div class="flex w-full min-w-0 max-w-full flex-wrap items-center gap-2 sm:w-auto">
            {{ $actions }}
        </div>
    @endisset
</div>
