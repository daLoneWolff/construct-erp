<div class="space-y-5">
    <x-page-header title="{{ __('Purchase orders') }}" description="{{ __('Supplier orders, approvals and net payable.') }}">
        <x-slot:actions>
            @can('manage')
                <flux:button variant="primary" icon="plus" :href="route('purchase-orders.create')" wire:navigate>
                    {{ __('New purchase order') }}
                </flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    @if ($orders->isEmpty() && $status === '')
        <x-empty-state
            title="{{ __('No purchase orders') }}"
            description="{{ __('Approved requisitions and direct orders will appear here.') }}"
        />
    @else
        <x-panel flush>
            <x-filter-bar>
                <flux:select wire:model.live="status" class="w-full sm:max-w-52" :label="__('Status')">
                    <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
                    @foreach ($statuses as $option)
                        <flux:select.option :value="$option->value">
                            {{ str($option->value)->replace('_', ' ')->title() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </x-filter-bar>

            <div class="overflow-x-auto border-t border-border">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Code') }}</flux:table.column>
                        <flux:table.column>{{ __('Project') }}</flux:table.column>
                        <flux:table.column>{{ __('Vendor') }}</flux:table.column>
                        <flux:table.column>{{ __('Order date') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('Net payable') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($orders as $order)
                            <flux:table.row :key="$order->id">
                                <flux:table.cell>
                                    <flux:link :href="route('purchase-orders.show', $order)" wire:navigate class="font-medium">
                                        {{ $order->code }}
                                    </flux:link>
                                </flux:table.cell>
                                <flux:table.cell>{{ $order->project->code }}</flux:table.cell>
                                <flux:table.cell>{{ $order->vendor->name }}</flux:table.cell>
                                <flux:table.cell><x-date :date="$order->order_date" /></flux:table.cell>
                                <flux:table.cell>
                                    <x-status-badge :tone="match ($order->status) {
                                        \App\Enums\PurchaseOrderStatus::Approved,
                                        \App\Enums\PurchaseOrderStatus::Sent,
                                        \App\Enums\PurchaseOrderStatus::PartiallyReceived => 'success',
                                        \App\Enums\PurchaseOrderStatus::Closed => 'neutral',
                                        default => 'warning',
                                    }">
                                        {{ str($order->status->value)->replace('_', ' ')->title() }}
                                    </x-status-badge>
                                </flux:table.cell>
                                <flux:table.cell class="text-right font-semibold tabular-nums"><x-money :paisa="$order->total_paisa" /></flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="6">
                                    <x-empty-state variant="table" title="{{ __('No purchase orders found') }}" description="{{ __('Try a different status filter.') }}" />
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            @if ($orders->hasPages())
                <div class="border-t border-border px-5 py-3">
                    {{ $orders->links() }}
                </div>
            @endif
        </x-panel>
    @endif
</div>
