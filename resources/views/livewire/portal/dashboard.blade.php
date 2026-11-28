<div class="space-y-5">
    @if (! $contractor)
        <x-empty-state
            title="{{ __('Contractor account not linked') }}"
            description="{{ __('Your login is not linked to a contractor record yet. Please contact your company admin.') }}"
        />
    @else
        <div class="space-y-1">
            <flux:heading size="xl">{{ __('Welcome, :name', ['name' => $contractor->name]) }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Here is your payment and job summary.') }}
            </flux:text>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            <x-stat-card label="{{ __('Total agreed') }}" :paisa="$summary['agreed_paisa']" />
            <x-stat-card label="{{ __('Total paid') }}" :paisa="$summary['paid_paisa']" />
            <x-stat-card label="{{ __('Remaining to take') }}" :paisa="$summary['balance_paisa']" />
        </div>

        <x-panel title="{{ __('Recent payments') }}" description="{{ __('Latest payments received from the company.') }}">
            @if ($recentPayments->isEmpty())
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No payments recorded yet.') }}</flux:text>
            @else
                <div class="space-y-3">
                    @foreach ($recentPayments as $payment)
                        <div class="flex items-start justify-between gap-3 rounded-xl border border-border bg-surface-subtle px-3 py-3">
                            <div class="min-w-0">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $payment->payment_no }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    <x-date :date="$payment->paid_on" />
                                    @if ($payment->project)
                                        · {{ $payment->project->name }}
                                    @endif
                                </div>
                                @if ($payment->job)
                                    <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $payment->job->title }}</div>
                                @endif
                            </div>
                            <div class="shrink-0 text-right font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">
                                <x-money :paisa="$payment->amount_paisa" />
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4">
                    <flux:button variant="ghost" size="sm" :href="route('portal.payments')" wire:navigate>
                        {{ __('View all payments') }}
                    </flux:button>
                </div>
            @endif
        </x-panel>

        <x-panel title="{{ __('Assigned jobs') }}" description="{{ __('Work assigned to you across projects.') }}">
            @if ($jobGroups->isEmpty())
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No jobs assigned yet.') }}</flux:text>
            @else
                <div class="space-y-4">
                    @foreach ($jobGroups as $group)
                        <div class="rounded-xl border border-border bg-surface-subtle p-4">
                            <div class="mb-3 font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $group['project']?->name ?? __('Unknown project') }}
                            </div>
                            <div class="space-y-2">
                                @foreach ($group['jobs'] as $row)
                                    <div class="flex items-start justify-between gap-3 text-sm">
                                        <div class="min-w-0">
                                            <div class="truncate font-medium text-zinc-800 dark:text-zinc-200">{{ $row['job']->title }}</div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ str($row['job']->status->value)->replace('_', ' ')->title() }}
                                            </div>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            <div class="tabular-nums text-zinc-900 dark:text-zinc-100"><x-money :paisa="$row['balance_paisa']" /></div>
                                            <div class="text-xs text-zinc-500">{{ __('Remaining') }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4">
                    <flux:button variant="ghost" size="sm" :href="route('portal.jobs')" wire:navigate>
                        {{ __('View all jobs') }}
                    </flux:button>
                </div>
            @endif
        </x-panel>
    @endif
</div>
