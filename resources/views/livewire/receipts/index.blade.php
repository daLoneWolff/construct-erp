<div class="space-y-5">
    <x-page-header
        title="{{ __('Material receipts') }}"
        description="{{ __('Confirmed goods received notes and their stock impact.') }}"
    >
        <x-slot:actions>
            @can('receive')
                @if (Route::has('purchase-orders.index'))
                    <flux:button icon="truck" :href="route('purchase-orders.index')" wire:navigate>
                        {{ __('Receive against a PO') }}
                    </flux:button>
                @endif
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-panel flush>
        <x-filter-bar>
            <flux:select wire:model.live="status" :label="__('Status')" class="w-full sm:max-w-xs">
                <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
                @foreach ($statuses as $status)
                    <flux:select.option :value="$status->value">
                        {{ str($status->value)->title() }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </x-filter-bar>

        <div class="overflow-x-auto border-t border-border">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Receipt') }}</flux:table.column>
                    <flux:table.column>{{ __('PO') }}</flux:table.column>
                    <flux:table.column>{{ __('Vendor') }}</flux:table.column>
                    <flux:table.column>{{ __('Warehouse') }}</flux:table.column>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Lines') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($receipts as $receipt)
                        <flux:table.row :key="$receipt->id">
                            <flux:table.cell>
                                <flux:link :href="route('receipts.show', $receipt)" wire:navigate class="font-mono font-medium">
                                    {{ $receipt->code }}
                                </flux:link>
                            </flux:table.cell>
                            <flux:table.cell class="font-mono">{{ $receipt->purchaseOrder?->code }}</flux:table.cell>
                            <flux:table.cell>{{ $receipt->purchaseOrder?->vendor?->name }}</flux:table.cell>
                            <flux:table.cell>{{ $receipt->warehouse?->name }}</flux:table.cell>
                            <flux:table.cell><x-date :date="$receipt->received_date" /></flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ $receipt->items_count }}</flux:table.cell>
                            <flux:table.cell>
                                <x-status-badge :tone="$receipt->status === \App\Enums\ReceiptStatus::Confirmed ? 'success' : 'warning'" size="sm">
                                    {{ str($receipt->status->value)->title() }}
                                </x-status-badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7">
                                <x-empty-state variant="table" title="{{ __('No receipts found') }}" description="{{ __('Receipts will appear here once material is received against purchase orders.') }}" />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        @if ($receipts->hasPages())
            <div class="border-t border-border px-5 py-3">
                {{ $receipts->links() }}
            </div>
        @endif
    </x-panel>
</div>
