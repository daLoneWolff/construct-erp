<div class="space-y-5">
    <x-page-header title="{{ $contractor->name }}">
        <div class="flex flex-wrap items-center gap-2">
            <flux:link :href="route('contractors.index')" wire:navigate class="text-sm">
                {{ __('Back to contractors') }}
            </flux:link>
            <x-status-badge :tone="$contractor->is_active ? 'success' : 'neutral'">
                {{ $contractor->is_active ? __('Active') : __('Inactive') }}
            </x-status-badge>
            @if ($contractor->pan)
                <flux:badge size="sm" color="zinc" class="font-mono">{{ $contractor->pan }}</flux:badge>
            @endif
        </div>

        <div>
            {{ $contractor->phone ?: __('No phone') }}
            @if ($contractor->address)
                · {{ $contractor->address }}
            @endif
        </div>

        @if ($contractor->notes)
            <div>{{ $contractor->notes }}</div>
        @endif

        <x-slot:actions>
            <flux:button
                icon="printer"
                :href="route('print.contractor-ledger', array_filter([
                    'contractor' => $contractor->id,
                    'project' => $projectId,
                    'from' => $from,
                    'to' => $to,
                ]))"
                target="_blank"
            >
                {{ __('Print ledger') }}
            </flux:button>
            <flux:button icon="pencil-square" :href="route('contractors.edit', $contractor)" wire:navigate>
                {{ __('Edit') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <x-balance-strip
        title="{{ __('Selected scope') }}"
        :agreed="$ledger['agreed_paisa']"
        :paid="$ledger['paid_paisa']"
        :balance="$ledger['balance_paisa']"
    >
        <x-slot:footer>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400">
                <span>{{ __('Tasks') }}: {{ count($ledger['jobs']) }}</span>
                <span>{{ __('Selected payments') }}: {{ $ledger['payment_count'] }}</span>
                <span><x-money :paisa="$ledger['filtered_paid_paisa']" /></span>
            </div>
        </x-slot:footer>
    </x-balance-strip>

    <x-panel flush>
        <x-filter-bar>
            <flux:select wire:model.live="projectId" :label="__('Project')" class="w-full sm:max-w-64">
                <flux:select.option value="">{{ __('All projects') }}</flux:select.option>
                @foreach ($projects as $option)
                    <flux:select.option :value="$option->id">
                        {{ $option->code }} — {{ $option->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model.live="from" type="date" :label="__('Payment date from')" />
            <flux:input wire:model.live="to" type="date" :label="__('Payment date to')" />

            @if ($hasPayableTasks)
                <x-slot:actions>
                    <flux:button
                        variant="primary"
                        icon="banknotes"
                        :href="route('contractors.payments.create', array_filter(['contractor' => $contractor, 'project' => $projectId], fn ($value) => filled($value)))"
                        wire:navigate
                    >
                        {{ __('Record Payment') }}
                    </flux:button>
                </x-slot:actions>
            @endif
        </x-filter-bar>

        <div class="px-5 pb-4">
            <flux:text class="text-xs text-zinc-500">
                {{ __('Date filters change the payment list only. Selecting a project also scopes the tasks and balances shown.') }}
            </flux:text>
        </div>
    </x-panel>

    <x-panel title="{{ __('Assigned tasks') }}" flush>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Task') }}</flux:table.column>
                    <flux:table.column>{{ __('Project') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Agreed') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Paid') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Balance') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($ledger['jobs'] as $row)
                        <flux:table.row :key="$row['job_id']">
                            <flux:table.cell>
                                <flux:link :href="route('jobs.show', $row['job'])" wire:navigate class="font-medium">
                                    {{ $row['job']->title }}
                                </flux:link>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="font-mono text-xs">{{ $row['project']?->code }}</div>
                                <flux:text class="text-xs text-zinc-500">{{ $row['project']?->name }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <x-status-badge>
                                    {{ str($row['job']->status->value)->replace('_', ' ')->title() }}
                                </x-status-badge>
                            </flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$row['agreed_paisa']" /></flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$row['paid_paisa']" /></flux:table.cell>
                            <flux:table.cell class="text-right font-semibold tabular-nums"><x-money :paisa="$row['balance_paisa']" /></flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6">
                                <x-empty-state
                                    variant="table"
                                    title="{{ __('No tasks assigned') }}"
                                    description="{{ __('Assign a task to this contractor to start tracking payments.') }}"
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </x-panel>

    <x-panel title="{{ __('Payment history') }}" flush>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Payment no.') }}</flux:table.column>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                    <flux:table.column>{{ __('Project') }}</flux:table.column>
                    <flux:table.column>{{ __('Task') }}</flux:table.column>
                    <flux:table.column>{{ __('Method') }}</flux:table.column>
                    <flux:table.column>{{ __('Reference') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Amount') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($ledger['payments'] as $payment)
                        <flux:table.row :key="$payment->id">
                            <flux:table.cell class="font-mono text-xs">
                                <flux:link :href="route('payments.show', $payment)" wire:navigate>
                                    {{ $payment->payment_no }}
                                </flux:link>
                            </flux:table.cell>
                            <flux:table.cell><x-date :date="$payment->paid_on" /></flux:table.cell>
                            <flux:table.cell>{{ $payment->project?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $payment->job?->title ?? '—' }}</flux:table.cell>
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
                            <flux:table.cell colspan="8">
                                <x-empty-state
                                    variant="table"
                                    title="{{ __('No payments in this filter') }}"
                                    description="{{ __('Clear the filters or record a payment against an assigned task.') }}"
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </x-panel>
</div>
