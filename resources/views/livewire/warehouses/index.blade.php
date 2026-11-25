<div class="space-y-5">
    <x-page-header
        title="{{ __('Warehouses') }}"
        description="{{ __('Stock is derived from confirmed receipts and issues.') }}"
    >
        <x-slot:actions>
            @if (Route::has('ledger.index'))
                <flux:button icon="queue-list" :href="route('ledger.index')" wire:navigate>
                    {{ __('Open stock ledger') }}
                </flux:button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($warehouses as $row)
            @php $warehouse = $row['warehouse']; @endphp
            <x-panel>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <flux:heading size="sm">{{ $warehouse->name }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500">{{ $warehouse->location }}</flux:text>
                    </div>
                    <x-status-badge tone="neutral" size="sm">
                        {{ str($warehouse->type->value)->title() }}
                    </x-status-badge>
                </div>

                <flux:text class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ $warehouse->project?->name ?? __('Central store') }}
                </flux:text>

                <div class="mt-4 flex items-center justify-between gap-3 border-t border-border pt-4">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __(':count items in stock', ['count' => $row['item_count']]) }}
                    </flux:text>
                    <div class="flex flex-wrap gap-2">
                        <flux:button size="sm" variant="ghost" :href="route('warehouses.show', $warehouse)" wire:navigate>
                            {{ __('View stock') }}
                        </flux:button>
                        @if (Route::has('warehouses.ledger'))
                            <flux:button size="sm" variant="ghost" :href="route('warehouses.ledger', $warehouse)" wire:navigate>
                                {{ __('Ledger') }}
                            </flux:button>
                        @endif
                    </div>
                </div>
            </x-panel>
        @endforeach
    </div>
</div>
