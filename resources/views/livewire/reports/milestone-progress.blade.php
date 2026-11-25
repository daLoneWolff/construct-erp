<div class="space-y-5">
    <x-page-header title="{{ __('Milestone progress') }}" description="{{ __('Task completion across project milestones.') }}">
        <x-slot:actions>
            <flux:button icon="printer" :href="$printUrl" target="_blank">
                {{ __('Print') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <x-report-tabs active="milestone-progress" />

    <x-panel>
        <x-filter-bar>
            <flux:select wire:model.live="projectId" :label="__('Project')" class="w-full sm:max-w-sm">
                <flux:select.option value="">{{ __('All projects') }}</flux:select.option>
                @foreach ($projects as $option)
                    <flux:select.option :value="$option->id">
                        {{ $option->code }} — {{ $option->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </x-filter-bar>

        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat-card label="Projects" :value="$totals['project_count']" />
            <x-stat-card label="Milestones" :value="$totals['milestone_count']" />
            <x-stat-card label="Completed" :value="$totals['completed_count']" tone="green" />
            <x-stat-card label="Tasks done" :value="$totals['tasks_done'].' / '.$totals['tasks_total']" />
        </div>
    </x-panel>

    <x-panel flush>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Project') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Milestones') }}</flux:table.column>
                    <flux:table.column>{{ __('Status mix') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Tasks done') }}</flux:table.column>
                    <flux:table.column>{{ __('Progress') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($rows as $row)
                        <flux:table.row :key="$row['project_id']">
                            <flux:table.cell>
                                <div class="font-medium">{{ $row['code'] }}</div>
                                <flux:text class="text-xs text-zinc-500">{{ $row['name'] }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ $row['milestone_count'] }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($statusOptions as $option)
                                        <flux:badge size="sm" color="zinc">
                                            {{ str($option->value)->replace('_', ' ')->title() }}:
                                            {{ $row['status_counts'][$option->value] }}
                                        </flux:badge>
                                    @endforeach
                                </div>
                            </flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ $row['tasks_done'] }}/{{ $row['tasks_total'] }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                    <div class="h-full rounded-full bg-emerald-500" style="width: {{ $row['progress_pct'] }}%"></div>
                                </div>
                                <flux:text class="text-xs text-zinc-500">
                                    <x-number :value="$row['progress_pct']" :decimals="2" suffix="%" />
                                </flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5">
                                <x-empty-state
                                    variant="table"
                                    title="No milestones found"
                                    description="Add milestones and tasks to a project to see progress here."
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </x-panel>
</div>
