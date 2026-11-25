<div class="space-y-5">
    <x-page-header title="{{ __('Issue material') }}">
        <flux:link :href="route('issues.index')" wire:navigate class="text-sm">{{ __('Back to issues') }}</flux:link>
    </x-page-header>

    <form wire:submit="save" class="space-y-5">
        <x-panel title="{{ __('Issue details') }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model.live="projectId" :label="__('Project')" required>
                    <flux:select.option value="">{{ __('Select project') }}</flux:select.option>
                    @foreach ($projects as $project)
                        <flux:select.option :value="$project->id">
                            {{ $project->code }} — {{ $project->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="warehouseId" :label="__('Warehouse')" required>
                    <flux:select.option value="">{{ __('Select warehouse') }}</flux:select.option>
                    @foreach ($warehouses as $warehouse)
                        <flux:select.option :value="$warehouse->id">
                            {{ $warehouse->name }} — {{ $warehouse->location }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="issuedToEmployeeId" :label="__('Issued to')" required>
                    <flux:select.option value="">{{ __('Select employee') }}</flux:select.option>
                    @foreach ($employees as $employee)
                        <flux:select.option :value="$employee->id">{{ $employee->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="issuedDate" type="date" :label="__('Issue date')" required />
            </div>

            <div class="mt-4">
                <flux:textarea wire:model="notes" :label="__('Notes')" rows="2" />
            </div>

            @if ($currentBalances->isNotEmpty())
                <div class="mt-4 rounded-xl bg-surface-subtle p-4 text-sm">
                    <flux:text class="font-medium text-zinc-700 dark:text-zinc-200">{{ __('Available stock') }}</flux:text>
                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-zinc-600 dark:text-zinc-400">
                        @foreach ($currentBalances as $row)
                            <span>{{ $row['item_description'] }}: {{ $row['balance_qty'] }} {{ $row['unit'] }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </x-panel>

        <x-panel title="{{ __('Issue lines') }}" flush>
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
                            <flux:table.column class="w-28">{{ __('Quantity') }}</flux:table.column>
                            <flux:table.column>{{ __('BOQ item') }}</flux:table.column>
                            <flux:table.column class="w-10"></flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($items as $i => $row)
                                <flux:table.row :key="'issue-item-'.$i">
                                    <flux:table.cell>
                                        <flux:input wire:model="items.{{ $i }}.description" size="sm" placeholder="Item description" />
                                        <x-field-error :message="$errors->first('items.'.$i.'.description')" class="mt-1" />
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <flux:input wire:model="items.{{ $i }}.unit" size="sm" />
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <flux:input
                                            wire:model="items.{{ $i }}.quantity"
                                            type="number"
                                            step="0.001"
                                            min="0"
                                            size="sm"
                                        />
                                        <x-field-error :message="$errors->first('items.'.$i.'.quantity')" class="mt-1" />
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

        <x-form-actions>
            <flux:button variant="ghost" :href="route('issues.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ __('Save issue') }}</flux:button>
        </x-form-actions>
    </form>
</div>
