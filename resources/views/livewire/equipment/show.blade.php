<div class="space-y-5">
    <x-page-header :title="$equipment->name">
        <div class="flex flex-wrap items-center gap-2">
            <flux:link :href="route('equipment.index')" wire:navigate class="text-sm">{{ __('Back to equipment') }}</flux:link>
            <flux:badge color="zinc" size="sm" class="font-mono">{{ $equipment->code }}</flux:badge>
            <flux:badge
                size="sm"
                :color="match ($equipment->status) {
                    \App\Enums\AssetStatus::OnSite => 'blue',
                    \App\Enums\AssetStatus::Maintenance => 'amber',
                    default => 'lime',
                }"
            >
                {{ str($equipment->status->value)->replace('_', ' ')->title() }}
            </flux:badge>
        </div>
        <div>{{ $equipment->type }} · {{ str($equipment->ownership->value)->title() }}</div>

        <x-slot:actions>
            @can('manage')
                @if (Route::has('equipment.edit'))
                    <flux:button icon="pencil" :href="route('equipment.edit', $equipment)" wire:navigate>
                        {{ __('Edit') }}
                    </flux:button>
                @endif
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-5 lg:grid-cols-3">
        <x-panel title="{{ __('Profile') }}">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Hourly rate') }}</dt>
                    <dd class="font-medium"><x-money :paisa="$equipment->hourly_rate_paisa" /></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Daily rate') }}</dt>
                    <dd class="font-medium"><x-money :paisa="$equipment->daily_rate_paisa" /></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Current project') }}</dt>
                    <dd class="font-medium">{{ $equipment->currentProject?->name ?? __('Unassigned') }}</dd>
                </div>
            </dl>
        </x-panel>

        <x-panel title="{{ __('Assign to project') }}" class="lg:col-span-2">
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

    <x-panel title="{{ __('Usage log') }}" flush>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                    <flux:table.column>{{ __('Project') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Hours') }}</flux:table.column>
                    <flux:table.column>{{ __('Operator') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($usages as $usage)
                        <flux:table.row :key="$usage->id">
                            <flux:table.cell><x-date :date="$usage->usage_date" /></flux:table.cell>
                            <flux:table.cell>{{ $usage->project?->code }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ $usage->hours }}</flux:table.cell>
                            <flux:table.cell>{{ $usage->operator_name ?? '—' }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4">
                                <x-empty-state variant="table" title="{{ __('No usage recorded yet') }}" description="{{ __('Equipment usage entries will appear here.') }}" />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </x-panel>
</div>
