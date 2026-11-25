<div class="space-y-5">
    <x-page-header :title="$vendor->name">
        <div class="flex flex-wrap items-center gap-2">
            <flux:link :href="route('vendors.index')" wire:navigate class="text-sm">{{ __('Back to vendors') }}</flux:link>
            <flux:badge color="zinc" size="sm" class="font-mono">{{ $vendor->pan }}</flux:badge>
        </div>

        <div>{{ $vendor->address }} · {{ $vendor->contact_person }} · {{ $vendor->phone }}</div>

        <x-slot:actions>
            @can('manage')
                <flux:button icon="pencil-square" :href="route('vendors.edit', $vendor)" wire:navigate>
                    {{ __('Edit') }}
                </flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="TDS rate" :value="$vendor->tds_rate.'%'" />
        <x-stat-card label="Total payable" :paisa="$totalPayablePaisa" />
        <x-stat-card label="Purchase orders" :value="$purchaseOrders->count()" />
    </div>

    <x-panel title="{{ __('Purchase orders') }}" flush>
        @if ($purchaseOrders->isEmpty())
            <div class="p-5">
                <flux:text class="text-sm text-zinc-500">{{ __('No purchase orders for this vendor yet.') }}</flux:text>
            </div>
        @else
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Code') }}</flux:table.column>
                        <flux:table.column>{{ __('Project') }}</flux:table.column>
                        <flux:table.column>{{ __('Order date') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('Net payable') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($purchaseOrders as $order)
                            <flux:table.row :key="$order->id">
                                <flux:table.cell>
                                    <flux:link :href="route('purchase-orders.show', $order)" wire:navigate class="font-medium">
                                        {{ $order->code }}
                                    </flux:link>
                                </flux:table.cell>
                                <flux:table.cell>{{ $order->project->code }}</flux:table.cell>
                                <flux:table.cell><x-date :date="$order->order_date" /></flux:table.cell>
                                <flux:table.cell>
                                    <x-status-badge>{{ str($order->status->value)->replace('_', ' ')->title() }}</x-status-badge>
                                </flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$order->total_paisa" /></flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    </x-panel>
</div>
