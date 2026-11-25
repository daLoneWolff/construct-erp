<div class="space-y-5">
    <x-page-header title="{{ __('Daily site reports') }}">
        <div class="flex flex-wrap items-center gap-2">
            <flux:badge color="zinc" size="sm" class="font-mono">{{ $project->code }}</flux:badge>
            <flux:link :href="route('projects.show', $project)" wire:navigate class="text-sm">
                {{ __('Back to project') }}
            </flux:link>
        </div>

        <div>{{ $project->name }} · {{ __('one report per day') }}</div>

        <x-slot:actions>
            @if ($canCreate)
                <flux:button
                    variant="primary"
                    icon="document-plus"
                    :href="route('projects.dsr.create', $project)"
                    wire:navigate
                >
                    {{ __('New site report') }}
                </flux:button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($reports->isEmpty())
        <x-empty-state
            title="{{ __('No site reports yet') }}"
            description="{{ __('Daily site reports capture manpower, equipment, material consumption and site photos.') }}"
        />
    @else
        <x-panel flush>
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Date') }}</flux:table.column>
                        <flux:table.column>{{ __('Submitted by') }}</flux:table.column>
                        <flux:table.column>{{ __('Weather') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('Manpower') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('Actions') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($reports as $report)
                            <flux:table.row :key="$report->id">
                                <flux:table.cell>
                                    <flux:link :href="route('dsr.show', $report)" wire:navigate class="font-medium">
                                        <x-date :date="$report->report_date" />
                                    </flux:link>
                                </flux:table.cell>
                                <flux:table.cell>{{ $report->submittedBy?->name }}</flux:table.cell>
                                <flux:table.cell>{{ $report->weather }}</flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums">{{ $report->manpower_total }}</flux:table.cell>
                                <flux:table.cell>
                                    <x-status-badge :tone="match ($report->status) {
                                        \App\Enums\DsrStatus::Approved => 'success',
                                        \App\Enums\DsrStatus::Rejected => 'danger',
                                        default => 'warning',
                                    }">
                                        {{ str($report->status->value)->title() }}
                                    </x-status-badge>
                                </flux:table.cell>
                                <flux:table.cell class="text-right">
                                    <div class="flex justify-end gap-1">
                                        <flux:button
                                            size="xs"
                                            variant="ghost"
                                            :href="route('dsr.show', $report)"
                                            wire:navigate
                                        >
                                            {{ __('View') }}
                                        </flux:button>

                                        @if ($report->status === \App\Enums\DsrStatus::Submitted)
                                            @can('approve')
                                                <flux:button size="xs" variant="primary" wire:click="approve({{ $report->id }})">
                                                    {{ __('Approve') }}
                                                </flux:button>
                                                <flux:button
                                                    size="xs"
                                                    variant="ghost"
                                                    wire:click="reject({{ $report->id }})"
                                                    wire:confirm="{{ __('Reject this site report?') }}"
                                                >
                                                    {{ __('Reject') }}
                                                </flux:button>
                                            @endcan
                                        @endif
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            @if ($reports->hasPages())
                <div class="border-t border-border px-5 py-3">
                    {{ $reports->links() }}
                </div>
            @endif
        </x-panel>
    @endif
</div>
