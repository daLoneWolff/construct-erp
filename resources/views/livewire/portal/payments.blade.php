<div class="space-y-5">
    @if (! $contractor)
        <x-empty-state
            title="{{ __('Contractor account not linked') }}"
            description="{{ __('Your login is not linked to a contractor record yet. Please contact your company admin.') }}"
        />
    @else
        <div class="space-y-1">
            <flux:heading size="xl">{{ __('My payments') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Payments you have received from the company.') }}
            </flux:text>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            <x-stat-card label="{{ __('Total agreed') }}" :paisa="$summary['agreed_paisa']" />
            <x-stat-card label="{{ __('Total paid') }}" :paisa="$summary['paid_paisa']" />
            <x-stat-card label="{{ __('Remaining to take') }}" :paisa="$summary['balance_paisa']" />
        </div>

        <x-panel title="{{ __('Payment history') }}" flush>
            @if ($payments->isEmpty())
                <div class="px-5 py-6">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No payments recorded yet.') }}</flux:text>
                </div>
            @else
                <div class="divide-y divide-border">
                    @foreach ($payments as $payment)
                        <div class="flex items-start justify-between gap-3 px-5 py-4">
                            <div class="min-w-0 space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $payment->payment_no }}</span>
                                    <flux:badge size="sm" color="zinc">{{ str($payment->method->value)->replace('_', ' ')->title() }}</flux:badge>
                                </div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                    <x-date :date="$payment->paid_on" />
                                </div>
                                <div class="text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ $payment->project?->name ?? __('General payment') }}
                                    @if ($payment->job)
                                        <span class="text-zinc-400">·</span> {{ $payment->job->title }}
                                    @endif
                                </div>
                                @if ($payment->reference_no)
                                    <div class="text-xs text-zinc-500">{{ __('Ref') }}: {{ $payment->reference_no }}</div>
                                @endif
                            </div>
                            <div class="shrink-0 text-right font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">
                                <x-money :paisa="$payment->amount_paisa" />
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-panel>
    @endif
</div>
