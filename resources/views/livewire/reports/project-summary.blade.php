<div class="space-y-5">
    <x-page-header title="{{ __('Project summary') }}" description="{{ __('Contract value, execution, billing, and material health by project.') }}">
        <x-slot:actions>
            <flux:button icon="printer" :href="$printUrl" target="_blank">
                {{ __('Print') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <x-report-tabs active="project-summary" />

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

        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-stat-card label="Projects" :value="$totals['project_count']" />
            <x-stat-card label="Contract value" :paisa="$totals['contract_value_paisa']" />
            <x-stat-card label="Certified work" :paisa="$totals['certified_paisa']" />
        </div>
    </x-panel>

    <x-panel flush>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Project') }}</flux:table.column>
                <flux:table.column>{{ __('Client') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column align="end" class="text-right">{{ __('Contract value') }}</flux:table.column>
                <flux:table.column align="end" class="text-right">{{ __('BOQ value') }}</flux:table.column>
                <flux:table.column align="end" class="text-right">{{ __('Executed') }}</flux:table.column>
                <flux:table.column align="end" class="text-right">{{ __('Certified') }}</flux:table.column>
                <flux:table.column align="end" class="text-right">{{ __('Advance left') }}</flux:table.column>
                <flux:table.column>{{ __('Milestones') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($rows as $row)
                    <flux:table.row :key="$row['project_id']">
                        <flux:table.cell>
                            <div class="font-medium">{{ $row['code'] }}</div>
                            <flux:text class="text-xs text-zinc-500">{{ $row['name'] }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>{{ $row['client_name'] }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge
                                size="sm"
                                :color="match ($row['status']) {
                                    'active' => 'lime',
                                    'completed' => 'blue',
                                    default => 'amber',
                                }"
                            >
                                {{ str($row['status'])->replace('_', ' ')->title() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$row['contract_value_paisa']" /></flux:table.cell>
                        <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$row['boq_value_paisa']" /></flux:table.cell>
                        <flux:table.cell class="text-right tabular-nums"><x-number :value="$row['executed_pct']" :decimals="2" suffix="%" /></flux:table.cell>
                        <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$row['certified_paisa']" /></flux:table.cell>
                        <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$row['advance_remaining_paisa']" /></flux:table.cell>
                        <flux:table.cell>
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                <div class="h-full rounded-full bg-emerald-500" style="width: {{ $row['milestone_pct'] }}%"></div>
                            </div>
                            <flux:text class="text-xs text-zinc-500">
                                <x-number :value="$row['milestone_pct']" :decimals="2" suffix="%" /> · {{ $row['open_po_count'] }} {{ __('open POs') }}
                            </flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="9">
                            <x-empty-state
                                variant="table"
                                title="No projects found"
                                description="Create a project before opening this report."
                            />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </x-panel>
</div>
