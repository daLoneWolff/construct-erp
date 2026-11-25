<div class="space-y-5">
    <x-page-header
        title="{{ __('Equipment') }}"
        description="{{ __('Owned and hired plant tracked across projects.') }}"
    >
        <x-slot:actions>
            @can('manage')
                @if (Route::has('equipment.create'))
                    <flux:button icon="plus" variant="primary" :href="route('equipment.create')" wire:navigate>
                        {{ __('Add equipment') }}
                    </flux:button>
                @endif
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-panel flush>
        <x-filter-bar>
            <flux:select wire:model.live="status" :label="__('Status')" class="w-full sm:max-w-xs">
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
                    <flux:table.column>{{ __('Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Type') }}</flux:table.column>
                    <flux:table.column>{{ __('Ownership') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Daily rate') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Current project') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Usages') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($equipment as $item)
                        <flux:table.row :key="$item->id">
                            <flux:table.cell>
                                <flux:link :href="route('equipment.show', $item)" wire:navigate class="font-mono font-medium">
                                    {{ $item->code }}
                                </flux:link>
                            </flux:table.cell>
                            <flux:table.cell class="font-medium">{{ $item->name }}</flux:table.cell>
                            <flux:table.cell>{{ $item->type }}</flux:table.cell>
                            <flux:table.cell>{{ str($item->ownership->value)->title() }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$item->daily_rate_paisa" /></flux:table.cell>
                            <flux:table.cell>
                                <flux:badge
                                    size="sm"
                                    :color="match ($item->status) {
                                        \App\Enums\AssetStatus::OnSite => 'blue',
                                        \App\Enums\AssetStatus::Maintenance => 'amber',
                                        default => 'lime',
                                    }"
                                >
                                    {{ str($item->status->value)->replace('_', ' ')->title() }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $item->currentProject?->code ?? '—' }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ $item->usages_count }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8">
                                <x-empty-state variant="table" title="{{ __('No equipment found') }}" description="{{ __('Add equipment to track plant across projects.') }}" />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        @if ($equipment->hasPages())
            <div class="border-t border-border px-5 py-3">
                {{ $equipment->links() }}
            </div>
        @endif
    </x-panel>
</div>
