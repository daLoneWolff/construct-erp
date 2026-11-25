<div class="space-y-5">
    <x-page-header
        title="{{ __('Contractors') }}"
        description="{{ __('Contractors and payees, with agreed work, payments and outstanding balance.') }}"
    >
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" :href="route('contractors.create')" wire:navigate>
                {{ __('New contractor') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <x-panel flush>
        <x-filter-bar>
            <flux:input
                wire:model.live.debounce.300ms="search"
                :label="__('Search')"
                :placeholder="__('Name, phone or PAN')"
                icon="magnifying-glass"
                class="w-full sm:max-w-sm"
            />
        </x-filter-bar>

        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Contractor') }}</flux:table.column>
                    <flux:table.column>{{ __('Contact') }}</flux:table.column>
                    <flux:table.column>{{ __('PAN') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Tasks') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Agreed') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Paid') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Balance') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($rows as $row)
                        @php($contractor = $row['contractor'])
                        <flux:table.row :key="$contractor->id">
                            <flux:table.cell>
                                <flux:link :href="route('contractors.show', $contractor)" wire:navigate class="font-medium">
                                    {{ $contractor->name }}
                                </flux:link>
                                @if ($contractor->notes)
                                    <flux:text class="text-xs text-zinc-500">{{ $contractor->notes }}</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <div>{{ $contractor->phone ?: '—' }}</div>
                                @if ($contractor->address)
                                    <flux:text class="text-xs text-zinc-500">{{ $contractor->address }}</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="font-mono text-xs">{{ $contractor->pan ?: '—' }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ $row['job_count'] }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$row['agreed_paisa']" /></flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$row['paid_paisa']" /></flux:table.cell>
                            <flux:table.cell class="text-right font-semibold tabular-nums"><x-money :paisa="$row['balance_paisa']" /></flux:table.cell>
                            <flux:table.cell>
                                <x-status-badge :tone="$contractor->is_active ? 'success' : 'neutral'">
                                    {{ $contractor->is_active ? __('Active') : __('Inactive') }}
                                </x-status-badge>
                            </flux:table.cell>
                            <flux:table.cell class="text-right">
                                <div class="flex justify-end gap-1">
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="eye"
                                        aria-label="{{ __('View contractor') }}"
                                        :href="route('contractors.show', $contractor)"
                                        wire:navigate
                                    />
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="pencil-square"
                                        aria-label="{{ __('Edit contractor') }}"
                                        :href="route('contractors.edit', $contractor)"
                                        wire:navigate
                                    />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="9">
                                <x-empty-state
                                    variant="table"
                                    title="{{ __('No contractors found') }}"
                                    description="{{ __('Add a contractor, then assign tasks and record payments.') }}"
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
