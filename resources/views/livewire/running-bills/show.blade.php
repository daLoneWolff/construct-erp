<div class="space-y-5">
    <x-page-header :title="$project->name">
        <div class="flex flex-wrap items-center gap-2">
            <flux:link :href="route('projects.running-bills', $project)" wire:navigate class="text-sm">
                {{ __('Back to running bills') }}
            </flux:link>
            <flux:badge color="zinc" class="font-mono">{{ $bill->code }}</flux:badge>
            <flux:badge
                size="sm"
                :color="match ($bill->status) {
                    \App\Enums\RunningBillStatus::Certified => 'lime',
                    \App\Enums\RunningBillStatus::Paid => 'blue',
                    default => 'amber',
                }"
            >
                {{ str($bill->status->value)->title() }}
            </flux:badge>
        </div>
        <div>
            <x-date :date="$bill->period_from" /> — <x-date :date="$bill->period_to" />
        </div>

        <x-slot:actions>
            @if (Route::has('running-bills.print'))
                <flux:button icon="printer" :href="route('running-bills.print', $bill)">
                    {{ __('Print') }}
                </flux:button>
            @endif

            @can('certify')
                @if ($canCertify)
                    <flux:button
                        icon="check-circle"
                        variant="primary"
                        wire:click="certify"
                        wire:confirm="Certify this bill?"
                    >
                        {{ __('Certify') }}
                    </flux:button>
                @endif
            @endcan
        </x-slot:actions>
    </x-page-header>

    @error('bill')
        <flux:callout variant="warning" icon="exclamation-triangle">
            {{ $message }}
        </flux:callout>
    @enderror

    <x-panel title="{{ __('Bill calculation') }}">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                <flux:text class="text-xs text-zinc-500">{{ __('Work certified') }}</flux:text>
                <div class="text-lg font-semibold tabular-nums"><x-money :paisa="$workCertifiedPaisa" /></div>
            </div>
            <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                <flux:text class="text-xs text-zinc-500">{{ __('Advance adjusted') }}</flux:text>
                <div class="text-lg font-semibold tabular-nums"><x-money :paisa="$totals['advance_adjusted_paisa']" /></div>
            </div>
            <div class="rounded-lg border border-zinc-900 bg-zinc-900 p-3 text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900">
                <flux:text class="text-xs opacity-70">{{ __('Net payable') }}</flux:text>
                <div class="text-lg font-semibold tabular-nums"><x-money :paisa="$totals['net_payable_paisa']" /></div>
            </div>
        </div>

        <flux:callout variant="info" icon="calculator" class="mt-4">
            <x-money :paisa="$workCertifiedPaisa" />
            − <x-money :paisa="$totals['advance_adjusted_paisa']" />
            = <x-money :paisa="$totals['net_payable_paisa']" />
        </flux:callout>

        <flux:text class="mt-3 text-sm text-zinc-500">
            {{ __('In words') }}: {{ $amountInWords }}
        </flux:text>
    </x-panel>

    <div class="grid gap-5 lg:grid-cols-2">
        <x-panel title="{{ __('Bill details') }}">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Project') }}</dt>
                    <dd class="font-medium">{{ $project->code }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Bill number') }}</dt>
                    <dd class="font-medium">#{{ $bill->bill_number }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Certified date') }}</dt>
                    <dd class="font-medium"><x-date :date="$bill->certified_date" /></dd>
                </div>
            </dl>
        </x-panel>

        <x-panel title="{{ __('Notes') }}">
            <flux:text class="text-sm">{{ $bill->notes ?: __('—') }}</flux:text>
        </x-panel>
    </div>
</div>
