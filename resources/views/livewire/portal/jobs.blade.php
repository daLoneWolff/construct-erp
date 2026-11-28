<div class="space-y-5">
    @if (! $contractor)
        <x-empty-state
            title="{{ __('Contractor account not linked') }}"
            description="{{ __('Your login is not linked to a contractor record yet. Please contact your company admin.') }}"
        />
    @else
        <div class="space-y-1">
            <flux:heading size="xl">{{ __('My jobs') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Jobs assigned to you, grouped by project.') }}
            </flux:text>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            <x-stat-card label="{{ __('Total agreed') }}" :paisa="$summary['agreed_paisa']" />
            <x-stat-card label="{{ __('Total paid') }}" :paisa="$summary['paid_paisa']" />
            <x-stat-card label="{{ __('Remaining to take') }}" :paisa="$summary['balance_paisa']" />
        </div>

        @if ($jobGroups->isEmpty())
            <x-panel>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No jobs assigned yet.') }}</flux:text>
            </x-panel>
        @else
            <div class="space-y-4">
                @foreach ($jobGroups as $group)
                    <x-panel :title="$group['project']?->name ?? __('Unknown project')" flush>
                        <div class="divide-y divide-border">
                            @foreach ($group['jobs'] as $row)
                                @php($job = $row['job'])
                                <div class="space-y-3 px-5 py-4">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $job->title }}</div>
                                            @if ($job->milestone)
                                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $job->milestone->title }}</div>
                                            @endif
                                        </div>
                                        <flux:badge
                                            size="sm"
                                            :color="match ($job->status->value) {
                                                'done', 'completed' => 'green',
                                                'in_progress' => 'blue',
                                                'cancelled' => 'red',
                                                default => 'zinc',
                                            }"
                                        >
                                            {{ str($job->status->value)->replace('_', ' ')->title() }}
                                        </flux:badge>
                                    </div>

                                    <div class="grid grid-cols-3 gap-3 text-sm">
                                        <div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Agreed') }}</div>
                                            <div class="font-medium tabular-nums"><x-money :paisa="$job->agreed_amount_paisa" /></div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Paid') }}</div>
                                            <div class="font-medium tabular-nums"><x-money :paisa="$row['paid_paisa']" /></div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Remaining') }}</div>
                                            <div class="font-medium tabular-nums"><x-money :paisa="$row['balance_paisa']" /></div>
                                        </div>
                                    </div>

                                    @if ($job->starts_on || $job->due_on)
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                            @if ($job->starts_on)
                                                {{ __('Start') }}: <x-date :date="$job->starts_on" />
                                            @endif
                                            @if ($job->starts_on && $job->due_on)
                                                <span class="mx-1">·</span>
                                            @endif
                                            @if ($job->due_on)
                                                {{ __('Due') }}: <x-date :date="$job->due_on" />
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </x-panel>
                @endforeach
            </div>
        @endif
    @endif
</div>
