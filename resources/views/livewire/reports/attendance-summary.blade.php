<div class="space-y-5">
    <x-page-header title="{{ __('Attendance summary') }}" description="{{ __('Headcount by employee for the selected project and date range.') }}">
        <x-slot:actions>
            <flux:button icon="printer" :href="$printUrl" target="_blank">
                {{ __('Print') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <x-report-tabs active="attendance-summary" />

    <x-panel>
        <x-filter-bar>
            <flux:select wire:model.live="projectId" :label="__('Project')" class="w-full sm:max-w-sm">
                <flux:select.option value="">{{ __('Select a project') }}</flux:select.option>
                @foreach ($projects as $option)
                    <flux:select.option :value="$option->id">
                        {{ $option->code }} — {{ $option->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model.live="from" type="date" :label="__('From')" class="w-full sm:max-w-[11rem]" />
            <flux:input wire:model.live="to" type="date" :label="__('To')" class="w-full sm:max-w-[11rem]" />
        </x-filter-bar>

        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <x-stat-card label="Present" :value="$summary['present']" tone="green" />
            <x-stat-card label="Absent" :value="$summary['absent']" tone="red" />
            <x-stat-card label="Half day" :value="$summary['half_day']" tone="amber" />
            <x-stat-card label="Leave" :value="$summary['leave']" />
            <x-stat-card label="Overtime" :value="$summary['overtime_hours']" :decimals="1" :suffix="' h'" />
        </div>
    </x-panel>

    <x-panel flush>
        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Employee') }}</flux:table.column>
                    <flux:table.column>{{ __('Trade') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Present') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Absent') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Half day') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Leave') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Overtime (h)') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Days recorded') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($rows as $row)
                        <flux:table.row :key="$row['employee_code']">
                            <flux:table.cell>
                                <div class="font-medium">{{ $row['name'] }}</div>
                                <flux:text class="text-xs text-zinc-500">{{ $row['employee_code'] }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>{{ $row['trade'] }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ $row['present'] }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ $row['absent'] }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ $row['half_day'] }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ $row['leave'] }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums"><x-number :value="$row['overtime_hours']" :decimals="1" /></flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ $row['days'] }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8">
                                <x-empty-state
                                    variant="table"
                                    title="No attendance in this range"
                                    description="Choose another project or widen the date range."
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </x-panel>
</div>
