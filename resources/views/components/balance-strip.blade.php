@props([
    'agreed' => 0,
    'paid' => 0,
    'balance' => 0,
    'title' => 'Contractor balance',
    'description' => null,
])

<x-panel {{ $attributes }} :title="$title" :description="$description">
    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="min-w-0 space-y-1">
            <flux:text class="text-xs font-medium tracking-wide text-zinc-500 uppercase dark:text-zinc-400">
                {{ __('Agreed') }}
            </flux:text>
            <div class="min-w-0 max-w-full break-words text-xl font-semibold leading-tight tabular-nums [overflow-wrap:anywhere]">
                <x-money :paisa="$agreed" />
            </div>
        </div>

        <div class="min-w-0 space-y-1">
            <flux:text class="text-xs font-medium tracking-wide text-zinc-500 uppercase dark:text-zinc-400">
                {{ __('Paid') }}
            </flux:text>
            <div class="min-w-0 max-w-full break-words text-xl font-semibold leading-tight tabular-nums [overflow-wrap:anywhere]">
                <x-money :paisa="$paid" />
            </div>
        </div>

        <div class="min-w-0 space-y-1 rounded-xl bg-warning/10 p-3 sm:-my-3 sm:-me-3">
            <flux:text class="text-xs font-medium tracking-wide text-warning uppercase">
                {{ __('Outstanding') }}
            </flux:text>
            <div class="min-w-0 max-w-full break-words text-xl font-semibold leading-tight tracking-tight tabular-nums text-warning [overflow-wrap:anywhere] sm:text-2xl">
                <x-money :paisa="$balance" />
            </div>
        </div>
    </div>

    @isset($footer)
        <x-slot:footer>{{ $footer }}</x-slot:footer>
    @endisset
</x-panel>
