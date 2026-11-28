<div class="space-y-5">
    <x-page-header title="{{ __('Client receipts') }}">
        <div class="flex flex-wrap items-center gap-2">
            <flux:link :href="route('projects.show', $project)" wire:navigate class="text-sm">
                {{ __('Back to project') }}
            </flux:link>
            <flux:badge color="zinc" size="sm" class="font-mono">{{ $project->code }}</flux:badge>
        </div>
        <div>{{ $project->name }}</div>

        <x-slot:actions>
            <flux:button variant="primary" icon="banknotes" :href="route('projects.client-receipts.create', $project)" wire:navigate>
                {{ __('Record receipt') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <x-panel title="{{ __('Client money') }}" description="{{ __('Collected against the full contract value, including advances.') }}">
        <div class="grid gap-4 sm:grid-cols-3">
            <x-stat-card label="Contract value" :paisa="$summary['contract_value_paisa']" />
            <x-stat-card label="Collected from client" :paisa="$summary['collected_paisa']" />
            <x-stat-card label="Remaining to collect" :paisa="$summary['remaining_to_collect_paisa']" :tone="$summary['remaining_to_collect_paisa'] > 0 ? 'amber' : 'green'" />
        </div>
    </x-panel>

    <x-panel title="{{ __('Receipts') }}" flush>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Receipt no.') }}</flux:table.column>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                    <flux:table.column>{{ __('Type') }}</flux:table.column>
                    <flux:table.column>{{ __('Running bill') }}</flux:table.column>
                    <flux:table.column>{{ __('Method') }}</flux:table.column>
                    <flux:table.column>{{ __('Reference') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Amount') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($receipts as $receipt)
                        <flux:table.row :key="$receipt->id">
                            <flux:table.cell class="font-mono text-xs">
                                <flux:link :href="route('client-receipts.show', $receipt)" wire:navigate>
                                    {{ $receipt->receipt_no }}
                                </flux:link>
                            </flux:table.cell>
                            <flux:table.cell><x-date :date="$receipt->received_on" /></flux:table.cell>
                            <flux:table.cell>{{ __($receipt->type->label()) }}</flux:table.cell>
                            <flux:table.cell>{{ $receipt->runningBill?->code ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ str($receipt->method->value)->replace('_', ' ')->title() }}</flux:table.cell>
                            <flux:table.cell class="text-xs text-zinc-500">{{ $receipt->reference_no ?: '—' }}</flux:table.cell>
                            <flux:table.cell class="text-right font-semibold tabular-nums"><x-money :paisa="$receipt->amount_paisa" /></flux:table.cell>
                            <flux:table.cell class="text-right">
                                <flux:button size="sm" variant="ghost" icon="eye" :href="route('client-receipts.show', $receipt)" wire:navigate />
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8">
                                <x-empty-state
                                    variant="table"
                                    title="{{ __('No client receipts yet') }}"
                                    description="{{ __('Record advance or bill receipts to track money coming from the client.') }}"
                                    cta="{{ __('Record receipt') }}"
                                    :href="route('projects.client-receipts.create', $project)"
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
