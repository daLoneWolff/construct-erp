<div class="space-y-5">
    <x-page-header title="{{ __('Employees') }}" description="{{ __('Staff, site team and labour — SSF and wage records.') }}">
        <x-slot:actions>
            @if ($canManage)
                <flux:button variant="primary" icon="plus" :href="route('employees.create')" wire:navigate>
                    {{ __('Add employee') }}
                </flux:button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-panel flush>
        <x-filter-bar>
            <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search name, code or designation')" icon="magnifying-glass" class="w-full sm:max-w-sm" />
            <flux:select wire:model.live="trade" class="w-full sm:w-44">
                <flux:select.option value="">{{ __('All trades') }}</flux:select.option>
                @foreach ($trades as $item)
                    <flux:select.option :value="$item">{{ $item }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="status" class="w-full sm:w-40">
                <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
                <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
            </flux:select>
        </x-filter-bar>

        @if ($employees->isEmpty())
            <div class="p-5">
                <x-empty-state variant="table" title="{{ __('No employees found') }}" description="{{ __('Adjust the filters or add your first employee.') }}" />
            </div>
        @else
            <div class="overflow-x-auto border-t border-border">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Code') }}</flux:table.column>
                        <flux:table.column>{{ __('Name') }}</flux:table.column>
                        <flux:table.column>{{ __('Trade') }}</flux:table.column>
                        <flux:table.column>{{ __('Designation') }}</flux:table.column>
                        <flux:table.column>{{ __('SSF') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($employees as $employee)
                            <flux:table.row :key="$employee->id">
                                <flux:table.cell class="font-mono text-xs">
                                    <flux:link :href="route('employees.show', $employee)" wire:navigate>
                                        {{ $employee->employee_code }}
                                    </flux:link>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:link :href="route('employees.show', $employee)" wire:navigate class="font-medium">
                                        {{ $employee->name }}
                                    </flux:link>
                                    <flux:text class="text-xs text-zinc-500">{{ $employee->department }}</flux:text>
                                </flux:table.cell>
                                <flux:table.cell>{{ $employee->trade ?? '—' }}</flux:table.cell>
                                <flux:table.cell>{{ $employee->designation }}</flux:table.cell>
                                <flux:table.cell>
                                    <x-status-badge :tone="$employee->ssf_enrolled ? 'success' : 'neutral'">
                                        {{ $employee->ssf_enrolled ? __('Enrolled') : __('Not enrolled') }}
                                    </x-status-badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <x-status-badge :tone="$employee->status === \App\Enums\EmployeeStatus::Active ? 'success' : 'neutral'">
                                        {{ str($employee->status->value)->title() }}
                                    </x-status-badge>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <div class="border-t border-border px-5 py-4">
                {{ $employees->links() }}
            </div>
        @endif
    </x-panel>
</div>
