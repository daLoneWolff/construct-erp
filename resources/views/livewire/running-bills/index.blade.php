<div class="space-y-5">
    <x-page-header title="{{ __('Running bills') }}">
        <div class="flex flex-wrap items-center gap-2">
            <flux:link :href="route('projects.show', $project)" wire:navigate class="text-sm">{{ __('Back to project') }}</flux:link>
            <flux:badge color="zinc" size="sm">{{ $project->code }}</flux:badge>
        </div>
        <div>{{ $project->name }}</div>

        <x-slot:actions>
            @can('certify')
                @if (Route::has('projects.running-bills.create'))
                    <flux:button
                        icon="plus"
                        variant="primary"
                        :href="route('projects.running-bills.create', $project)"
                        wire:navigate
                    >
                        {{ __('Create bill') }}
                    </flux:button>
                @endif
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-stat-card label="Certified work" :paisa="$summary['certified_paisa']" />
        <x-stat-card label="Advance available" :paisa="$summary['advance_available_paisa']" />
    </div>

    <x-panel flush>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Bill') }}</flux:table.column>
                    <flux:table.column>{{ __('Period') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Work certified') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Advance adjusted') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Net payable') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($bills as $bill)
                        <flux:table.row :key="$bill->id">
                            <flux:table.cell>
                                <flux:link :href="route('running-bills.show', $bill)" wire:navigate class="font-mono">
                                    {{ $bill->code }}
                                </flux:link>
                                <flux:text class="block text-xs text-zinc-500">#{{ $bill->bill_number }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <x-date :date="$bill->period_from" /> — <x-date :date="$bill->period_to" />
                            </flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$bill->work_certified_paisa" /></flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$bill->advance_adjusted_paisa" /></flux:table.cell>
                            <flux:table.cell class="text-right font-semibold tabular-nums"><x-money :paisa="$bill->net_payable_paisa" /></flux:table.cell>
                            <flux:table.cell>
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
                            </flux:table.cell>
                            <flux:table.cell class="text-right">
                                @if (Route::has('running-bills.print'))
                                    <flux:button
                                        size="xs"
                                        variant="ghost"
                                        icon="printer"
                                        :href="route('running-bills.print', $bill)"
                                    >
                                        {{ __('Print') }}
                                    </flux:button>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7">
                                <x-empty-state
                                    variant="table"
                                    title="{{ __('No running bills yet') }}"
                                    description="{{ __('Create the first bill for this project.') }}"
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        @if ($bills->hasPages())
            <div class="border-t border-border px-5 py-3">
                {{ $bills->links() }}
            </div>
        @endif
    </x-panel>
</div>
