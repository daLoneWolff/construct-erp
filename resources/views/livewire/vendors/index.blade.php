<div class="space-y-5">
    <x-page-header title="{{ __('Vendors') }}" description="{{ __('Supplier master with PAN and TDS settings.') }}">
        <x-slot:actions>
            @can('manage')
                <flux:button variant="primary" icon="plus" :href="route('vendors.create')" wire:navigate>
                    {{ __('New vendor') }}
                </flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    @if ($vendors->isEmpty())
        <x-empty-state
            title="{{ __('No vendors') }}"
            description="{{ __('Add a supplier to start raising purchase orders.') }}"
            :cta="auth()->user()->can('manage') ? __('New vendor') : null"
            :href="auth()->user()->can('manage') ? route('vendors.create') : null"
        />
    @else
        <x-panel flush>
            <x-filter-bar>
                <flux:input wire:model.live="search" :label="__('Search')" placeholder="Name or PAN" class="w-full sm:max-w-sm" icon="magnifying-glass" />
            </x-filter-bar>

            <div class="overflow-x-auto border-t border-border">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Vendor') }}</flux:table.column>
                        <flux:table.column>{{ __('PAN') }}</flux:table.column>
                        <flux:table.column>{{ __('TDS rate') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('POs') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($vendors as $vendor)
                            <flux:table.row :key="$vendor->id">
                                <flux:table.cell>
                                    <flux:link :href="route('vendors.show', $vendor)" wire:navigate class="font-medium">
                                        {{ $vendor->name }}
                                    </flux:link>
                                    <flux:text class="text-xs text-zinc-500">{{ $vendor->contact_person }}</flux:text>
                                </flux:table.cell>
                                <flux:table.cell class="font-mono">{{ $vendor->pan }}</flux:table.cell>
                                <flux:table.cell>{{ $vendor->tds_rate }}%</flux:table.cell>
                                <flux:table.cell>
                                    <x-status-badge :tone="$vendor->status === \App\Enums\VendorStatus::Active ? 'success' : 'neutral'">
                                        {{ str($vendor->status->value)->title() }}
                                    </x-status-badge>
                                </flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums">{{ $vendor->purchase_orders_count }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

        @if ($vendors->hasPages())
            <div class="border-t border-border px-5 py-3">
                {{ $vendors->links() }}
            </div>
        @endif
        </x-panel>
    @endif
</div>
