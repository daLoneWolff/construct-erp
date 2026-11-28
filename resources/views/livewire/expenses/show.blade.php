<div class="space-y-5">
    <x-page-header title="{{ $expense->expense_no }}">
        <div class="flex flex-wrap items-center gap-2">
            <flux:link :href="route('expenses.index')" wire:navigate class="text-sm">
                {{ __('Back to expenses') }}
            </flux:link>
            <flux:badge color="zinc" size="sm">{{ __($expense->category->label()) }}</flux:badge>
        </div>

        <x-slot:actions>
            <flux:button icon="pencil-square" :href="route('expenses.edit', $expense)" wire:navigate>
                {{ __('Edit') }}
            </flux:button>
            <flux:button variant="danger" icon="trash" wire:click="delete" wire:confirm="{{ __('Delete this expense?') }}">
                {{ __('Delete') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-5 lg:grid-cols-3">
        <x-panel title="{{ __('Expense details') }}" class="lg:col-span-2">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <flux:text class="text-xs text-zinc-500">{{ __('Date') }}</flux:text>
                    <div class="font-medium"><x-date :date="$expense->expense_date" /></div>
                </div>
                <div>
                    <flux:text class="text-xs text-zinc-500">{{ __('Category') }}</flux:text>
                    <div class="font-medium">{{ __($expense->category->label()) }}</div>
                </div>
                <div>
                    <flux:text class="text-xs text-zinc-500">{{ __('Payment method') }}</flux:text>
                    <div class="font-medium">{{ str($expense->method->value)->replace('_', ' ')->title() }}</div>
                </div>
                <div>
                    <flux:text class="text-xs text-zinc-500">{{ __('Payee') }}</flux:text>
                    <div class="font-medium">{{ $expense->payee ?: '—' }}</div>
                </div>
                <div>
                    <flux:text class="text-xs text-zinc-500">{{ __('Reference') }}</flux:text>
                    <div class="font-medium">{{ $expense->reference_no ?: '—' }}</div>
                </div>
                <div>
                    <flux:text class="text-xs text-zinc-500">{{ __('Recorded by') }}</flux:text>
                    <div class="font-medium">{{ $expense->creator?->name ?? '—' }}</div>
                </div>
                <div class="sm:col-span-2">
                    <flux:text class="text-xs text-zinc-500">{{ __('Description') }}</flux:text>
                    <div class="whitespace-pre-line font-medium">{{ $expense->description ?: '—' }}</div>
                </div>
            </dl>
        </x-panel>

        <div class="space-y-5">
            <x-panel title="{{ __('Amount') }}">
                <div class="text-2xl font-semibold tabular-nums"><x-money :paisa="$expense->amount_paisa" /></div>
                <flux:text class="mt-2 text-xs text-zinc-500">{{ __('Company expense amount') }}</flux:text>
            </x-panel>

            <x-panel title="{{ __('Attachments') }}">
                @if ($expense->attachments->isEmpty())
                    <flux:text class="text-sm text-zinc-500">{{ __('No attachments.') }}</flux:text>
                @else
                    <div class="space-y-2">
                        @foreach ($expense->attachments as $attachment)
                            <a
                                href="{{ route('expenses.attachments.show', [$expense, $attachment]) }}"
                                class="block rounded-xl border border-border bg-surface-subtle px-3 py-2 text-sm transition hover:border-zinc-400 dark:hover:border-zinc-500"
                            >
                                <div class="truncate font-mono text-zinc-900 dark:text-zinc-100">{{ $attachment->filename }}</div>
                                <div class="text-xs text-zinc-500">{{ $attachment->size_label }} · {{ $attachment->mime }}</div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </x-panel>
        </div>
    </div>
</div>
