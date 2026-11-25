<div class="space-y-5">
    <x-page-header title="{{ __('Payment record') }}">
        <div class="flex flex-wrap items-center gap-2">
            <flux:link :href="route('payments.index')" wire:navigate class="text-sm">
                {{ __('Back to payments') }}
            </flux:link>
            <flux:badge color="zinc" size="sm" class="font-mono">{{ $payment->payment_no }}</flux:badge>
        </div>

        <div>
            {{ $payment->project?->name }} · {{ $payment->job?->title }} · {{ $payment->contractor?->name }}
        </div>

        <x-slot:actions>
            <flux:button icon="printer" :href="route('print.payment', $payment)" target="_blank">
                {{ __('Print receipt') }}
            </flux:button>
            <flux:button icon="pencil-square" :href="route('payments.edit', $payment)" wire:navigate>
                {{ __('Edit') }}
            </flux:button>
            <flux:button
                variant="danger"
                icon="trash"
                wire:click="delete"
                wire:confirm="{{ __('Delete this payment? The job balance will increase.') }}"
            >
                {{ __('Delete') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Amount paid" :paisa="$payment->amount_paisa" />
        <x-panel class="space-y-1.5">
            <flux:text class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Paid on') }}</flux:text>
            <div class="min-w-0 max-w-full break-words text-xl font-semibold leading-tight tracking-tight [overflow-wrap:anywhere] sm:text-2xl">
                <x-date :date="$payment->paid_on" />
            </div>
        </x-panel>
        <x-stat-card
            label="Task balance after payment"
            :paisa="$payment->job?->balance_paisa"
            hint="Agreed minus all recorded payments"
        />
    </div>

    <x-panel title="{{ __('Record details') }}">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <flux:text class="text-xs text-zinc-500">{{ __('Project') }}</flux:text>
                <div class="mt-1">
                    @if ($payment->project)
                        <flux:link :href="route('projects.show', $payment->project)" wire:navigate>
                            {{ $payment->project->code }} · {{ $payment->project->name }}
                        </flux:link>
                    @else
                        —
                    @endif
                </div>
            </div>
            <div>
                <flux:text class="text-xs text-zinc-500">{{ __('Task') }}</flux:text>
                <div class="mt-1">
                    @if ($payment->job)
                        <flux:link :href="route('jobs.show', $payment->job)" wire:navigate>
                            {{ $payment->job->title }}
                        </flux:link>
                    @else
                        —
                    @endif
                </div>
            </div>
            <div>
                <flux:text class="text-xs text-zinc-500">{{ __('Contractor') }}</flux:text>
                <div class="mt-1">
                    @if ($payment->contractor)
                        <flux:link :href="route('contractors.show', $payment->contractor)" wire:navigate>
                            {{ $payment->contractor->name }}
                        </flux:link>
                    @else
                        —
                    @endif
                </div>
            </div>
            <div>
                <flux:text class="text-xs text-zinc-500">{{ __('Method') }}</flux:text>
                <div class="mt-1">{{ str($payment->method->value)->replace('_', ' ')->title() }}</div>
            </div>
            <div>
                <flux:text class="text-xs text-zinc-500">{{ __('Reference') }}</flux:text>
                <div class="mt-1 font-mono text-sm">{{ $payment->reference_no ?: '—' }}</div>
            </div>
            <div>
                <flux:text class="text-xs text-zinc-500">{{ __('Recorded by') }}</flux:text>
                <div class="mt-1">{{ $payment->createdBy?->name ?? '—' }}</div>
            </div>
        </div>

        @if ($payment->note)
            <div class="mt-4 border-t border-border pt-4">
                <flux:text class="text-xs text-zinc-500">{{ __('Note') }}</flux:text>
                <flux:text class="mt-1">{{ $payment->note }}</flux:text>
            </div>
        @endif
    </x-panel>
</div>
