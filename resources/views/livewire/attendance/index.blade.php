<div class="space-y-5">
    <x-page-header title="{{ __('Attendance') }}" description="{{ __('One row per employee per day. Saves update today’s grid in place.') }}">
        <x-slot:actions>
            @if ($canEdit)
                <flux:button variant="primary" icon="check" wire:click="save">
                    {{ __('Save all') }}
                </flux:button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-panel flush>
        <x-filter-bar>
            <flux:input wire:model.live="date" type="date" :label="__('Date')" max="{{ today()->toDateString() }}" />
            <flux:select wire:model.live="projectId" :label="__('Project')" class="w-full sm:min-w-64">
                @foreach ($projects as $project)
                    <flux:select.option :value="$project->id">
                        {{ $project->code }} · {{ $project->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <x-slot:actions>
                <flux:text class="text-sm text-zinc-500">
                    {{ __(':count employees on this grid', ['count' => $employees->count()]) }}
                </flux:text>
            </x-slot:actions>
        </x-filter-bar>

        @if ($employees->isEmpty())
            <div class="p-5">
                <x-empty-state
                    variant="table"
                    title="{{ __('No employees on this project') }}"
                    description="{{ __('Assign employees to the project team before recording attendance.') }}"
                />
            </div>
        @else
            <div class="overflow-x-auto border-t border-border">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Employee') }}</flux:table.column>
                        <flux:table.column>{{ __('Trade') }}</flux:table.column>
                        <flux:table.column class="w-1/2">{{ __('Status') }}</flux:table.column>
                        <flux:table.column align="end" class="w-28 text-right">{{ __('Overtime (h)') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($employees as $employee)
                            <flux:table.row :key="$employee->id">
                                <flux:table.cell>
                                    <div class="font-medium">{{ $employee->name }}</div>
                                    <flux:text class="text-xs text-zinc-500">{{ $employee->employee_code }}</flux:text>
                                </flux:table.cell>

                                <flux:table.cell class="text-sm">{{ $employee->trade ?? $employee->designation }}</flux:table.cell>

                                <flux:table.cell>
                                    @if ($canEdit)
                                        <flux:radio.group wire:model="statuses.{{ $employee->id }}" variant="segmented" size="sm">
                                            @foreach ($statusOptions as $status)
                                                <flux:radio
                                                    :value="$status->value"
                                                    :label="str($status->value)->replace('_', ' ')->title()"
                                                />
                                            @endforeach
                                        </flux:radio.group>
                                        <x-field-error :message="$errors->first('statuses.'.$employee->id)" class="mt-1" />
                                    @else
                                        <x-status-badge :tone="match ($statuses[$employee->id] ?? 'present') {
                                            'present' => 'success',
                                            'absent' => 'danger',
                                            'half_day' => 'warning',
                                            default => 'info',
                                        }">
                                            {{ str($statuses[$employee->id] ?? 'present')->replace('_', ' ')->title() }}
                                        </x-status-badge>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell class="text-right">
                                    @if ($canEdit)
                                        <flux:input
                                            wire:model="overtime.{{ $employee->id }}"
                                            type="number"
                                            step="0.5"
                                            min="0"
                                            max="24"
                                            size="sm"
                                            class="text-right"
                                            aria-label="{{ __('Overtime hours for :employee', ['employee' => $employee->name]) }}"
                                        />
                                    @else
                                        <span class="tabular-nums"><x-number :value="$overtime[$employee->id]" :decimals="1" /></span>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    </x-panel>
</div>
