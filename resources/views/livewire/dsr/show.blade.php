<div class="space-y-5">
    <x-page-header :title="__('Site report')">
        <div class="flex flex-wrap items-center gap-2">
            <flux:badge color="zinc" size="sm" class="font-mono">{{ $report->project->code }}</flux:badge>
            <flux:link :href="route('projects.dsr', $report->project)" wire:navigate class="text-sm">
                {{ __('Back to site reports') }}
            </flux:link>
        </div>

        <div class="font-medium text-zinc-700 dark:text-zinc-200">
            <x-date :date="$report->report_date" />
        </div>

        <div>
            {{ __('Submitted by') }} {{ $report->submittedBy?->name }} ·
            {{ $report->weather }} ·
            {{ __('Manpower') }} {{ $report->manpower_total }}
        </div>

        <x-slot:actions>
            <x-status-badge :tone="match ($report->status) {
                \App\Enums\DsrStatus::Approved => 'success',
                \App\Enums\DsrStatus::Rejected => 'danger',
                default => 'warning',
            }">
                {{ str($report->status->value)->title() }}
            </x-status-badge>

            @if ($report->status === \App\Enums\DsrStatus::Submitted)
                @can('approve')
                    <flux:button variant="primary" icon="check" wire:click="approve">
                        {{ __('Approve') }}
                    </flux:button>
                    <flux:button
                        variant="ghost"
                        icon="x-mark"
                        wire:click="reject"
                        wire:confirm="{{ __('Reject this site report?') }}"
                    >
                        {{ __('Reject') }}
                    </flux:button>
                @endcan
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($report->status !== \App\Enums\DsrStatus::Submitted && $report->approved_at)
        <flux:text class="text-sm text-zinc-500">
            {{ $report->status === \App\Enums\DsrStatus::Approved ? __('Approved') : __('Rejected') }}
            {{ __('by') }} {{ $report->approvedBy?->name }} {{ __('on') }}
            <x-date :date="$report->approved_at" :time="true" />
        </flux:text>
    @endif

    <div class="grid gap-5 lg:grid-cols-2">
        <x-panel title="{{ __('Manpower') }}" flush>
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Trade') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('Count') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('Hours') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($report->manpower as $row)
                            <flux:table.row :key="$row->id">
                                <flux:table.cell>{{ $row->trade }}</flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums">{{ $row->count }}</flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums"><x-number :value="$row->hours" :decimals="1" /></flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="3">
                                    <x-empty-state variant="table" title="{{ __('No manpower recorded.') }}" />
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </x-panel>

        <x-panel title="{{ __('Equipment used') }}" flush>
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Equipment') }}</flux:table.column>
                        <flux:table.column align="end" class="text-right">{{ __('Hours') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($report->equipment as $row)
                            <flux:table.row :key="$row->id">
                                <flux:table.cell>{{ $row->equipment?->name }}</flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums"><x-number :value="$row->hours" :decimals="1" /></flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="2">
                                    <x-empty-state variant="table" title="{{ __('No equipment recorded.') }}" />
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </x-panel>
    </div>

    <x-panel title="{{ __('Materials consumed') }}" flush>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('BOQ item') }}</flux:table.column>
                    <flux:table.column>{{ __('Description') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Quantity') }}</flux:table.column>
                    <flux:table.column>{{ __('Unit') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($report->materials as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell class="font-mono text-xs">{{ $row->boqItem?->code }}</flux:table.cell>
                            <flux:table.cell>{{ $row->description }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-number :value="$row->quantity" :decimals="3" /></flux:table.cell>
                            <flux:table.cell>{{ $row->unit }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4">
                                <x-empty-state variant="table" title="{{ __('No material consumption recorded.') }}" />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </x-panel>

    <div class="grid gap-5 lg:grid-cols-2">
        <x-panel title="{{ __('Remarks') }}">
            <flux:text>{{ $report->remarks ?: __('No remarks.') }}</flux:text>
        </x-panel>

        <x-panel title="{{ __('Site photos') }}">
            @forelse ($report->photos as $photo)
                <div class="flex items-center justify-between gap-3 rounded-lg bg-surface-subtle px-3 py-2 text-sm {{ !$loop->last ? 'mb-2' : '' }}">
                    <div class="flex items-center gap-2 min-w-0">
                        <flux:icon.photo class="size-4 shrink-0 text-zinc-400" />
                        <span class="truncate">{{ $photo->filename }}</span>
                    </div>
                    <div class="flex shrink-0 items-center gap-3 text-xs text-zinc-500">
                        <span>{{ $photo->size_label }}</span>
                        <span class="font-mono">{{ $photo->path_mock }}</span>
                    </div>
                </div>
            @empty
                <flux:text>{{ __('No photos attached.') }}</flux:text>
            @endforelse
        </x-panel>
    </div>
</div>
