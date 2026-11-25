<div class="space-y-5">
    <x-page-header :title="__('Requisition :code', ['code' => $requisition->code])">
        <div class="flex flex-wrap items-center gap-2">
            <flux:link :href="route('requisitions.index')" wire:navigate class="text-sm">{{ __('Back to requisitions') }}</flux:link>
            <flux:badge color="zinc" size="sm" class="font-mono">{{ $requisition->code }}</flux:badge>
            <x-status-badge
                :tone="match ($requisition->status) {
                    \App\Enums\RequisitionStatus::Approved => 'success',
                    \App\Enums\RequisitionStatus::PoGenerated => 'info',
                    \App\Enums\RequisitionStatus::Rejected => 'danger',
                    default => 'warning',
                }"
            >
                {{ str($requisition->status->value)->replace('_', ' ')->title() }}
            </x-status-badge>
        </div>

        <div>
            {{ $requisition->project->code }} — {{ $requisition->project->name }} ·
            {{ __('Required by') }} <x-date :date="$requisition->required_by" />
        </div>

        <x-slot:actions>
            @can('approve')
                @if ($requisition->status === \App\Enums\RequisitionStatus::Pending)
                    <flux:button variant="primary" icon="check" wire:click="approve">{{ __('Approve') }}</flux:button>
                    <flux:button variant="danger" icon="x-mark" wire:click="reject">{{ __('Reject') }}</flux:button>
                @elseif ($requisition->status === \App\Enums\RequisitionStatus::Approved)
                    <flux:button variant="primary" icon="shopping-cart" wire:click="generatePo">
                        {{ __('Generate PO') }}
                    </flux:button>
                @endif
            @endcan

            @if ($requisition->purchaseOrder)
                <flux:button
                    icon="document-text"
                    :href="route('purchase-orders.show', $requisition->purchaseOrder)"
                    wire:navigate
                >
                    {{ __('View :code', ['code' => $requisition->purchaseOrder->code]) }}
                </flux:button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-panel title="{{ __('Details') }}">
        <div class="grid gap-3 sm:grid-cols-3">
            <div>
                <flux:text class="text-xs text-zinc-500">{{ __('Raised by') }}</flux:text>
                <div>{{ $requisition->raisedBy?->name ?? '—' }}</div>
            </div>
            <div>
                <flux:text class="text-xs text-zinc-500">{{ __('Approved by') }}</flux:text>
                <div>{{ $requisition->approvedBy?->name ?? '—' }}</div>
            </div>
            <div>
                <flux:text class="text-xs text-zinc-500">{{ __('Approved at') }}</flux:text>
                <div><x-date :date="$requisition->approved_at" :time="true" /></div>
            </div>
        </div>

        @if ($requisition->notes)
            <div class="mt-4 border-t border-border pt-4">
                <flux:text class="text-sm">{{ $requisition->notes }}</flux:text>
            </div>
        @endif
    </x-panel>

    <x-panel title="{{ __('Requested items') }}" flush>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Description') }}</flux:table.column>
                    <flux:table.column>{{ __('BOQ item') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Quantity') }}</flux:table.column>
                    <flux:table.column>{{ __('Notes') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($items as $item)
                        <flux:table.row :key="$item->id">
                            <flux:table.cell class="font-medium">{{ $item->description }}</flux:table.cell>
                            <flux:table.cell>{{ $item->boqItem?->code ?? '—' }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ $item->quantity }} {{ $item->unit }}</flux:table.cell>
                            <flux:table.cell>{{ $item->notes ?: '—' }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </x-panel>
</div>
