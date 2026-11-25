<div class="space-y-5">
    <x-page-header title="{{ __('Receive material') }}">
        <div class="flex flex-wrap items-center gap-2">
            <flux:link :href="route('purchase-orders.show', $po)" wire:navigate class="text-sm">{{ __('Back to PO') }}</flux:link>
            <flux:badge color="zinc" size="sm" class="font-mono">{{ $po->code }}</flux:badge>
        </div>
    </x-page-header>

    @if (! $canReceive)
        <flux:callout variant="warning" icon="exclamation-triangle">
            {{ __('This purchase order must be approved or sent before material can be received.') }}
        </flux:callout>
    @endif

    <form wire:submit="save" class="space-y-5">
        <x-panel title="{{ __('Receipt details') }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model.live="warehouseId" :label="__('Warehouse')" required>
                    <flux:select.option value="">{{ __('Select warehouse') }}</flux:select.option>
                    @foreach ($warehouses as $warehouse)
                        <flux:select.option :value="$warehouse->id">
                            {{ $warehouse->name }} — {{ $warehouse->location }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="receivedDate" type="date" :label="__('Received date')" required />
                <flux:select wire:model="receivedByEmployeeId" :label="__('Received by')" required>
                    <flux:select.option value="">{{ __('Select employee') }}</flux:select.option>
                    @foreach ($employees as $employee)
                        <flux:select.option :value="$employee->id">{{ $employee->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="mt-4">
                <flux:textarea wire:model="notes" :label="__('Notes')" rows="2" />
            </div>

            @if ($currentBalances->isNotEmpty())
                <div class="mt-4 rounded-xl bg-surface-subtle p-4 text-sm">
                    <flux:text class="font-medium text-zinc-700 dark:text-zinc-200">{{ __('Current stock in this warehouse') }}</flux:text>
                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-zinc-600 dark:text-zinc-400">
                        @foreach ($currentBalances as $row)
                            <span>{{ $row['item_description'] }}: {{ $row['balance_qty'] }} {{ $row['unit'] }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </x-panel>

        <x-panel title="{{ __('PO lines') }}" flush>
            <div class="p-5">
                <flux:text class="mb-4 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $po->vendor?->name }} · {{ __('Ordered') }} <x-date :date="$po->order_date" />
                </flux:text>

                <x-field-error :message="$errors->first('po')" class="mb-3" />
                <x-field-error :message="$errors->first('items')" class="mb-3" />

                <div class="overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('Description') }}</flux:table.column>
                            <flux:table.column class="w-20">{{ __('Unit') }}</flux:table.column>
                            <flux:table.column align="end" class="w-28 text-right">{{ __('Outstanding') }}</flux:table.column>
                            <flux:table.column align="end" class="w-32 text-right">{{ __('Received') }}</flux:table.column>
                            <flux:table.column align="end" class="w-32 text-right">{{ __('Rejected') }}</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($items as $i => $row)
                                <flux:table.row :key="'receipt-item-'.$i">
                                    <flux:table.cell class="font-medium">{{ $row['description'] }}</flux:table.cell>
                                    <flux:table.cell>{{ $row['unit'] }}</flux:table.cell>
                                    <flux:table.cell class="text-right tabular-nums">{{ $row['ordered_qty'] }}</flux:table.cell>
                                    <flux:table.cell class="text-right">
                                        <flux:input
                                            wire:model="items.{{ $i }}.received_qty"
                                            type="number"
                                            step="0.001"
                                            min="0"
                                            size="sm"
                                            class="text-right"
                                        />
                                        <x-field-error :message="$errors->first('items.'.$i.'.received_qty')" class="mt-1" />
                                    </flux:table.cell>
                                    <flux:table.cell class="text-right">
                                        <flux:input
                                            wire:model="items.{{ $i }}.rejected_qty"
                                            type="number"
                                            step="0.001"
                                            min="0"
                                            size="sm"
                                            class="text-right"
                                        />
                                        <x-field-error :message="$errors->first('items.'.$i.'.rejected_qty')" class="mt-1" />
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            </div>
        </x-panel>

        <x-form-actions>
            <flux:button variant="ghost" :href="route('purchase-orders.show', $po)" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check" :disabled="! $canReceive">
                {{ __('Confirm receipt') }}
            </flux:button>
        </x-form-actions>
    </form>
</div>
