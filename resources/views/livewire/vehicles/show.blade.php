<div class="space-y-5">
    <x-page-header :title="$vehicle->make_model">
        <div class="flex flex-wrap items-center gap-2">
            <flux:link :href="route('vehicles.index')" wire:navigate class="text-sm">{{ __('Back to vehicles') }}</flux:link>
            <flux:badge color="zinc" size="sm" class="font-mono">{{ $vehicle->registration_no }}</flux:badge>
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
        </div>
        <div>{{ $vehicle->type }} · {{ $vehicle->currentProject?->name ?? __('Unassigned') }}</div>

        <x-slot:actions>
            @can('manage')
                @if (Route::has('vehicles.edit'))
                    <flux:button icon="pencil" :href="route('vehicles.edit', $vehicle)" wire:navigate>
                        {{ __('Edit') }}
                    </flux:button>
                @endif
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-panel title="{{ __('Document expiry') }}">
        <x-slot:actions>
            <flux:badge
                :color="match ($expiry['badge']) {
                    'red' => 'red',
                    'yellow' => 'amber',
                    'green' => 'lime',
                    default => 'zinc',
                }"
            >
                @if ($expiry['badge'] === 'red')
                    {{ __('Expired') }}
                @elseif ($expiry['badge'] === 'yellow')
                    {{ __('Expiring soon') }}
                @elseif ($expiry['badge'] === 'green')
                    {{ __('Valid') }}
                @else
                    {{ __('No documents') }}
                @endif
            </flux:badge>
        </x-slot:actions>

        <div class="overflow-x-auto -mx-5 -mb-5">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Document') }}</flux:table.column>
                    <flux:table.column>{{ __('Expiry date') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($documents as $key => $document)
                        <flux:table.row :key="$key">
                            <flux:table.cell class="font-medium">{{ $document['label'] }}</flux:table.cell>
                            <flux:table.cell>
                                <x-date
                                    :date="$vehicle->{($key === 'route_permit' ? 'route_permit_expiry' : $key.'_expiry')}"
                                    :with-badge="true"
                                />
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($document['days'] === null)
                                    <flux:badge size="sm" color="zinc">{{ __('Not set') }}</flux:badge>
                                @elseif ($document['days'] < 0)
                                    <flux:badge size="sm" color="red">{{ __(':days days overdue', ['days' => abs($document['days'])]) }}</flux:badge>
                                @else
                                    <flux:badge size="sm" :color="$document['days'] <= 30 ? 'amber' : 'lime'">
                                        {{ __(':days days left', ['days' => $document['days']]) }}
                                    </flux:badge>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </x-panel>

    <x-panel title="{{ __('Assign to project') }}">
        @can('manage')
            <form wire:submit="assignProject" class="flex flex-wrap items-end gap-3">
                <div class="min-w-64 flex-1">
                    <flux:select wire:model="assignProjectId" :label="__('Project')">
                        <flux:select.option value="">{{ __('Unassigned') }}</flux:select.option>
                        @foreach ($projects as $project)
                            <flux:select.option :value="$project->id">
                                {{ $project->code }} — {{ $project->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:button type="submit" variant="primary" icon="check">
                    {{ __('Update assignment') }}
                </flux:button>
            </form>
        @else
            <flux:text class="text-sm text-zinc-500">{{ __('Read-only access.') }}</flux:text>
        @endcan
    </x-panel>
</div>
