<div class="space-y-5">
    <x-page-header title="{{ __('New requisition') }}">
        <flux:link :href="route('requisitions.index')" wire:navigate class="text-sm">{{ __('Back to requisitions') }}</flux:link>
    </x-page-header>

    <form wire:submit="save" class="space-y-5">
        <x-panel title="{{ __('Requisition details') }}">
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:select wire:model.live="projectId" :label="__('Project')" required>
                    <flux:select.option value="">{{ __('Select project') }}</flux:select.option>
                    @foreach ($projects as $project)
                        <flux:select.option :value="$project->id">
                            {{ $project->code }} — {{ $project->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="requiredBy" type="date" :label="__('Required by')" required />

                <div class="flex items-end">
                    <flux:text class="text-sm text-zinc-500">
                        {{ __('Submitted as pending for PM approval.') }}
                    </flux:text>
                </div>
            </div>

            <div class="mt-4">
                <flux:textarea wire:model="notes" :label="__('Notes')" rows="2" />
            </div>
        </x-panel>

        <x-panel title="{{ __('Line items') }}" flush>
            <x-slot:actions>
                <flux:button size="sm" icon="plus" wire:click="addItem">{{ __('Add item') }}</flux:button>
            </x-slot:actions>

            <div class="p-5">
                @error('items')
                    <x-field-error :message="$message" class="mb-3" />
                @enderror

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
                                <flux:table.row :key="'item-'.$i">
                                    <flux:table.cell>
                                        <flux:input wire:model="items.{{ $i }}.description" size="sm" placeholder="Item description" />
                                        <x-field-error :message="$errors->first('items.'.$i.'.description')" class="mt-1" />
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <flux:input wire:model="items.{{ $i }}.unit" size="sm" />
                                        <x-field-error :message="$errors->first('items.'.$i.'.unit')" class="mt-1" />
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <flux:input wire:model="items.{{ $i }}.quantity" type="number" step="0.001" min="0" size="sm" />
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
                                        <x-field-error :message="$errors->first('items.'.$i.'.boq_item_id')" class="mt-1" />
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
            <flux:button variant="ghost" :href="route('requisitions.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ __('Submit requisition') }}</flux:button>
        </x-form-actions>
    </form>
</div>
