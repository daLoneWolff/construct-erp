<div class="space-y-5">
    <x-page-header
        title="{{ __('Material requisitions') }}"
        description="{{ __('Site material requests and approvals.') }}{{ $pendingCount > 0 ? ' '.__(':count pending.', ['count' => $pendingCount]) : '' }}"
    >
        <x-slot:actions>
            @can('write')
                <flux:button variant="primary" icon="plus" :href="route('requisitions.create')" wire:navigate>
                    {{ __('New requisition') }}
                </flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    @if ($requisitions->isEmpty() && $status === '')
        <x-empty-state
            title="{{ __('No requisitions') }}"
            description="{{ __('Material requests raised by the site team will appear here.') }}"
        />
    @else
        <x-panel flush>
            <x-filter-bar>
                <flux:select wire:model.live="status" class="w-full sm:max-w-48" :label="__('Status')">
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
                        <flux:table.column>{{ __('Project') }}</flux:table.column>
                        <flux:table.column>{{ __('Raised by') }}</flux:table.column>
                        <flux:table.column>{{ __('Required by') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('Items') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($requisitions as $requisition)
                            <flux:table.row :key="$requisition->id">
                                <flux:table.cell>
                                    <flux:link :href="route('requisitions.show', $requisition)" wire:navigate class="font-medium">
                                        {{ $requisition->code }}
                                    </flux:link>
                                </flux:table.cell>
                                <flux:table.cell>{{ $requisition->project->code }}</flux:table.cell>
                                <flux:table.cell>{{ $requisition->raisedBy?->name ?? '—' }}</flux:table.cell>
                                <flux:table.cell><x-date :date="$requisition->required_by" /></flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums">{{ $requisition->items_count }}</flux:table.cell>
                                <flux:table.cell>
                                    <x-status-badge :tone="match ($requisition->status) {
                                        \App\Enums\RequisitionStatus::Approved => 'success',
                                        \App\Enums\RequisitionStatus::PoGenerated => 'info',
                                        \App\Enums\RequisitionStatus::Rejected => 'danger',
                                        default => 'warning',
                                    }">
                                        {{ str($requisition->status->value)->replace('_', ' ')->title() }}
                                    </x-status-badge>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="6">
                                    <x-empty-state variant="table" title="{{ __('No requisitions found') }}" description="{{ __('Try a different status filter.') }}" />
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            @if ($requisitions->hasPages())
                <div class="border-t border-border px-5 py-3">
                    {{ $requisitions->links() }}
                </div>
            @endif
        </x-panel>
    @endif
</div>
