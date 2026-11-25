<div class="space-y-5">
    <x-page-header
        title="{{ __('Material issues') }}"
        description="{{ __('Issues reduce the stock balance at the source warehouse.') }}"
    >
        <x-slot:actions>
            @can('receive')
                @if (Route::has('issues.create'))
                    <flux:button icon="plus" variant="primary" :href="route('issues.create')" wire:navigate>
                        {{ __('Create issue') }}
                    </flux:button>
                @endif
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-panel flush>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Issue') }}</flux:table.column>
                    <flux:table.column>{{ __('Project') }}</flux:table.column>
                    <flux:table.column>{{ __('Warehouse') }}</flux:table.column>
                    <flux:table.column>{{ __('Issued to') }}</flux:table.column>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Lines') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($issues as $issue)
                        <flux:table.row :key="$issue->id">
                            <flux:table.cell class="font-mono font-medium">{{ $issue->code }}</flux:table.cell>
                            <flux:table.cell>{{ $issue->project?->code }}</flux:table.cell>
                            <flux:table.cell>{{ $issue->warehouse?->name }}</flux:table.cell>
                            <flux:table.cell>{{ $issue->issuedTo?->name }}</flux:table.cell>
                            <flux:table.cell><x-date :date="$issue->issued_date" /></flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ $issue->items_count }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6">
                                <x-empty-state variant="table" title="{{ __('No issues found') }}" description="{{ __('Material issues will appear here once stock is issued to projects.') }}" />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        @if ($issues->hasPages())
            <div class="border-t border-border px-5 py-3">
                {{ $issues->links() }}
            </div>
        @endif
    </x-panel>
</div>
