<div class="space-y-5">
    <x-page-header title="{{ $project->name }}">
        <div class="flex flex-wrap items-center gap-2">
            <flux:badge color="zinc" size="sm" class="font-mono">{{ $project->code }}</flux:badge>
            <x-status-badge
                :tone="match ($project->status) {
                    \App\Enums\ProjectStatus::Active => 'success',
                    \App\Enums\ProjectStatus::Completed => 'neutral',
                    default => 'warning',
                }"
            >
                {{ str($project->status->value)->replace('_', ' ')->title() }}
            </x-status-badge>
        </div>

        <div>
            {{ $project->client_name }} · {{ $project->location_ward }},
            {{ $project->location_municipality }}, {{ $project->location_district }}
        </div>

        <div>
            {{ __('Timeline') }}:
            <x-date :date="$project->start_date" /> – <x-date :date="$project->end_date" />
            · {{ __('PM') }}: {{ $project->manager?->name }}
        </div>

        <x-slot:actions>
            <flux:dropdown position="bottom" align="end">
                <flux:button
                    size="sm"
                    variant="ghost"
                    icon="bell"
                    aria-label="{{ __('Project notifications') }}"
                    data-test="project-notifications"
                >
                    {{ __('Notifications') }}
                    @if ($dashboard['notification_count'] > 0)
                        <flux:badge size="sm" color="red">{{ $dashboard['notification_count'] }}</flux:badge>
                    @endif
                </flux:button>

                <flux:menu class="w-80 max-w-[calc(100vw-2rem)]">
                    <flux:menu.heading>{{ __('Project notifications') }}</flux:menu.heading>

                    @if ($dashboard['shortage_count'] > 0)
                        <flux:menu.item
                            :href="route('projects.reconciliation', $project)"
                            wire:navigate
                            icon="exclamation-triangle"
                            class="items-start"
                        >
                            <div class="min-w-0">
                                <div class="font-semibold">{{ __('Material shortage') }}</div>
                                <div class="text-xs font-normal text-zinc-500 dark:text-zinc-400">
                                    {{ trans_choice(
                                        'BOQ line is short of the :threshold% issue threshold|BOQ lines are short of the :threshold% issue threshold',
                                        $dashboard['shortage_count'],
                                        ['threshold' => $dashboard['reconciliation_issue_threshold_pct']]
                                    ) }}
                                </div>
                            </div>
                            <flux:badge size="sm" color="amber">{{ $dashboard['shortage_count'] }}</flux:badge>
                        </flux:menu.item>
                    @endif

                    @if ($dashboard['over_consumed_count'] > 0)
                        <flux:menu.item
                            :href="route('projects.reconciliation', $project)"
                            wire:navigate
                            icon="exclamation-triangle"
                            class="items-start"
                        >
                            <div class="min-w-0">
                                <div class="font-semibold">{{ __('Consumption without issue trail') }}</div>
                                <div class="text-xs font-normal text-zinc-500 dark:text-zinc-400">
                                    {{ trans_choice(
                                        'Line shows consumption without an issue trail|Lines show consumption without an issue trail',
                                        $dashboard['over_consumed_count']
                                    ) }}
                                </div>
                            </div>
                            <flux:badge size="sm" color="amber">{{ $dashboard['over_consumed_count'] }}</flux:badge>
                        </flux:menu.item>
                    @endif

                    @if ($dashboard['notification_count'] === 0)
                        <div class="px-2 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('No project notifications') }}
                        </div>
                    @endif

                    <flux:menu.separator />
                    <flux:menu.item
                        :href="route('projects.reconciliation', $project)"
                        wire:navigate
                        icon="clipboard-document-list"
                    >
                        {{ __('Open material reconciliation') }}
                    </flux:menu.item>
                    @if (Route::has('projects.client-receipts'))
                        <flux:menu.item
                            :href="route('projects.client-receipts', $project)"
                            wire:navigate
                            icon="banknotes"
                        >
                            {{ __('Client receipts') }}
                        </flux:menu.item>
                    @endif
                </flux:menu>
            </flux:dropdown>

            @can('manage')
                <flux:button icon="pencil-square" :href="route('projects.edit', $project)" wire:navigate>
                    {{ __('Edit project') }}
                </flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-panel title="{{ __('Client money') }}" description="{{ __('Collected against the full contract value, including advances.') }}">
        <x-slot:actions>
            @if (Route::has('projects.client-receipts'))
                <flux:button size="sm" variant="ghost" :href="route('projects.client-receipts', $project)" wire:navigate>
                    {{ __('View receipts') }}
                </flux:button>
            @endif
        </x-slot:actions>

        <div class="grid divide-y divide-border sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            <div class="min-w-0 space-y-1 px-1 py-2 sm:px-5 sm:py-1">
                <flux:text class="text-xs font-medium tracking-wide text-zinc-500 uppercase dark:text-zinc-400">
                    {{ __('Contract value') }}
                </flux:text>
                <div class="min-w-0 max-w-full break-words text-xl font-semibold leading-tight tabular-nums [overflow-wrap:anywhere]">
                    <x-money :paisa="$dashboard['contract_value_paisa']" />
                </div>
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Agreed with client') }}
                </flux:text>
            </div>

            <div class="min-w-0 space-y-1 px-1 py-4 sm:px-5 sm:py-1">
                <flux:text class="text-xs font-medium tracking-wide text-zinc-500 uppercase dark:text-zinc-400">
                    {{ __('Collected from client') }}
                </flux:text>
                <div class="min-w-0 max-w-full break-words text-xl font-semibold leading-tight tabular-nums [overflow-wrap:anywhere]">
                    <x-money :paisa="$dashboard['client_collected_paisa']" />
                </div>
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Including advance') }}
                </flux:text>
            </div>

            <div class="min-w-0 space-y-1 rounded-xl bg-warning/10 px-3 py-4 sm:-my-3 sm:px-5">
                <flux:text class="text-xs font-medium tracking-wide text-warning uppercase">
                    {{ __('Remaining to collect') }}
                </flux:text>
                <div class="min-w-0 max-w-full break-words text-xl font-semibold leading-tight tracking-tight tabular-nums text-warning [overflow-wrap:anywhere] sm:text-2xl">
                    <x-money :paisa="$dashboard['client_remaining_to_collect_paisa']" />
                </div>
                <flux:text class="text-xs text-warning/80">
                    {{ __('Contract value minus collected') }}
                </flux:text>
            </div>
        </div>
    </x-panel>

    @if (Route::has('projects.payments'))
        <x-balance-strip
            title="{{ __('Contractor payments') }}"
            :agreed="$paymentSummary['agreed_paisa']"
            :paid="$paymentSummary['paid_paisa']"
            :balance="$paymentSummary['balance_paisa']"
        >
            <x-slot:actions>
                <flux:button size="sm" variant="primary" icon="banknotes" :href="route('projects.payments', $project)" wire:navigate>
                    {{ __('Payment ledger') }}
                </flux:button>
            </x-slot:actions>
        </x-balance-strip>
    @endif

    <x-panel
        title="{{ __('JV Partners') }}"
        description="{{ __('Joint venture share allocation for work, billing, cost, and Profit/Loss.') }}"
        flush
    >
        @if ($jvSummary['partners']->isEmpty())
            <div class="px-5 py-6">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('No JV partners added to this project.') }}
                </flux:text>
            </div>
        @else
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Partner') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('Share') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('Work allocation') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('Billing sharing') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('Cost sharing') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('Profit/Loss') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($jvSummary['partners'] as $partner)
                            <flux:table.row :key="$partner['name']">
                                <flux:table.cell class="font-medium">{{ $partner['name'] }}</flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums">
                                    <x-number :value="$partner['share_percent']" :decimals="2" suffix="%" />
                                </flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$partner['work_paisa']" /></flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$partner['billing_paisa']" /></flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$partner['cost_paisa']" /></flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums">
                                    <div @class([
                                        'inline-flex items-center justify-end gap-1',
                                        'text-emerald-600 dark:text-emerald-400' => $partner['profit_paisa'] > 0,
                                        'text-amber-600 dark:text-amber-400' => $partner['profit_paisa'] < 0,
                                        'text-zinc-700 dark:text-zinc-200' => $partner['profit_paisa'] === 0,
                                    ])>
                                        @if ($partner['profit_paisa'] > 0)
                                            <flux:icon.arrow-up class="size-4" />
                                        @elseif ($partner['profit_paisa'] < 0)
                                            <flux:icon.arrow-down class="size-4" />
                                        @endif
                                        <x-money :paisa="$partner['profit_paisa']" />
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                        <flux:table.row>
                            <flux:table.cell class="font-semibold">{{ __('Total') }}</flux:table.cell>
                            <flux:table.cell class="text-right font-semibold tabular-nums">
                                <x-number :value="$jvSummary['totals']['share_percent']" :decimals="2" suffix="%" />
                            </flux:table.cell>
                            <flux:table.cell class="text-right font-semibold tabular-nums"><x-money :paisa="$jvSummary['totals']['work_paisa']" /></flux:table.cell>
                            <flux:table.cell class="text-right font-semibold tabular-nums"><x-money :paisa="$jvSummary['totals']['billing_paisa']" /></flux:table.cell>
                            <flux:table.cell class="text-right font-semibold tabular-nums"><x-money :paisa="$jvSummary['totals']['cost_paisa']" /></flux:table.cell>
                            <flux:table.cell class="text-right font-semibold tabular-nums">
                                <div @class([
                                    'inline-flex items-center justify-end gap-1',
                                    'text-emerald-600 dark:text-emerald-400' => $jvSummary['totals']['profit_paisa'] > 0,
                                    'text-amber-600 dark:text-amber-400' => $jvSummary['totals']['profit_paisa'] < 0,
                                    'text-zinc-700 dark:text-zinc-200' => $jvSummary['totals']['profit_paisa'] === 0,
                                ])>
                                    @if ($jvSummary['totals']['profit_paisa'] > 0)
                                        <flux:icon.arrow-up class="size-4" />
                                    @elseif ($jvSummary['totals']['profit_paisa'] < 0)
                                        <flux:icon.arrow-down class="size-4" />
                                    @endif
                                    <x-money :paisa="$jvSummary['totals']['profit_paisa']" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    </flux:table.rows>
                </flux:table>
            </div>
            <div class="border-t border-border px-5 py-3">
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Work allocation is based on contract value. Billing sharing is based on client money collected. Cost sharing is based on contractor payments paid on this project. Profit/loss is billing minus cost.') }}
                </flux:text>
            </div>
        @endif
    </x-panel>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card
            label="BOQ Value"
            :paisa="$dashboard['boq_value_paisa']"
            hint="Approved BOQ total"
        />

        <x-stat-card
            label="Executed"
            :value="$dashboard['executed_pct']"
            :decimals="1"
            suffix="%"
            hint="Measured progress (weighted by BOQ value)"
        />

        <x-stat-card
            label="Work Certified"
            :paisa="$dashboard['certified_paisa']"
            hint="Certified running bills"
        />

        <x-stat-card
            label="Advance Remaining"
            :paisa="$dashboard['advance_remaining_paisa']"
            hint="Not yet adjusted against bills"
        />

        <x-stat-card
            label="Milestones"
            :value="$dashboard['milestone_pct']"
            :decimals="1"
            suffix="%"
            hint="Task completion"
        />

        <x-stat-card
            label="Open POs"
            :value="$dashboard['open_po_count']"
            hint="Not yet closed"
        />

        <x-stat-card
            label="Manpower"
            :value="$dashboard['manpower']['count']"
        >
            <x-slot:footer>
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                    @if ($dashboard['manpower']['is_today'])
                        {{ __('Today') }}
                    @elseif ($dashboard['manpower']['as_of'])
                        {{ __('As of') }} <x-date :date="$dashboard['manpower']['as_of']" />
                    @else
                        {{ __('No site report yet') }}
                    @endif
                </flux:text>
            </x-slot:footer>
        </x-stat-card>
    </div>

    <x-panel flush>
        <div class="flex flex-wrap items-center gap-1 border-b border-border p-2">
            @if (Route::has('projects.boq'))
                <flux:button size="sm" variant="ghost" :href="route('projects.boq', $project)" wire:navigate>
                    {{ __('BOQ') }}
                </flux:button>
            @endif

            @if (Route::has('projects.milestones'))
                <flux:button size="sm" variant="ghost" :href="route('projects.milestones', $project)" wire:navigate>
                    {{ __('Milestones/Tasks') }}
                </flux:button>
            @endif

            @if (Route::has('projects.dsr'))
                <flux:button size="sm" variant="ghost" :href="route('projects.dsr', $project)" wire:navigate>
                    {{ __('DSR') }}
                </flux:button>
            @endif

            @if (Route::has('projects.reconciliation'))
                <flux:button size="sm" variant="ghost" :href="route('projects.reconciliation', $project)" wire:navigate>
                    {{ __('Reconciliation') }}
                </flux:button>
            @endif

            @if (Route::has('projects.running-bills'))
                <flux:button size="sm" variant="ghost" :href="route('projects.running-bills', $project)" wire:navigate>
                    {{ __('Running bills') }}
                </flux:button>
            @endif

            @if (Route::has('projects.payments'))
                <flux:button size="sm" variant="ghost" :href="route('projects.payments', $project)" wire:navigate>
                    {{ __('Payments') }}
                </flux:button>
            @endif

            @if (Route::has('documents.index'))
                <flux:button size="sm" variant="ghost" :href="route('documents.index', ['project' => $project->id])" wire:navigate>
                    {{ __('Documents') }}
                </flux:button>
            @endif
        </div>

        <div class="space-y-5 p-6">
            <div class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium">{{ __('BOQ execution') }}</span>
                    <span class="text-zinc-500">
                        <x-money :paisa="$dashboard['executed_value_paisa']" />
                        {{ __('of') }}
                        <x-money :paisa="$dashboard['boq_value_paisa']" />
                    </span>
                </div>
                <x-progress-bar :pct="$dashboard['executed_pct']" />
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <x-panel title="{{ __('Pending approvals') }}">
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span>{{ __('Material requisitions') }}</span>
                            <x-status-badge tone="warning">{{ $dashboard['pending_mr_count'] }}</x-status-badge>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>{{ __('Open purchase orders') }}</span>
                            <x-status-badge tone="info">{{ $dashboard['open_po_count'] }}</x-status-badge>
                        </div>
                    </div>
                </x-panel>

                <x-panel title="{{ __('Material health') }}">
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span>{{ __('Shortage lines') }}</span>
                            <x-status-badge tone="warning">{{ $dashboard['shortage_count'] }}</x-status-badge>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>{{ __('Over-consumed lines') }}</span>
                            <x-status-badge tone="danger">{{ $dashboard['over_consumed_count'] }}</x-status-badge>
                        </div>
                    </div>
                </x-panel>
            </div>
        </div>
    </x-panel>
</div>
