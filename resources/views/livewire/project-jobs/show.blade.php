<div class="space-y-5">
    <x-page-header :title="$job->title">
        <div class="flex flex-wrap items-center gap-2">
            <flux:link :href="route('projects.milestones', $job->project)" wire:navigate class="text-sm">
                {{ __('Back to milestones') }}
            </flux:link>
            <flux:badge color="zinc" size="sm" class="font-mono">{{ $job->project?->code }}</flux:badge>
            <flux:badge size="sm" color="zinc">
                {{ str($job->status->value)->replace('_', ' ')->title() }}
            </flux:badge>
            @if ($job->isPayable())
                <flux:badge size="sm" color="blue">{{ __('Contractor task') }}</flux:badge>
            @endif
        </div>
        <div>{{ $job->project?->name }}</div>

        <x-slot:actions>
            <flux:button icon="pencil-square" :href="route('jobs.edit', $job)" wire:navigate>
                {{ __('Edit task') }}
            </flux:button>
            @if ($job->isPayable())
                <flux:button
                    variant="primary"
                    icon="plus"
                    :href="route('jobs.payments.create', $job)"
                    wire:navigate
                >
                    {{ __('Record payment') }}
                </flux:button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Agreed amount" :paisa="$job->agreed_amount_paisa" />
        <x-stat-card label="Paid" :paisa="$paidPaisa" tone="green" />
        <x-stat-card label="Outstanding" :paisa="$balancePaisa" :tone="$balancePaisa > 0 ? 'amber' : 'green'" />
    </div>

    <x-panel title="{{ __('Task information') }}">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <flux:text class="text-xs text-zinc-500">{{ __('Milestone') }}</flux:text>
                <div class="mt-1 font-medium">{{ $job->milestone?->title ?? '—' }}</div>
            </div>
            <div>
                <flux:text class="text-xs text-zinc-500">{{ __('Assigned to') }}</flux:text>
                <div class="mt-1">
                    @if ($job->contractor)
                        <flux:link :href="route('contractors.show', $job->contractor)" wire:navigate class="font-medium">
                            {{ $job->contractor->name }}
                        </flux:link>
                        <flux:badge size="sm" color="blue" class="ml-1">{{ __('Contractor') }}</flux:badge>
                    @elseif ($job->assignee)
                        <span class="font-medium">{{ $job->assignee->name }}</span>
                    @else
                        <flux:badge size="sm" color="zinc">{{ __('Unassigned') }}</flux:badge>
                    @endif
                </div>
            </div>
            <div>
                <flux:text class="text-xs text-zinc-500">{{ __('Timeline') }}</flux:text>
                <div class="mt-1 font-medium">
                    <x-date :date="$job->starts_on" /> – <x-date :date="$job->due_on" />
                </div>
            </div>
        </div>
        @if ($job->description)
            <flux:text class="mt-4 text-sm">{{ $job->description }}</flux:text>
        @endif
    </x-panel>

    <x-panel title="{{ __('Payments') }}" flush>
        <x-slot:actions>
            <flux:badge size="sm" color="zinc">{{ $payments->count() }}</flux:badge>
        </x-slot:actions>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Payment no.') }}</flux:table.column>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                    <flux:table.column>{{ __('Method') }}</flux:table.column>
                    <flux:table.column>{{ __('Reference') }}</flux:table.column>
                    <flux:table.column>{{ __('Note') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Amount') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($payments as $payment)
                        <flux:table.row :key="$payment->id">
                            <flux:table.cell class="font-mono text-xs">
                                <flux:link :href="route('payments.show', $payment)" wire:navigate>
                                    {{ $payment->payment_no }}
                                </flux:link>
                            </flux:table.cell>
                            <flux:table.cell><x-date :date="$payment->paid_on" /></flux:table.cell>
                            <flux:table.cell>{{ str($payment->method->value)->replace('_', ' ')->title() }}</flux:table.cell>
                            <flux:table.cell class="text-xs text-zinc-500">{{ $payment->reference_no ?: '—' }}</flux:table.cell>
                            <flux:table.cell class="text-xs text-zinc-500">{{ $payment->note ?: '—' }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$payment->amount_paisa" /></flux:table.cell>
                            <flux:table.cell class="text-right">
                                <div class="flex justify-end gap-1">
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="eye"
                                        aria-label="{{ __('View payment') }}"
                                        :href="route('payments.show', $payment)"
                                        wire:navigate
                                    />
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="pencil-square"
                                        aria-label="{{ __('Edit payment') }}"
                                        :href="route('payments.edit', $payment)"
                                        wire:navigate
                                    />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7">
                                <x-empty-state
                                    variant="table"
                                    title="No payments recorded"
                                    description="Record the first payment against this task."
                                    cta="Record payment"
                                    :href="route('jobs.payments.create', $job)"
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </x-panel>
</div>
