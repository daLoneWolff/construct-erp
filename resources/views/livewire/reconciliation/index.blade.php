<div class="space-y-5">
    <x-page-header title="{{ __('Material reconciliation') }}">
        <div class="flex flex-wrap items-center gap-2">
            <flux:link :href="route('projects.show', $project)" wire:navigate class="text-sm">{{ __('Back to project') }}</flux:link>
            <flux:badge color="zinc" size="sm">{{ $project->code }}</flux:badge>
        </div>
        <div>{{ $project->name }}</div>

        <x-slot:actions>
            <flux:badge color="red" size="sm">
                {{ __('Over consumed: :count', ['count' => $summary['over_consumed']]) }}
            </flux:badge>
            <flux:badge color="amber" size="sm">
                {{ __('Shortage: :count', ['count' => $summary['shortage']]) }}
            </flux:badge>
            <flux:badge color="lime" size="sm">
                {{ __('OK: :count', ['count' => $summary['ok']]) }}
            </flux:badge>
        </x-slot:actions>
    </x-page-header>

    <flux:callout variant="info" icon="information-circle">
        {{ __('Consumption is drawn from issued material; the two columns are never added together. Variance is BOQ quantity minus issued quantity.') }}
    </flux:callout>

    <x-panel flush>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('BOQ item') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('BOQ qty') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Requisitioned') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Received') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Issued') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Consumed') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Variance') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($rows as $row)
                        <flux:table.row :key="$row['boq_item_id']">
                            <flux:table.cell>
                                <div class="font-medium">{{ $row['description'] }}</div>
                                <div class="font-mono text-xs text-zinc-500">{{ $row['code'] }} · {{ $row['unit'] }}</div>
                            </flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ $row['boq_qty'] }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ $row['requisitioned_qty'] }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ $row['received_qty'] }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ $row['issued_qty'] }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ $row['consumed_qty'] }}</flux:table.cell>
                            <flux:table.cell class="text-right font-semibold tabular-nums">{{ $row['variance_qty'] }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge
                                    size="sm"
                                    :color="match ($row['status']) {
                                        'over_consumed' => 'red',
                                        'shortage' => 'amber',
                                        default => 'lime',
                                    }"
                                >
                                    {{ str($row['status'])->replace('_', ' ')->title() }}
                                </flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8">
                                <x-empty-state
                                    variant="table"
                                    title="{{ __('No reconciliation data yet') }}"
                                    description="{{ __('No material movement has been recorded for this project yet.') }}"
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </x-panel>
</div>
