<div class="space-y-5">
    <x-page-header
        title="{{ __('Projects') }}"
        description="{{ __('Portfolio of active and completed contracts.') }}"
    >
        <x-slot:actions>
            @can('manage')
                <flux:button variant="primary" icon="plus" :href="route('projects.create')" wire:navigate>
                    {{ __('New project') }}
                </flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    @if ($projects->isEmpty())
        <x-empty-state
            title="{{ __('No projects yet') }}"
            description="{{ __('Create your first project to start tracking BOQ, procurement and billing.') }}"
            cta="{{ __('Add your first project') }}"
            :href="route('projects.create')"
        />
    @else
        <x-panel flush>
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Code') }}</flux:table.column>
                        <flux:table.column>{{ __('Project') }}</flux:table.column>
                        <flux:table.column>{{ __('Client') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('Contract value') }}</flux:table.column>
                        <flux:table.column>{{ __('Progress') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($projects as $project)
                            <flux:table.row :key="$project->id">
                                <flux:table.cell class="font-mono text-xs">
                                    <flux:link :href="route('projects.show', $project)" wire:navigate>
                                        {{ $project->code }}
                                    </flux:link>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:link :href="route('projects.show', $project)" wire:navigate class="font-medium">
                                        {{ $project->name }}
                                    </flux:link>
                                    <flux:text class="text-xs text-zinc-500">
                                        {{ $project->location_municipality }}, {{ $project->location_district }}
                                    </flux:text>
                                </flux:table.cell>

                                <flux:table.cell>{{ $project->client_name }}</flux:table.cell>

                                <flux:table.cell class="text-right tabular-nums">
                                    <x-money :paisa="$project->contract_value_paisa" />
                                </flux:table.cell>

                                <flux:table.cell class="min-w-40">
                                    <div class="flex items-center gap-2">
                                        <x-progress-bar :pct="$progress[$project->id]['pct']" class="flex-1" />
                                        <span class="w-12 text-right text-xs text-zinc-500">
                                            <x-number :value="$progress[$project->id]['pct']" :decimals="1" suffix="%" />
                                        </span>
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <x-status-badge
                                        :tone="match ($project->status) {
                                            \App\Enums\ProjectStatus::Active => 'success',
                                            \App\Enums\ProjectStatus::Completed => 'neutral',
                                            default => 'warning',
                                        }"
                                    >
                                        {{ str($project->status->value)->replace('_', ' ')->title() }}
                                    </x-status-badge>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

        @if ($projects->hasPages())
            <div class="border-t border-border px-5 py-3">
                {{ $projects->links() }}
            </div>
        @endif
        </x-panel>
    @endif
</div>
