@props([
    'title',
    'description',
])

<div {{ $attributes->merge(['class' => 'flex w-full flex-col gap-2 text-center']) }}>
    <flux:heading size="xl" level="1" class="text-balance">{{ $title }}</flux:heading>
    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</flux:text>
</div>
