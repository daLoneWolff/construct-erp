<div class="space-y-5">
    <x-page-header title="{{ __('PO status') }}" description="{{ __('Supplier orders with approval state and recalculated net payable.') }}">
        <x-slot:actions>
            <flux:button icon="printer" :href="$printUrl" target="_blank">
                {{ __('Print') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <x-report-tabs active="po-status" />

    <x-panel>
        <x-filter-bar>
            <flux:select wire:model.live="projectId" :label="__('Project')" class="w-full sm:max-w-xs">
                <flux:select.option value="">{{ __('All projects') }}</flux:select.option>
                @foreach ($projects as $option)
                    <flux:select.option :value="$option->id">
                        {{ $option->code }} — {{ $option->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model.live="from" type="date" :label="__('Order date from')" class="w-full sm:max-w-xs" />
            <flux:input wire:model.live="to" type="date" :label="__('Order date to')" class="w-full sm:max-w-xs" />
        </x-filter-bar>

        <div class="mt-4 flex flex-wrap gap-2">
            @foreach ($statusOptions as $option)
                <flux:badge color="zinc" size="sm">
                    {{ str($option->value)->replace('_', ' ')->title() }}:
                    {{ $statusCounts[$option->value] }}
                </flux:badge>
            @endforeach
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <x-stat-card label="Purchase orders" :value="$totals['po_count']" />
            <x-stat-card label="Net payable" :paisa="$totals['net_payable_paisa']" />
        </div>
    </x-panel>

    <x-panel flush>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('PO') }}</flux:table.column>
                    <flux:table.column>{{ __('Project') }}</flux:table.column>
                    <flux:table.column>{{ __('Vendor') }}</flux:table.column>
                    <flux:table.column>{{ __('Order date') }}</flux:table.column>
                    <flux:table.column>{{ __('Expected delivery') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Net payable') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($rows as $row)
                        <flux:table.row :key="$row['id']">
                            <flux:table.cell class="font-mono">{{ $row['code'] }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="font-medium">{{ $row['project_code'] }}</div>
                                <flux:text class="text-xs text-zinc-500">{{ $row['project_name'] }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>{{ $row['vendor_name'] }}</flux:table.cell>
                            <flux:table.cell><x-date :date="$row['order_date']" /></flux:table.cell>
                            <flux:table.cell><x-date :date="$row['expected_delivery']" /></flux:table.cell>
                            <flux:table.cell>
                                <flux:badge
                                    size="sm"
                                    :color="match ($row['status']) {
                                        'approved', 'sent', 'partially_received' => 'lime',
                                        'closed' => 'zinc',
                                        default => 'amber',
                                    }"
                                >
                                    {{ str($row['status'])->replace('_', ' ')->title() }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$row['total_paisa']" /></flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7">
                                <x-empty-state
                                    variant="table"
                                    title="No purchase orders in this range"
                                    description="Widen the order date range or choose another project."
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </x-panel>
</div>
