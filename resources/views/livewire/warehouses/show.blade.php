<div class="space-y-5">
    <x-page-header :title="$warehouse->name">
        <div class="flex flex-wrap items-center gap-2">
            <flux:link :href="route('warehouses.index')" wire:navigate class="text-sm">{{ __('Back to warehouses') }}</flux:link>
            <x-status-badge tone="neutral" size="sm">{{ str($warehouse->type->value)->title() }}</x-status-badge>
        </div>
        <div>{{ $warehouse->location }} · {{ $warehouse->project?->name ?? __('Central store') }}</div>

        <x-slot:actions>
            @if (Route::has('warehouses.ledger'))
                <flux:button icon="queue-list" :href="route('warehouses.ledger', $warehouse)" wire:navigate>
                    {{ __('View ledger') }}
                </flux:button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-panel title="{{ __('Current stock') }}" flush>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Item') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Unit') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Received') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Issued') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Balance') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($balances as $row)
                        <flux:table.row :key="str($row['item_description']).'-'.$row['unit']">
                            <flux:table.cell class="font-medium">{{ $row['item_description'] }}</flux:table.cell>
                            <flux:table.cell class="text-right">{{ $row['unit'] }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ $row['in_qty'] }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ $row['out_qty'] }}</flux:table.cell>
                            <flux:table.cell class="text-right font-semibold tabular-nums">{{ $row['balance_qty'] }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5">
                                <x-empty-state variant="table" title="{{ __('No stock yet') }}" description="{{ __('No ledger entries for this warehouse yet.') }}" />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </x-panel>

    <div class="grid gap-5 lg:grid-cols-2">
        <x-panel title="{{ __('Recent receipts') }}">
            <div class="space-y-2">
                @forelse ($receipts as $receipt)
                    <div class="flex items-center justify-between gap-3 border-b border-border py-2 last:border-0 last:pb-0">
                        <div>
                            @if (Route::has('receipts.show'))
                                <flux:link :href="route('receipts.show', $receipt)" wire:navigate class="font-mono text-sm font-medium">
                                    {{ $receipt->code }}
                                </flux:link>
                            @else
                                <flux:text class="font-mono text-sm font-medium">{{ $receipt->code }}</flux:text>
                            @endif
                            <flux:text class="block text-xs text-zinc-500">{{ $receipt->purchaseOrder?->code }}</flux:text>
                        </div>
                        <div class="text-right">
                            <div class="text-sm"><x-date :date="$receipt->received_date" /></div>
                            <x-status-badge :tone="$receipt->status === \App\Enums\ReceiptStatus::Confirmed ? 'success' : 'warning'" size="sm">
                                {{ str($receipt->status->value)->title() }}
                            </x-status-badge>
                        </div>
                    </div>
                @empty
                    <flux:text class="text-sm text-zinc-500">{{ __('No receipts yet.') }}</flux:text>
                @endforelse
            </div>
        </x-panel>

        <x-panel title="{{ __('Recent issues') }}">
            <div class="space-y-2">
                @forelse ($issues as $issue)
                    <div class="flex items-center justify-between gap-3 border-b border-border py-2 last:border-0 last:pb-0">
                        <div>
                            <flux:text class="font-mono text-sm font-medium">{{ $issue->code }}</flux:text>
                            <flux:text class="block text-xs text-zinc-500">{{ $issue->project?->code }}</flux:text>
                        </div>
                        <div class="text-sm"><x-date :date="$issue->issued_date" /></div>
                    </div>
                @empty
                    <flux:text class="text-sm text-zinc-500">{{ __('No issues yet.') }}</flux:text>
                @endforelse
            </div>
        </x-panel>
    </div>
</div>
