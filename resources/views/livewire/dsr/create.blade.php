<div class="space-y-5">
    <x-page-header title="{{ __('New daily site report') }}">
        <div class="flex flex-wrap items-center gap-2">
            <flux:badge color="zinc" size="sm" class="font-mono">{{ $project->code }}</flux:badge>
            <flux:link :href="route('projects.dsr', $project)" wire:navigate class="text-sm">
                {{ __('Back to site reports') }}
            </flux:link>
        </div>
        <div>{{ $project->name }}</div>

        <x-slot:actions>
            <flux:button variant="primary" icon="check" wire:click="save">
                {{ __('Submit report') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <form wire:submit="save" class="space-y-5">
        <x-panel title="{{ __('Report details') }}">
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="reportDate" type="date" :label="__('Report date')" max="{{ today()->toDateString() }}" />
                <flux:select wire:model="weather" :label="__('Weather')">
                    @foreach ($weatherOptions as $option)
                        <flux:select.option :value="$option">{{ $option }}</flux:select.option>
                    @endforeach
                </flux:select>
                <div class="flex items-end">
                    <flux:text class="text-sm text-zinc-500">{{ __('One report per project per day.') }}</flux:text>
                </div>
            </div>

            <div class="mt-4">
                <flux:textarea wire:model="remarks" :label="__('Remarks')" rows="2" />
            </div>
        </x-panel>

        <x-panel title="{{ __('Manpower') }}" flush>
            <x-slot:actions>
                <flux:button size="sm" icon="plus" wire:click="addManpower">{{ __('Add trade') }}</flux:button>
            </x-slot:actions>

            <div class="p-5">
                <x-field-error :message="$errors->first('manpower')" class="mb-3" />

                <div class="overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('Trade') }}</flux:table.column>
                            <flux:table.column class="w-28">{{ __('Count') }}</flux:table.column>
                            <flux:table.column class="w-28">{{ __('Hours') }}</flux:table.column>
                            <flux:table.column class="w-10"></flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($manpower as $i => $row)
                                <flux:table.row :key="'mp-'.$i">
                                    <flux:table.cell>
                                        <flux:select wire:model="manpower.{{ $i }}.trade" size="sm">
                                            @foreach ($trades as $trade)
                                                <flux:select.option :value="$trade">{{ $trade }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <x-field-error :message="$errors->first('manpower.'.$i.'.trade')" class="mt-1" />
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:input wire:model="manpower.{{ $i }}.count" type="number" min="0" size="sm" />
                                        <x-field-error :message="$errors->first('manpower.'.$i.'.count')" class="mt-1" />
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:input wire:model="manpower.{{ $i }}.hours" type="number" step="0.5" min="0" max="24" size="sm" />
                                        <x-field-error :message="$errors->first('manpower.'.$i.'.hours')" class="mt-1" />
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:button size="xs" variant="ghost" icon="trash" aria-label="{{ __('Remove trade') }}" wire:click="removeManpower({{ $i }})" />
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                <flux:text class="mt-3 text-xs text-zinc-500">
                    {{ __('Manpower total: :total', ['total' => collect($manpower)->sum(fn ($row) => (int) ($row['count'] ?? 0))]) }}
                </flux:text>
            </div>
        </x-panel>

        <x-panel title="{{ __('Equipment used') }}" flush>
            <x-slot:actions>
                <flux:button size="sm" icon="plus" wire:click="addEquipment">{{ __('Add equipment') }}</flux:button>
            </x-slot:actions>

            <div class="p-5">
                @if (empty($equipment))
                    <flux:text class="text-sm text-zinc-500">{{ __('No equipment recorded today.') }}</flux:text>
                @else
                    <div class="overflow-x-auto">
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>{{ __('Equipment') }}</flux:table.column>
                                <flux:table.column class="w-32">{{ __('Hours') }}</flux:table.column>
                                <flux:table.column class="w-10"></flux:table.column>
                            </flux:table.columns>

                            <flux:table.rows>
                                @foreach ($equipment as $i => $row)
                                    <flux:table.row :key="'eq-'.$i">
                                        <flux:table.cell>
                                            <flux:select wire:model="equipment.{{ $i }}.equipment_id" size="sm">
                                                <flux:select.option value="">{{ __('Select equipment') }}</flux:select.option>
                                                @foreach ($equipmentOptions as $option)
                                                    <flux:select.option :value="$option->id">
                                                        {{ $option->code }} · {{ $option->name }}
                                                    </flux:select.option>
                                                @endforeach
                                            </flux:select>
                                            <x-field-error :message="$errors->first('equipment.'.$i.'.equipment_id')" class="mt-1" />
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:input wire:model="equipment.{{ $i }}.hours" type="number" step="0.5" min="0" max="24" size="sm" />
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:button size="xs" variant="ghost" icon="trash" aria-label="{{ __('Remove equipment') }}" wire:click="removeEquipment({{ $i }})" />
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </div>
                @endif
            </div>
        </x-panel>

        <x-panel title="{{ __('Materials consumed') }}" description="{{ __('Each line links to a BOQ item so reconciliation can compare issued vs consumed.') }}" flush>
            <x-slot:actions>
                <flux:button size="sm" icon="plus" wire:click="addMaterial">{{ __('Add material') }}</flux:button>
            </x-slot:actions>

            <div class="p-5">
                <div class="overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('BOQ item') }}</flux:table.column>
                            <flux:table.column class="w-32">{{ __('Quantity') }}</flux:table.column>
                            <flux:table.column class="w-24">{{ __('Unit') }}</flux:table.column>
                            <flux:table.column class="w-10"></flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($materials as $i => $row)
                                <flux:table.row :key="'mat-'.$i">
                                    <flux:table.cell>
                                        <flux:select wire:model.live="materials.{{ $i }}.boq_item_id" size="sm">
                                            <flux:select.option value="">{{ __('Select BOQ item') }}</flux:select.option>
                                            @foreach ($boqItems as $item)
                                                <flux:select.option :value="$item->id">
                                                    {{ $item->code }} · {{ $item->description }}
                                                </flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <x-field-error :message="$errors->first('materials.'.$i.'.boq_item_id')" class="mt-1" />
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:input wire:model="materials.{{ $i }}.quantity" type="number" step="0.001" min="0" size="sm" />
                                        <x-field-error :message="$errors->first('materials.'.$i.'.quantity')" class="mt-1" />
                                    </flux:table.cell>
                                    <flux:table.cell class="text-xs text-zinc-500">
                                        {{ $boqItems->firstWhere('id', (int) ($row['boq_item_id'] ?? 0))?->unit }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:button size="xs" variant="ghost" icon="trash" aria-label="{{ __('Remove material') }}" wire:click="removeMaterial({{ $i }})" />
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            </div>
        </x-panel>

        <x-panel title="{{ __('Site photos') }}" description="{{ __('Demo mode stores file metadata only — no file leaves your browser.') }}">
            <input
                type="file"
                multiple
                accept="image/*"
                x-on:change="
                    $wire.set('photos', Array.from($event.target.files).map(f => ({
                        name: f.name, size: f.size, mime: f.type
                    })));
                "
                class="block w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm file:me-3 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-1 file:text-sm dark:file:bg-zinc-700"
            >

            @if (count($photos) > 0)
                <div class="mt-3 space-y-2">
                    @foreach ($displayPhotos as $i => $photo)
                        <div class="flex items-center justify-between gap-3 rounded-lg bg-surface-subtle px-3 py-2 text-sm">
                            <div class="flex items-center gap-2">
                                <flux:icon.photo class="size-4 text-zinc-400" />
                                <span>{{ $photo['name'] }}</span>
                                <span class="text-zinc-500">{{ $photo['size_label'] }}</span>
                            </div>
                            <flux:button size="xs" variant="ghost" wire:click="removePhoto({{ $i }})">
                                {{ __('Remove') }}
                            </flux:button>
                        </div>
                    @endforeach
                </div>
            @endif

            <x-field-error :message="$errors->first('photos.*.name')" class="mt-2" />
        </x-panel>

        <x-form-actions>
            <flux:button variant="ghost" :href="route('projects.dsr', $project)" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check">
                {{ __('Submit report') }}
            </flux:button>
        </x-form-actions>
    </form>
</div>
