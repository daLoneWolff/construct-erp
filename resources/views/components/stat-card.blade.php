@props(['label', 'value' => null, 'paisa' => null, 'hint' => null, 'tone' => null, 'decimals' => null, 'suffix' => null])

<flux:card {{ $attributes->merge(['class' => 'min-w-0 space-y-1.5']) }}>
    <flux:text class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ $label }}</flux:text>

    <div class="min-w-0 max-w-full break-words text-xl font-semibold leading-tight tracking-tight tabular-nums [overflow-wrap:anywhere] sm:text-2xl {{ match ($tone) {
        'red' => 'text-danger',
        'amber' => 'text-warning',
        'green' => 'text-success',
        default => '',
    } }}">
        @if ($paisa !== null)
            <x-money :paisa="$paisa" />
        @elseif ($decimals !== null)
            <x-number :value="$value" :decimals="$decimals" :suffix="$suffix ?? ''" />
        @else
            {{ $value ?? '—' }}
        @endif
    </div>

    @if ($hint)
        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</flux:text>
    @endif

    @isset($footer)
        <div class="pt-1">
            {{ $footer }}
        </div>
    @endisset
</flux:card>
