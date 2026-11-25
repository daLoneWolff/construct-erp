<div class="space-y-5">
    <x-page-header
        title="{{ __('Stock ledger') }}"
        description="{{ __('Every receipt and issue posts one immutable ledger row.') }}"
    >
        <x-slot:actions>
            @if (Route::has('warehouses.index'))
                <flux:button icon="archive-box" :href="route('warehouses.index')" wire:navigate>
                    {{ __('Warehouses') }}
                </flux:button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-panel title="{{ __('Select a warehouse') }}">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($warehouses as $row)
                @php $warehouse = $row['warehouse']; @endphp
                <flux:button
                    variant="{{ $warehouse->is($selectedWarehouse) ? 'primary' : 'ghost' }}"
                    :href="route('warehouses.ledger', $warehouse)"
                    wire:navigate
                    class="justify-start"
                >
                    <span class="text-left">
                        <span class="block font-medium">{{ $warehouse->name }}</span>
                        <span class="block text-xs opacity-70">{{ $warehouse->location }}</span>
                    </span>
                </flux:button>
            @endforeach
        </div>
    </x-panel>

    @if ($selectedWarehouse !== null)
        <x-panel title="{{ __('Closing balances — :name', ['name' => $selectedWarehouse->name]) }}">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @forelse ($balances as $row)
                    <div class="rounded-xl border border-border bg-surface-subtle p-3">
                        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ $row['item_description'] }}</flux:text>
                        <div class="mt-1 text-lg font-semibold tabular-nums">{{ $row['balance_qty'] }} {{ $row['unit'] }}</div>
                    </div>
                @empty
                    <flux:text class="text-sm text-zinc-500">{{ __('No stock has been recorded here.') }}</flux:text>
                @endforelse
            </div>
        </x-panel>

        <x-panel title="{{ __('Transactions') }}" flush>
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Date') }}</flux:table.column>
                        <flux:table.column>{{ __('Item') }}</flux:table.column>
                        <flux:table.column>{{ __('Type') }}</flux:table.column>
                        <flux:table.column>{{ __('Reference') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('In') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('Out') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('Balance') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($entries as $entry)
                            <flux:table.row :key="$entry->id">
                                <flux:table.cell><x-date :date="$entry->transaction_date" /></flux:table.cell>
                                <flux:table.cell class="font-medium">{{ $entry->item_description }}</flux:table.cell>
                                <flux:table.cell>
                                    <x-status-badge
                                        :tone="$entry->transaction_type === \App\Enums\StockTransactionType::Receipt ? 'success' : 'warning'"
                                        size="sm"
                                    >
                                        {{ str($entry->transaction_type->value)->title() }}
                                    </x-status-badge>
                                </flux:table.cell>
                                <flux:table.cell class="font-mono text-xs text-zinc-500">
                                    {{ class_basename($entry->reference_type) }} #{{ $entry->reference_id }}
                                </flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums">{{ $entry->in_qty }}</flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums">{{ $entry->out_qty }}</flux:table.cell>
                                <flux:table.cell class="text-right font-semibold tabular-nums">{{ $entry->balance_qty }} {{ $entry->unit }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="7">
                                    <x-empty-state variant="table" title="{{ __('No transactions recorded') }}" description="{{ __('Receipts and issues will appear here.') }}" />
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            @if ($entries->hasPages())
                <div class="border-t border-border px-4 py-3">
                    {{ $entries->links() }}
                </div>
            @endif
        </x-panel>
    @else
        <flux:callout variant="info" icon="information-circle">
            {{ __('Choose a warehouse above to see its running balance and transactions.') }}
        </flux:callout>
    @endif
</div>
