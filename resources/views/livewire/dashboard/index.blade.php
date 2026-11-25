<div class="space-y-5">
    <x-page-header title="{{ __('Financial overview') }}">
        {{ $companyRecord?->name ?? config('app.name') }} ·
        <x-date :date="today()" />

        <x-slot:actions>
            <flux:button variant="primary" icon="banknotes" :href="route('payments.index')" wire:navigate>
                {{ __('Review payments') }}
            </flux:button>
            <flux:button icon="folder" :href="route('projects.index')" wire:navigate>
                {{ __('Projects') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    @if ($isEmptyWorkspace)
        <x-empty-state
            title="{{ __('Your workspace is ready') }}"
            description="{{ __('No projects, contractors or payments have been recorded for this company yet. Start with the first record you actually need.') }}"
        >
            <x-slot:icon>
                <div class="flex size-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-300">
                    <flux:icon.building-office-2 class="size-7" />
                </div>
            </x-slot:icon>

            <x-slot:actions>
                <flux:button variant="primary" icon="folder" :href="route('projects.create')" wire:navigate>
                    {{ __('Create your first project') }}
                </flux:button>
                <flux:button icon="users" :href="route('contractors.create')" wire:navigate>
                    {{ __('Add your first contractor') }}
                </flux:button>
                <flux:button icon="banknotes" :href="route('payments.index')" wire:navigate>
                    {{ __('Record your first payment') }}
                </flux:button>
            </x-slot:actions>
        </x-empty-state>
    @endif

    <x-panel title="{{ __('Money position') }}" description="{{ __('The current contractor position across this company.') }}">
        <div class="grid divide-y divide-border sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            <div class="min-w-0 space-y-1 px-1 py-2 sm:px-5 sm:py-1">
                <flux:text class="text-xs font-medium tracking-wide text-zinc-500 uppercase dark:text-zinc-400">
                    {{ __('Client advances received') }}
                </flux:text>
                <div class="min-w-0 max-w-full break-words text-xl font-semibold leading-tight tabular-nums [overflow-wrap:anywhere]">
                    <x-money :paisa="$clientAdvancesReceivedPaisa" />
                </div>
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Recorded project advances') }}
                </flux:text>
            </div>

            <div class="min-w-0 space-y-1 px-1 py-4 sm:px-5 sm:py-1">
                <flux:text class="text-xs font-medium tracking-wide text-zinc-500 uppercase dark:text-zinc-400">
                    {{ __('Agreed task value') }}
                </flux:text>
                <div class="min-w-0 max-w-full break-words text-xl font-semibold leading-tight tabular-nums [overflow-wrap:anywhere]">
                    <x-money :paisa="$paymentSummary['agreed_paisa']" />
                </div>
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Across all tasks') }}
                </flux:text>
            </div>

            <div class="min-w-0 space-y-1 rounded-xl bg-warning/10 px-3 py-4 sm:-my-3 sm:px-5">
                <flux:text class="text-xs font-medium tracking-wide text-warning uppercase">
                    {{ __('Still owed to contractors') }}
                </flux:text>
                <div class="min-w-0 max-w-full break-words text-xl font-semibold leading-tight tracking-tight tabular-nums text-warning [overflow-wrap:anywhere] sm:text-2xl">
                    <x-money :paisa="$paymentSummary['balance_paisa']" />
                </div>
                <flux:text class="text-xs text-warning/80">
                    {{ __('Agreed work minus payments made') }}
                </flux:text>
            </div>
        </div>
    </x-panel>

    <div class="grid min-w-0 gap-5">
        <x-panel title="{{ __('Projects by outstanding balance') }}" flush>
            <x-slot:actions>
                <flux:button size="sm" variant="ghost" :href="route('projects.index')" wire:navigate>
                    {{ __('View all') }}
                </flux:button>
            </x-slot:actions>

            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Project') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('Agreed') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('Paid') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('Outstanding') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($projectRows as $row)
                            <flux:table.row :key="$row['project']->id">
                                <flux:table.cell>
                                    <flux:link :href="route('projects.payments', $row['project'])" wire:navigate class="font-medium">
                                        {{ $row['project']->name }}
                                    </flux:link>
                                    <flux:text class="text-xs text-zinc-500">
                                        {{ $row['project']->code }}
                                    </flux:text>
                                </flux:table.cell>
                                <flux:table.cell class="text-right"><x-money :paisa="$row['agreed_paisa']" /></flux:table.cell>
                                <flux:table.cell class="text-right"><x-money :paisa="$row['paid_paisa']" /></flux:table.cell>
                                <flux:table.cell class="text-right font-semibold tabular-nums"><x-money :paisa="$row['balance_paisa']" /></flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4">
                                    <x-empty-state
                                        variant="table"
                                        title="{{ __('No projects yet') }}"
                                        description="{{ __('Create a project to begin tracking contractor jobs and payments.') }}"
                                        cta="{{ __('Create project') }}"
                                        :href="route('projects.create')"
                                    />
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </x-panel>

        <x-panel title="{{ __('Contractor balances') }}" flush>
            <x-slot:actions>
                <flux:button size="sm" variant="ghost" :href="route('contractors.index')" wire:navigate>
                    {{ __('View all') }}
                </flux:button>
            </x-slot:actions>

            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Contractor') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('Agreed') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('Outstanding') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($contractorRows as $row)
                            <flux:table.row :key="$row['contractor']->id">
                                <flux:table.cell>
                                    <flux:link :href="route('contractors.show', $row['contractor'])" wire:navigate class="font-medium">
                                        {{ $row['contractor']->name }}
                                    </flux:link>
                                </flux:table.cell>
                                <flux:table.cell class="text-right"><x-money :paisa="$row['agreed_paisa']" /></flux:table.cell>
                                <flux:table.cell class="text-right font-semibold tabular-nums"><x-money :paisa="$row['balance_paisa']" /></flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="3">
                                    <x-empty-state
                                        variant="table"
                                        title="{{ __('No contractors yet') }}"
                                        description="{{ __('Add a contractor, then assign tasks to track balances.') }}"
                                        cta="{{ __('Add contractor') }}"
                                        :href="route('contractors.create')"
                                    />
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </x-panel>
    </div>
</div>
