<div class="space-y-5">
    <x-page-header title="{{ __('Tasks') }}">
        <div class="flex flex-wrap items-center gap-2">
            <flux:link :href="route('projects.show', $project)" wire:navigate class="text-sm">
                {{ __('Back to project') }}
            </flux:link>
            <flux:badge color="zinc" size="sm" class="font-mono">{{ $project->code }}</flux:badge>
        </div>
        <div>{{ $project->name }}</div>

        <x-slot:actions>
            <flux:button
                variant="primary"
                icon="plus"
                :href="route('projects.jobs.create', $project)"
                wire:navigate
            >
                {{ __('New task') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-stat-card label="Agreed task value" :paisa="$summary['agreed_paisa']" />
        <x-stat-card label="Paid" :paisa="$summary['paid_paisa']" tone="green" />
        <x-stat-card label="Outstanding" :paisa="$summary['balance_paisa']" :tone="$summary['balance_paisa'] > 0 ? 'amber' : 'green'" />
        <x-stat-card label="Contractors" :value="$summary['contractor_count']" />
        <x-stat-card label="Unassigned tasks" :value="$summary['unassigned_job_count']" />
    </div>

    <x-panel flush>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Task') }}</flux:table.column>
                    <flux:table.column>{{ __('Contractor') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Starts') }}</flux:table.column>
                    <flux:table.column>{{ __('Due') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Agreed') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Paid') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Balance') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($summary['jobs'] as $row)
                        <flux:table.row :key="$row['job_id']">
                            <flux:table.cell>
                                <flux:link :href="route('jobs.show', $row['job'])" wire:navigate class="font-medium">
                                    {{ $row['job']->title }}
                                </flux:link>
                                @if ($row['job']->description)
                                    <flux:text class="text-xs text-zinc-500">{{ $row['job']->description }}</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($row['contractor'])
                                    <flux:link :href="route('contractors.show', $row['contractor'])" wire:navigate>
                                        {{ $row['contractor']->name }}
                                    </flux:link>
                                @else
                                    <flux:badge size="sm" color="amber">{{ __('Unassigned') }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" color="zinc">
                                    {{ str($row['job']->status->value)->replace('_', ' ')->title() }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell><x-date :date="$row['job']->starts_on" /></flux:table.cell>
                            <flux:table.cell><x-date :date="$row['job']->due_on" /></flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$row['agreed_paisa']" /></flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$row['paid_paisa']" /></flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$row['balance_paisa']" /></flux:table.cell>
                            <flux:table.cell class="text-right">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="eye"
                                    :href="route('jobs.show', $row['job'])"
                                    wire:navigate
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="9">
                                <x-empty-state
                                    variant="table"
                                    title="No tasks yet"
                                    description="Create a task, assign a contractor, then record payments against it."
                                    cta="Create first task"
                                    :href="route('projects.jobs.create', $project)"
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </x-panel>

    @if (count($summary['contractors']) > 0)
        <x-panel title="{{ __('Roll-up by contractor') }}" flush>
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Contractor') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('Tasks') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('Agreed') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('Paid') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('Balance') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($summary['contractors'] as $row)
                            <flux:table.row :key="$row['contractor_id']">
                                <flux:table.cell>
                                    <flux:link :href="route('contractors.show', $row['contractor'])" wire:navigate>
                                        {{ $row['contractor']?->name }}
                                    </flux:link>
                                </flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums">{{ $row['job_count'] }}</flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$row['agreed_paisa']" /></flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$row['paid_paisa']" /></flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$row['balance_paisa']" /></flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        </x-panel>
    @endif
</div>
