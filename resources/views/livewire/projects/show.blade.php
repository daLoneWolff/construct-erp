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
                </flux:menu>
            </flux:dropdown>

            @can('manage')
                <flux:button icon="pencil-square" :href="route('projects.edit', $project)" wire:navigate>
                    {{ __('Edit project') }}
                </flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

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
