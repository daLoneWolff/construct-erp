<div class="space-y-5">
    <x-page-header title="{{ __('New running bill') }}">
        <div class="flex flex-wrap items-center gap-2">
            <flux:link :href="route('projects.running-bills', $project)" wire:navigate class="text-sm">
                {{ __('Back to running bills') }}
            </flux:link>
            <flux:badge color="zinc" size="sm">{{ $project->code }}</flux:badge>
        </div>
    </x-page-header>

    <form wire:submit="save" class="space-y-5">
        <x-panel title="{{ __('Bill details') }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="periodFrom" type="date" :label="__('Period from')" required />
                <flux:input wire:model="periodTo" type="date" :label="__('Period to')" required />
                <flux:input wire:model.live="workCertified" type="number" step="0.01" min="0" :label="__('Work certified (NPR)')" required />
                <flux:input wire:model.live="advanceAdjusted" type="number" step="0.01" min="0" :label="__('Advance adjusted (NPR)')" />
            </div>

            <flux:textarea wire:model="notes" :label="__('Notes')" rows="3" />

            <flux:callout icon="calculator" color="blue" class="!py-2">
                <flux:callout.text>
                    {{ __('Net payable is calculated as work certified minus advance adjusted. Both values are entered in NPR.') }}
                </flux:callout.text>
            </flux:callout>
        </x-panel>

        <x-panel title="{{ __('Totals') }}">
            <x-slot:actions>
                <flux:badge color="zinc" size="sm">{{ __('Live calculation') }}</flux:badge>
            </x-slot:actions>

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

            <flux:text class="mt-4 text-sm">
                {{ __('Formula') }}:
                <x-money :paisa="$workCertifiedPaisa" />
                − <x-money :paisa="$totals['advance_adjusted_paisa']" />
                = <x-money :paisa="$totals['net_payable_paisa']" />
            </flux:text>
        </x-panel>

        <x-form-actions>
            <flux:button variant="ghost" :href="route('projects.running-bills', $project)" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ __('Save draft') }}</flux:button>
        </x-form-actions>
    </form>
</div>
