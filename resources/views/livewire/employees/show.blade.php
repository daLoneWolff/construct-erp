<div class="space-y-5">
    <x-page-header :title="$employee->name">
        <div class="flex flex-wrap items-center gap-2">
            <flux:badge color="zinc" size="sm" class="font-mono">{{ $employee->employee_code }}</flux:badge>
            <x-status-badge :tone="$employee->status === \App\Enums\EmployeeStatus::Active ? 'success' : 'neutral'">
                {{ str($employee->status->value)->title() }}
            </x-status-badge>
            @if ($employee->ssf_enrolled)
                <x-status-badge tone="success">{{ __('SSF enrolled') }}</x-status-badge>
            @endif
        </div>

        <div>
            {{ $employee->designation }} · {{ $employee->department }}
            @if ($employee->trade) · {{ $employee->trade }} @endif
        </div>

        <div>
            {{ __('Joined') }} <x-date :date="$employee->joined_at" />
            @if ($employee->phone) · {{ $employee->phone }} @endif
        </div>

        <x-slot:actions>
            @if ($canManage)
                <flux:button icon="pencil-square" :href="route('employees.edit', $employee)" wire:navigate>
                    {{ __('Edit employee') }}
                </flux:button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-panel flush>
        <div class="flex flex-wrap items-center gap-1 border-b border-border p-2">
            @foreach (['profile' => __('Profile'), 'assignments' => __('Project assignments'), 'attendance' => __('Attendance (30d)'), 'ssf' => __('SSF & allowance')] as $key => $label)
                <flux:button
                    size="sm"
                    :variant="$tab === $key ? 'filled' : 'ghost'"
                    wire:click="$set('tab', '{{ $key }}')"
                >
                    {{ $label }}
                </flux:button>
            @endforeach
        </div>

        <div class="p-6">
            @if ($tab === 'profile')
                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="space-y-3">
                        <flux:heading size="sm">{{ __('Employment') }}</flux:heading>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">{{ __('Type') }}</dt>
                                <dd>{{ str($employee->employment_type->value)->title() }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">{{ __('Daily wage') }}</dt>
                                <dd>
                                    @if ($employee->daily_wage_paisa !== null)
                                        <x-money :paisa="$employee->daily_wage_paisa" />
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">{{ __('Monthly basic') }}</dt>
                                <dd>
                                    @if ($employee->monthly_basic_paisa !== null)
                                        <x-money :paisa="$employee->monthly_basic_paisa" />
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">{{ __('PAN') }}</dt>
                                <dd class="font-mono">{{ $employee->pan ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="space-y-3">
                        <flux:heading size="sm">{{ __('Contact') }}</flux:heading>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">{{ __('Phone') }}</dt>
                                <dd>{{ $employee->phone ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">{{ __('Email') }}</dt>
                                <dd>{{ $employee->email ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">{{ __('SSF number') }}</dt>
                                <dd class="font-mono">{{ $employee->ssf_no ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">{{ __('Joined') }}</dt>
                                <dd><x-date :date="$employee->joined_at" /></dd>
                            </div>
                        </dl>
                    </div>
                </div>
            @endif

            @if ($tab === 'assignments')
                @if ($assignments->isEmpty())
                    <x-empty-state title="{{ __('No project assignments') }}" description="{{ __('This employee is not currently on a project team.') }}" />
                @else
                    <div class="overflow-x-auto">
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>{{ __('Project') }}</flux:table.column>
                                <flux:table.column>{{ __('Role on project') }}</flux:table.column>
                                <flux:table.column>{{ __('Assigned') }}</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach ($assignments as $project)
                                    <flux:table.row :key="$project->id">
                                        <flux:table.cell>
                                            <flux:link :href="route('projects.show', $project)" wire:navigate>
                                                {{ $project->code }} · {{ $project->name }}
                                            </flux:link>
                                        </flux:table.cell>
                                        <flux:table.cell>{{ $project->pivot->role_on_project }}</flux:table.cell>
                                        <flux:table.cell><x-date :date="$project->pivot->assigned_at" /></flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </div>
                @endif
            @endif

            @if ($tab === 'attendance')
                <div class="grid gap-4 sm:grid-cols-5">
                    <x-stat-card label="Present" :value="$attendanceSummary['present']" />
                    <x-stat-card label="Absent" :value="$attendanceSummary['absent']" />
                    <x-stat-card label="Half day" :value="$attendanceSummary['half_day']" />
                    <x-stat-card label="Leave" :value="$attendanceSummary['leave']" />
                    <x-stat-card label="Overtime (h)" :value="$attendanceSummary['overtime_hours']" :decimals="1" />
                </div>

                <div class="mt-6">
                    @if ($attendance->isEmpty())
                        <x-empty-state
                            :title="__('No attendance in the last :days days', ['days' => $attendanceWindowDays])"
                            :description="__('Attendance rows appear once the site grid is saved.')"
                        />
                    @else
                        <div class="overflow-x-auto">
                            <flux:table>
                                <flux:table.columns>
                                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                                    <flux:table.column align="end" class="text-right">{{ __('Overtime') }}</flux:table.column>
                                </flux:table.columns>
                                <flux:table.rows>
                                    @foreach ($attendance as $row)
                                        <flux:table.row :key="$row->id">
                                            <flux:table.cell><x-date :date="$row->date" /></flux:table.cell>
                                            <flux:table.cell>
                                                <x-status-badge :tone="match ($row->status) {
                                                    \App\Enums\AttendanceStatus::Present => 'success',
                                                    \App\Enums\AttendanceStatus::Absent => 'danger',
                                                    default => 'warning',
                                                }">
                                                    {{ str($row->status->value)->replace('_', ' ')->title() }}
                                                </x-status-badge>
                                            </flux:table.cell>
                                            <flux:table.cell class="text-right tabular-nums"><x-number :value="$row->overtime_hours" :decimals="1" /></flux:table.cell>
                                        </flux:table.row>
                                    @endforeach
                                </flux:table.rows>
                            </flux:table>
                        </div>
                    @endif
                </div>
            @endif

            @if ($tab === 'ssf')
                @if ($ssf === null)
                    <x-empty-state
                        title="{{ __('No monthly basic salary') }}"
                        description="{{ __('SSF contributions apply to monthly employees. Set a monthly basic to see the split.') }}"
                    />
                @else
                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-stat-card
                            :label="__('Employee contribution (:rate%)', ['rate' => $ssf['employee_rate_percent']])"
                            :paisa="$ssf['employee_paisa']"
                        />
                        <x-stat-card
                            :label="__('Employer contribution (:rate%)', ['rate' => $ssf['employer_rate_percent']])"
                            :paisa="$ssf['employer_paisa']"
                        />
                        <x-stat-card
                            :label="__('Total SSF deposit (:rate%)', ['rate' => $ssf['total_rate_percent']])"
                            :paisa="$ssf['total_paisa']"
                        />
                    </div>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <x-stat-card label="Festival allowance" :paisa="$festival" hint="Pro-rated by months worked" />
                        <x-stat-card
                            label="Monthly basic"
                            :paisa="$employee->monthly_basic_paisa"
                            hint="Base for SSF and festival allowance"
                        />
                    </div>
                @endif
            @endif
        </div>
    </x-panel>
</div>
