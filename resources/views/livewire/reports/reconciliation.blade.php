<div class="space-y-5">
    <x-page-header title="{{ __('Reconciliation report') }}" description="{{ __('The same material variance view as the project reconciliation screen.') }}">
        <x-slot:actions>
            <flux:button icon="printer" :href="$printUrl" target="_blank">
                {{ __('Print') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <x-report-tabs active="reconciliation" />

    <x-panel>
        <x-filter-bar>
            <flux:select wire:model.live="projectId" :label="__('Project')" class="w-full sm:max-w-sm">
                <flux:select.option value="">{{ __('Select a project') }}</flux:select.option>
                @foreach ($projects as $option)
                    <flux:select.option :value="$option->id">
                        {{ $option->code }} — {{ $option->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </x-filter-bar>

        <div class="mt-4 flex flex-wrap gap-2">
            <flux:badge color="red" size="sm">
                {{ __('Over consumed: :count', ['count' => $summary['over_consumed']]) }}
            </flux:badge>
            <flux:badge color="amber" size="sm">
                {{ __('Shortage: :count', ['count' => $summary['shortage']]) }}
            </flux:badge>
            <flux:badge color="lime" size="sm">
                {{ __('OK: :count', ['count' => $summary['ok']]) }}
            </flux:badge>
            <flux:badge color="zinc" size="sm">
                {{ __('Net variance:') }} <x-number :value="$summary['total_variance_qty']" :decimals="2" />
            </flux:badge>
        </div>
    </x-panel>

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
                            <flux:table.cell class="text-right tabular-nums"><x-number :value="$row['boq_qty']" :decimals="2" /></flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-number :value="$row['requisitioned_qty']" :decimals="2" /></flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-number :value="$row['received_qty']" :decimals="2" /></flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-number :value="$row['issued_qty']" :decimals="2" /></flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-number :value="$row['consumed_qty']" :decimals="2" /></flux:table.cell>
                            <flux:table.cell class="text-right font-semibold tabular-nums"><x-number :value="$row['variance_qty']" :decimals="2" /></flux:table.cell>
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
                                    title="No material movement"
                                    description="This project has no requisitions, receipts, or issues to reconcile yet."
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </x-panel>
</div>
