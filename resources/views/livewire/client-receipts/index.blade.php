<div class="space-y-5">
    <x-page-header
        title="{{ __('Client receipts') }}"
        description="{{ __('Money received from clients, including advances and running bill payments.') }}"
    >
        <x-slot:actions>
            <flux:button variant="primary" icon="banknotes" :href="route('client-receipts.create')" wire:navigate>
                {{ __('Record receipt') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <x-panel flush>
        <x-filter-bar>
            <flux:select wire:model.live="projectId" :label="__('Project')" class="w-full sm:min-w-56">
                <flux:select.option value="">{{ __('All projects') }}</flux:select.option>
                @foreach ($projects as $option)
                    <flux:select.option :value="$option->id">{{ $option->code }} — {{ $option->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model.live="from" type="date" :label="__('Received from')" />
            <flux:input wire:model.live="to" type="date" :label="__('Received to')" />
        </x-filter-bar>

        <div class="grid gap-3 px-5 pb-5 sm:grid-cols-2">
            <x-stat-card label="Receipts" :value="$summary['receipt_count']" />
            <x-stat-card label="Received in filter" :paisa="$summary['received_paisa']" />
        </div>

        <div class="overflow-x-auto border-t border-border">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Receipt no.') }}</flux:table.column>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                    <flux:table.column>{{ __('Project') }}</flux:table.column>
                    <flux:table.column>{{ __('Type') }}</flux:table.column>
                    <flux:table.column>{{ __('Method') }}</flux:table.column>
                    <flux:table.column>{{ __('Reference') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Amount') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($receipts as $receipt)
                        <flux:table.row :key="$receipt->id">
                            <flux:table.cell class="font-mono text-xs">
                                <flux:link :href="route('client-receipts.show', $receipt)" wire:navigate>{{ $receipt->receipt_no }}</flux:link>
                            </flux:table.cell>
                            <flux:table.cell><x-date :date="$receipt->received_on" /></flux:table.cell>
                            <flux:table.cell>
                                <div class="font-mono text-xs">{{ $receipt->project?->code ?? '—' }}</div>
                                <flux:text class="text-xs text-zinc-500">{{ $receipt->project?->name }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>{{ __($receipt->type->label()) }}</flux:table.cell>
                            <flux:table.cell>{{ str($receipt->method->value)->replace('_', ' ')->title() }}</flux:table.cell>
                            <flux:table.cell class="text-xs text-zinc-500">{{ $receipt->reference_no ?: '—' }}</flux:table.cell>
                            <flux:table.cell class="text-right font-semibold tabular-nums"><x-money :paisa="$receipt->amount_paisa" /></flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7">
                                <x-empty-state
                                    variant="table"
                                    title="{{ __('No client receipts found') }}"
                                    description="{{ __('Record a client receipt from a project to track money coming in.') }}"
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        @if ($receipts->hasPages())
            <div class="border-t border-border px-5 py-3">{{ $receipts->links() }}</div>
        @endif
    </x-panel>
</div>
