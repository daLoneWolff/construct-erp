<div class="space-y-5">
    <x-page-header title="{{ __('Contractor payments') }}">
        <div class="flex flex-wrap items-center gap-2">
            <flux:link :href="route('projects.show', $project)" wire:navigate class="text-sm">
                {{ __('Back to project') }}
            </flux:link>
            <flux:badge color="zinc" size="sm" class="font-mono">{{ $project->code }}</flux:badge>
        </div>
        <div>{{ $project->name }}</div>

        <x-slot:actions>
            <flux:button icon="printer" :href="route('print.project-payments', $project)" target="_blank">
                {{ __('Print summary') }}
            </flux:button>
            <flux:button
                variant="primary"
                icon="clipboard-document-list"
                :href="route('projects.milestones', $project)"
                wire:navigate
            >
                {{ __('Milestones and tasks') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-stat-card label="Agreed work" :paisa="$summary['agreed_paisa']" />
        <x-stat-card label="Paid" :paisa="$summary['paid_paisa']" tone="green" />
        <x-stat-card label="Outstanding" :paisa="$summary['balance_paisa']" :tone="$summary['balance_paisa'] > 0 ? 'amber' : 'green'" />
        <x-stat-card label="Tasks" :value="$summary['job_count']" />
        <x-stat-card label="Contractors" :value="$summary['contractor_count']" />
    </div>

    <x-panel title="{{ __('By contractor') }}" flush>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Contractor') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Tasks') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Agreed') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Paid') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Balance') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($summary['contractors'] as $row)
                        <flux:table.row :key="$row['contractor_id']">
                            <flux:table.cell>
                                <flux:link :href="route('contractors.show', $row['contractor'])" wire:navigate>
                                    {{ $row['contractor']?->name }}
                                </flux:link>
                            </flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ $row['job_count'] }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$row['agreed_paisa']" /></flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$row['paid_paisa']" /></flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$row['balance_paisa']" /></flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5">
                                <x-empty-state
                                    variant="table"
                                    title="No assigned tasks"
                                    description="Add a contractor task to a milestone and assign a contractor to see a grouped payment roll-up."
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </x-panel>

    <x-panel title="{{ __('Tasks') }}" flush>
        <x-slot:actions>
            <flux:link :href="route('projects.milestones', $project)" wire:navigate class="text-sm">
                {{ __('Manage milestones') }}
            </flux:link>
        </x-slot:actions>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Task') }}</flux:table.column>
                    <flux:table.column>{{ __('Contractor') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Agreed') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Paid') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Balance') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($summary['jobs'] as $row)
                        <flux:table.row :key="$row['job_id']">
                            <flux:table.cell>
                                <flux:link :href="route('jobs.show', $row['job'])" wire:navigate>
                                    {{ $row['job']->title }}
                                </flux:link>
                            </flux:table.cell>
                            <flux:table.cell>{{ $row['contractor']?->name ?? __('Unassigned') }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$row['agreed_paisa']" /></flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$row['paid_paisa']" /></flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$row['balance_paisa']" /></flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5">
                                <x-empty-state
                                    variant="table"
                                    title="No tasks yet"
                                    description="Add tasks under a milestone before recording payments."
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </x-panel>

    <x-panel title="{{ __('Payments') }}" flush>
        <x-slot:actions>
            <flux:link :href="route('payments.index')" wire:navigate class="text-sm">
                {{ __('All payments') }}
            </flux:link>
        </x-slot:actions>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Payment no.') }}</flux:table.column>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                    <flux:table.column>{{ __('Task') }}</flux:table.column>
                    <flux:table.column>{{ __('Contractor') }}</flux:table.column>
                    <flux:table.column>{{ __('Method') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Amount') }}</flux:table.column>
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
                            <flux:table.cell>{{ $payment->job?->title ?? __('General payment') }}</flux:table.cell>
                            <flux:table.cell>{{ $payment->contractor?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ str($payment->method->value)->replace('_', ' ')->title() }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$payment->amount_paisa" /></flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6">
                                <x-empty-state
                                    variant="table"
                                    title="No payments recorded"
                                    description="Record a payment from a task to see it here."
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </x-panel>
</div>
