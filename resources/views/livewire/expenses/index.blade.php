<div class="space-y-5">
    <x-page-header
        title="{{ __('Expenses') }}"
        description="{{ __('Track company expenses and receipt metadata.') }}"
    >
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" :href="route('expenses.create')" wire:navigate>
                {{ __('Record expense') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <x-panel flush>
        <x-filter-bar>
            <flux:select wire:model.live="category" :label="__('Category')" class="w-full sm:min-w-52">
                <flux:select.option value="">{{ __('All categories') }}</flux:select.option>
                @foreach ($categories as $option)
                    <flux:select.option :value="$option->value">{{ __($option->label()) }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model.live="from" type="date" :label="__('From')" />
            <flux:input wire:model.live="to" type="date" :label="__('To')" />
        </x-filter-bar>

        <div class="grid gap-3 px-5 pb-5 sm:grid-cols-2">
            <x-stat-card label="Expenses" :value="$summary['expense_count']" />
            <x-stat-card label="Total in filter" :paisa="$summary['total_paisa']" />
        </div>

        <div class="overflow-x-auto border-t border-border">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Expense no.') }}</flux:table.column>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                    <flux:table.column>{{ __('Category') }}</flux:table.column>
                    <flux:table.column>{{ __('Payee') }}</flux:table.column>
                    <flux:table.column>{{ __('Method') }}</flux:table.column>
                    <flux:table.column>{{ __('Reference') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Amount') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($expenses as $expense)
                        <flux:table.row :key="$expense->id">
                            <flux:table.cell class="font-mono text-xs">
                                <flux:link :href="route('expenses.show', $expense)" wire:navigate>{{ $expense->expense_no }}</flux:link>
                            </flux:table.cell>
                            <flux:table.cell><x-date :date="$expense->expense_date" /></flux:table.cell>
                            <flux:table.cell>{{ __($expense->category->label()) }}</flux:table.cell>
                            <flux:table.cell>{{ $expense->payee ?: '—' }}</flux:table.cell>
                            <flux:table.cell>{{ str($expense->method->value)->replace('_', ' ')->title() }}</flux:table.cell>
                            <flux:table.cell class="text-xs text-zinc-500">{{ $expense->reference_no ?: '—' }}</flux:table.cell>
                            <flux:table.cell class="text-right font-semibold tabular-nums"><x-money :paisa="$expense->amount_paisa" /></flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7">
                                <x-empty-state
                                    variant="table"
                                    title="{{ __('No expenses found') }}"
                                    description="{{ __('Record company expenses to track money going out.') }}"
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        @if ($expenses->hasPages())
            <div class="border-t border-border px-5 py-3">{{ $expenses->links() }}</div>
        @endif
    </x-panel>
</div>
