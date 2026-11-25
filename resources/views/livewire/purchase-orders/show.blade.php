<div class="space-y-5">
    <x-page-header :title="$order->vendor->name">
        <div class="flex flex-wrap items-center gap-2">
            <flux:link :href="route('purchase-orders.index')" wire:navigate class="text-sm">{{ __('Back to purchase orders') }}</flux:link>
            <flux:badge color="zinc" size="sm" class="font-mono">{{ $order->code }}</flux:badge>
            <x-status-badge :tone="match ($order->status) {
                \App\Enums\PurchaseOrderStatus::Approved,
                \App\Enums\PurchaseOrderStatus::Sent,
                \App\Enums\PurchaseOrderStatus::PartiallyReceived => 'success',
                \App\Enums\PurchaseOrderStatus::Closed => 'neutral',
                default => 'warning',
            }">
                {{ str($order->status->value)->replace('_', ' ')->title() }}
            </x-status-badge>
        </div>

        <div>
            {{ $order->project->code }} — {{ $order->project->name }} ·
            {{ __('Ordered') }} <x-date :date="$order->order_date" />
        </div>

        <x-slot:actions>
            <flux:button icon="printer" :href="route('purchase-orders.print', $order)">
                {{ __('Print') }}
            </flux:button>

            @can('approve')
                @if ($order->status === \App\Enums\PurchaseOrderStatus::Pending)
                    <flux:button variant="primary" icon="check" wire:click="approve">
                        {{ __('Approve') }}
                    </flux:button>
                @endif
            @endcan

            @can('manage')
                @if ($order->status === \App\Enums\PurchaseOrderStatus::Approved)
                    <flux:button icon="paper-airplane" wire:click="markSent">
                        {{ __('Mark as sent') }}
                    </flux:button>
                @endif
            @endcan

            @if (Route::has('purchase-orders.receipts.create'))
                <flux:button icon="truck" :href="route('purchase-orders.receipts.create', $order)" wire:navigate>
                    {{ __('Receive material') }}
                </flux:button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Subtotal" :paisa="$totals['subtotal_paisa']" />
        <x-stat-card label="Invoice total" :paisa="$totals['invoice_total_paisa']" />
        <x-stat-card label="Net payable" :paisa="$totals['net_payable_paisa']" tone="green" />
    </div>

    <x-panel title="{{ __('Order items') }}" flush>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Description') }}</flux:table.column>
                    <flux:table.column>{{ __('BOQ item') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Qty') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Rate') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Amount') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($items as $item)
                        <flux:table.row :key="$item->id">
                            <flux:table.cell class="font-medium">{{ $item->description }}</flux:table.cell>
                            <flux:table.cell>{{ $item->boqItem?->code ?? '—' }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ $item->quantity }} {{ $item->unit }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$item->rate_paisa" /></flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums font-medium"><x-money :paisa="$item->amount_paisa" /></flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </x-panel>

    <div class="grid gap-5 lg:grid-cols-2">
        <x-panel title="{{ __('Tax summary') }}">
            <div class="space-y-2">
                <div class="flex justify-between gap-4 text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('Subtotal') }}</span>
                    <span class="tabular-nums"><x-money :paisa="$totals['subtotal_paisa']" /></span>
                </div>
                <div class="flex justify-between gap-4 text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('VAT @ :rate%', ['rate' => $vatRate]) }}</span>
                    <span class="tabular-nums"><x-money :paisa="$totals['vat_paisa']" /></span>
                </div>
                <div class="flex justify-between gap-4 border-t border-border pt-2 font-medium">
                    <span>{{ __('Invoice total') }}</span>
                    <span class="tabular-nums"><x-money :paisa="$totals['invoice_total_paisa']" /></span>
                </div>
                <div class="flex justify-between gap-4 text-sm text-zinc-500 dark:text-zinc-400">
                    <span>{{ __('TDS withheld @ :rate%', ['rate' => $order->vendor->tds_rate]) }}</span>
                    <span class="tabular-nums"><x-money :paisa="$totals['tds_paisa']" /></span>
                </div>
                <div class="flex justify-between gap-4 border-t border-border pt-2 text-lg font-semibold">
                    <span>{{ __('Net payable to vendor') }}</span>
                    <span class="tabular-nums"><x-money :paisa="$totals['net_payable_paisa']" /></span>
                </div>
            </div>
        </x-panel>

        <x-panel title="{{ __('Order details') }}">
            <div class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('Expected delivery') }}</span>
                    <div><x-date :date="$order->expected_delivery" /></div>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('Approved by') }}</span>
                    <div>{{ $order->approvedBy?->name ?? '—' }}</div>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('Approved at') }}</span>
                    <div><x-date :date="$order->approved_at" :time="true" /></div>
                </div>
                @if ($order->requisition)
                    <div class="flex justify-between gap-4">
                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('Source requisition') }}</span>
                        <flux:link :href="route('requisitions.show', $order->requisition)" wire:navigate>
                            {{ $order->requisition->code }}
                        </flux:link>
                    </div>
                @endif
                @if ($order->notes)
                    <div class="border-t border-border pt-3">
                        <flux:text class="text-sm">{{ $order->notes }}</flux:text>
                    </div>
                @endif
            </div>
        </x-panel>
    </div>
</div>
