<div class="space-y-5">
    <x-page-header
        title="{{ __('Contractor payments') }}"
        description="{{ __('Every recorded payout with its project, task, payee and outstanding context.') }}"
    >
        <x-slot:actions>
            <flux:button
                icon="arrow-down-tray"
                :href="route('payments.export', array_filter([
                    'project' => $projectId,
                    'contractor' => $contractorId,
                    'from' => $from,
                    'to' => $to,
                ]))"
            >
                {{ __('Export CSV') }}
            </flux:button>
            <flux:button variant="ghost" icon="users" :href="route('contractors.index')" wire:navigate>
                {{ __('Manage contractors') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <x-panel flush>
        <x-filter-bar>
            <flux:select wire:model.live="projectId" :label="__('Project')" class="w-full sm:min-w-56">
                <flux:select.option value="">{{ __('All projects') }}</flux:select.option>
                @foreach ($projects as $option)
                    <flux:select.option :value="$option->id">
                        {{ $option->code }} — {{ $option->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="contractorId" :label="__('Contractor')" class="w-full sm:min-w-48">
                <flux:select.option value="">{{ __('All contractors') }}</flux:select.option>
                @foreach ($contractors as $option)
                    <flux:select.option :value="$option->id">{{ $option->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model.live="from" type="date" :label="__('Paid from')" />
            <flux:input wire:model.live="to" type="date" :label="__('Paid to')" />
        </x-filter-bar>

        <div class="grid gap-3 px-5 pb-5 sm:grid-cols-2">
            <x-stat-card label="Payments" :value="$summary['payment_count']" />
            <x-stat-card label="Paid in filter" :paisa="$summary['paid_paisa']" />
        </div>

        <div class="overflow-x-auto border-t border-border">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Payment no.') }}</flux:table.column>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                    <flux:table.column>{{ __('Project') }}</flux:table.column>
                    <flux:table.column>{{ __('Task') }}</flux:table.column>
                    <flux:table.column>{{ __('Contractor') }}</flux:table.column>
                    <flux:table.column>{{ __('Method') }}</flux:table.column>
                    <flux:table.column>{{ __('Reference') }}</flux:table.column>
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
                            <flux:table.cell>
                                <div class="font-mono text-xs">{{ $payment->project?->code ?? __('?') }}</div>
                                <flux:text class="text-xs text-zinc-500">{{ $payment->project?->name ?? __('No project') }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($payment->job)
                                    <flux:link :href="route('jobs.show', $payment->job)" wire:navigate>
                                        {{ $payment->job->title }}
                                    </flux:link>
                                @else
                                    <flux:text class="text-zinc-500">{{ __('General payment') }}</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($payment->contractor)
                                    <flux:link :href="route('contractors.show', $payment->contractor)" wire:navigate>
                                        {{ $payment->contractor->name }}
                                    </flux:link>
                                @else
                                    —
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ str($payment->method->value)->replace('_', ' ')->title() }}</flux:table.cell>
                            <flux:table.cell class="text-xs text-zinc-500">{{ $payment->reference_no ?: '—' }}</flux:table.cell>
                            <flux:table.cell class="text-right font-semibold tabular-nums"><x-money :paisa="$payment->amount_paisa" /></flux:table.cell>
                            <flux:table.cell class="text-right">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="eye"
                                    aria-label="{{ __('View payment') }}"
                                    :href="route('payments.show', $payment)"
                                    wire:navigate
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="9">
                                <x-empty-state
                                    variant="table"
                                    title="{{ __('No payments found') }}"
                                    description="{{ __('Add a contractor, assign a task, then record the first payment.') }}"
                                    cta="{{ __('Add your first contractor') }}"
                                    :href="route('contractors.create')"
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        @if ($payments->hasPages())
            <div class="border-t border-border px-5 py-3">
                {{ $payments->links() }}
            </div>
        @endif
    </x-panel>
</div>
