<div class="space-y-5">
    <x-page-header :title="$receipt->warehouse?->name ?? __('Receipt')">
        <div class="flex flex-wrap items-center gap-2">
            <flux:link :href="route('receipts.index')" wire:navigate class="text-sm">{{ __('Back to receipts') }}</flux:link>
            <flux:badge color="zinc" size="sm" class="font-mono">{{ $receipt->code }}</flux:badge>
            <x-status-badge :tone="$receipt->status === \App\Enums\ReceiptStatus::Confirmed ? 'success' : 'warning'" size="sm">
                {{ str($receipt->status->value)->title() }}
            </x-status-badge>
        </div>

        <div>
            {{ __('Received') }} <x-date :date="$receipt->received_date" /> · {{ $receipt->receivedBy?->name }}
        </div>

        <x-slot:actions>
            @if (Route::has('purchase-orders.show'))
                <flux:button icon="shopping-cart" :href="route('purchase-orders.show', $receipt->purchaseOrder)" wire:navigate>
                    {{ $receipt->purchaseOrder?->code }}
                </flux:button>
            @endif
            @if (Route::has('warehouses.ledger'))
                <flux:button icon="queue-list" :href="route('warehouses.ledger', $receipt->warehouse)" wire:navigate>
                    {{ __('Ledger') }}
                </flux:button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-panel title="{{ __('Received lines') }}" flush>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Description') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Unit') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Ordered') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Received') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Rejected') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Accepted to stock') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($items as $item)
                        <flux:table.row :key="$item->id">
                            <flux:table.cell class="font-medium">{{ $item->purchaseOrderItem?->description }}</flux:table.cell>
                            <flux:table.cell class="text-right">{{ $item->purchaseOrderItem?->unit }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ $item->ordered_qty }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ $item->received_qty }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ $item->rejected_qty }}</flux:table.cell>
                            <flux:table.cell class="text-right font-semibold tabular-nums">{{ $item->acceptedQty }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </x-panel>

    @if ($receipt->notes)
        <x-panel title="{{ __('Notes') }}">
            <flux:text>{{ $receipt->notes }}</flux:text>
        </x-panel>
    @endif
</div>
