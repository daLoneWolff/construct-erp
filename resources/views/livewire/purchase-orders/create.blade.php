<div class="space-y-5">
    <x-page-header title="{{ __('New purchase order') }}">
        <div class="flex flex-wrap items-center gap-2">
            <flux:link :href="route('purchase-orders.index')" wire:navigate class="text-sm">{{ __('Back to purchase orders') }}</flux:link>
            @if ($requisitionId)
                <x-status-badge tone="info">{{ __('From requisition') }}</x-status-badge>
            @endif
        </div>
    </x-page-header>

    <form wire:submit="save" class="space-y-5">
        <x-panel title="{{ __('Order details') }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model.live="projectId" :label="__('Project')" required>
                    <flux:select.option value="">{{ __('Select project') }}</flux:select.option>
                    @foreach ($projects as $project)
                        <flux:select.option :value="$project->id">
                            {{ $project->code }} — {{ $project->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="vendorId" :label="__('Vendor')" required>
                    <flux:select.option value="">{{ __('Select vendor') }}</flux:select.option>
                    @foreach ($vendors as $vendor)
                        <flux:select.option :value="$vendor->id">{{ $vendor->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="orderDate" type="date" :label="__('Order date')" required />
                <flux:input wire:model="expectedDelivery" type="date" :label="__('Expected delivery')" />
            </div>

            @if ($selectedVendor)
                <div class="mt-4">
                    <flux:callout variant="info" icon="information-circle">
                        {{ $selectedVendor->name }} · PAN {{ $selectedVendor->pan }} ·
                        {{ __('TDS rate auto-filled at :rate%.', ['rate' => $selectedVendor->tds_rate]) }}
                    </flux:callout>
                </div>
            @endif

            <div class="mt-4">
                <flux:textarea wire:model="notes" :label="__('Notes')" rows="2" />
            </div>
        </x-panel>

        <x-panel title="{{ __('Order items') }}" flush>
            <x-slot:actions>
                <flux:button size="sm" icon="plus" wire:click="addItem">{{ __('Add item') }}</flux:button>
            </x-slot:actions>

            <div class="p-5">
                <x-field-error :message="$errors->first('items')" class="mb-3" />

                <div class="overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('Description') }}</flux:table.column>
                            <flux:table.column class="w-20">{{ __('Unit') }}</flux:table.column>
                            <flux:table.column class="w-24">{{ __('Quantity') }}</flux:table.column>
                            <flux:table.column class="w-32">{{ __('Rate (NPR)') }}</flux:table.column>
                            <flux:table.column>{{ __('BOQ item') }}</flux:table.column>
                            <flux:table.column class="w-10"></flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($items as $i => $row)
                                <flux:table.row :key="'item-'.$i">
                                    <flux:table.cell>
                                        <flux:input wire:model="items.{{ $i }}.description" size="sm" placeholder="Item description" />
                                        <x-field-error :message="$errors->first('items.'.$i.'.description')" class="mt-1" />
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <flux:input wire:model="items.{{ $i }}.unit" size="sm" />
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <flux:input wire:model.live="items.{{ $i }}.quantity" type="number" step="0.001" min="0" size="sm" />
                                        <x-field-error :message="$errors->first('items.'.$i.'.quantity')" class="mt-1" />
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <flux:input wire:model.live="items.{{ $i }}.rate_npr" type="number" step="0.01" min="0" size="sm" />
                                        <x-field-error :message="$errors->first('items.'.$i.'.rate_npr')" class="mt-1" />
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <div class="flex items-center gap-1">
                                            <flux:select wire:model="items.{{ $i }}.boq_item_id" size="sm">
                                                <flux:select.option value="">{{ __('Not linked') }}</flux:select.option>
                                                @foreach ($boqItems as $boqItem)
                                                    <flux:select.option :value="$boqItem->id">
                                                        {{ $boqItem->code }} · {{ $boqItem->description }}
                                                    </flux:select.option>
                                                @endforeach
                                            </flux:select>

                                            <flux:button
                                                size="xs"
                                                variant="ghost"
                                                icon="arrow-down-on-square"
                                                wire:click="fillFromBoq({{ $i }})"
                                                :disabled="empty($row['boq_item_id'])"
                                            >
                                                {{ __('Use BOQ') }}
                                            </flux:button>
                                        </div>
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <flux:button size="xs" variant="ghost" icon="trash" aria-label="{{ __('Remove item') }}" wire:click="removeItem({{ $i }})" />
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            </div>
        </x-panel>

        <x-panel title="{{ __('Calculated totals') }}">
            <div class="ml-auto max-w-sm space-y-2">
                <div class="flex justify-between gap-4 text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('Subtotal') }}</span>
                    <span class="tabular-nums"><x-money :paisa="$totals['subtotal_paisa']" /></span>
                </div>
                <div class="flex justify-between gap-4 text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('VAT @ :rate%', ['rate' => $vatRate]) }}</span>
                    <span class="tabular-nums"><x-money :paisa="$totals['vat_paisa']" /></span>
                </div>
                <div class="border-t border-border pt-2 flex justify-between gap-4 font-medium">
                    <span>{{ __('Invoice total') }}</span>
                    <span class="tabular-nums"><x-money :paisa="$totals['invoice_total_paisa']" /></span>
                </div>
                <div class="flex justify-between gap-4 text-sm text-zinc-500 dark:text-zinc-400">
                    <span>{{ __('TDS withheld @ :rate%', ['rate' => $tdsRate]) }}</span>
                    <span class="tabular-nums"><x-money :paisa="$totals['tds_paisa']" /></span>
                </div>
                <div class="border-t border-border pt-2 flex justify-between gap-4 text-lg font-semibold">
                    <span>{{ __('Net payable') }}</span>
                    <span class="tabular-nums"><x-money :paisa="$totals['net_payable_paisa']" /></span>
                </div>
            </div>
        </x-panel>

        <x-form-actions>
            <flux:button variant="ghost" :href="route('purchase-orders.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ __('Save purchase order') }}</flux:button>
        </x-form-actions>
    </form>
</div>
