<div class="space-y-5">
    <x-page-header
        title="{{ __('Vehicles') }}"
        description="{{ __('One expiry badge per vehicle shows its worst document.') }}"
    >
        <x-slot:actions>
            @can('manage')
                @if (Route::has('vehicles.create'))
                    <flux:button icon="plus" variant="primary" :href="route('vehicles.create')" wire:navigate>
                        {{ __('Add vehicle') }}
                    </flux:button>
                @endif
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Expired" :value="$counts['red']" tone="red" />
        <x-stat-card label="Expiring soon" :value="$counts['yellow']" tone="amber" />
        <x-stat-card label="Valid" :value="$counts['green']" tone="green" />
    </div>

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
                    <flux:table.column>{{ __('Registration') }}</flux:table.column>
                    <flux:table.column>{{ __('Type') }}</flux:table.column>
                    <flux:table.column>{{ __('Make / model') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Current project') }}</flux:table.column>
                    <flux:table.column>{{ __('Expiry') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($vehicles as $row)
                        @php $vehicle = $row['vehicle']; @endphp
                        <flux:table.row :key="$vehicle->id">
                            <flux:table.cell>
                                <flux:link :href="route('vehicles.show', $vehicle)" wire:navigate class="font-mono font-medium">
                                    {{ $vehicle->registration_no }}
                                </flux:link>
                            </flux:table.cell>
                            <flux:table.cell>{{ $vehicle->type }}</flux:table.cell>
                            <flux:table.cell>{{ $vehicle->make_model }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge
                                    size="sm"
                                    :color="match ($vehicle->status) {
                                        \App\Enums\AssetStatus::OnSite => 'blue',
                                        \App\Enums\AssetStatus::Maintenance => 'amber',
                                        default => 'lime',
                                    }"
                                >
                                    {{ str($vehicle->status->value)->replace('_', ' ')->title() }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $vehicle->currentProject?->code ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge
                                    size="sm"
                                    :color="match ($row['expiry']['badge']) {
                                        'red' => 'red',
                                        'yellow' => 'amber',
                                        'green' => 'lime',
                                        default => 'zinc',
                                    }"
                                >
                                    {{ $row['expiryLabel'] }}
                                </flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6">
                                <x-empty-state variant="table" title="{{ __('No vehicles found') }}" description="{{ __('Add a vehicle to track document expiry and project assignment.') }}" />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </x-panel>
</div>
