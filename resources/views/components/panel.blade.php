@props(['title' => null, 'description' => null, 'flush' => false])

<flux:card {{ $attributes->merge(['class' => 'overflow-hidden']) }}>
    @if ($title || isset($actions))
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-border px-5 py-4">
            <div class="min-w-0 space-y-0.5">
                @if ($title)
                    <flux:heading size="sm">{{ $title }}</flux:heading>
                @endif

                @if ($description)
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ $description }}</flux:text>
                @endif
            </div>

            @isset($actions)
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif

    <div class="{{ $flush ? '' : 'p-5' }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t border-border bg-surface-subtle px-5 py-3">
            {{ $footer }}
        </div>
    @endisset
</flux:card>
