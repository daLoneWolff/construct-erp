<div class="space-y-5">
    <x-page-header title="{{ __('Milestones/Tasks') }}">
        <div class="flex flex-wrap items-center gap-2">
            <flux:link :href="route('projects.show', $project)" wire:navigate class="text-sm">
                {{ __('Back to project') }}
            </flux:link>
            <flux:badge color="zinc" size="sm" class="font-mono">{{ $project->code }}</flux:badge>
        </div>
        <div class="space-y-2">
            <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                <span>
                    {{ $project->name }} · {{ __('Task completion') }}:
                    <x-number :value="$progressPct" :decimals="2" suffix="%" />
                    <span class="text-zinc-500">({{ $doneTasks }}/{{ $totalTasks }})</span>
                </span>
            </div>
            <x-progress-bar :pct="$progressPct" />
        </div>

        <x-slot:actions>
            @can('manage')
                <flux:button variant="primary" icon="plus" wire:click="createMilestone">
                    {{ __('Add milestone') }}
                </flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="flex min-w-0 gap-4 overflow-x-auto pb-2">
        @foreach ($columns as $column)
            @php
                $items = $milestones->filter(function ($m) use ($column) {
                    if ($column === \App\Enums\MilestoneStatus::Completed) return $m->status === $column;
                    if ($column === \App\Enums\MilestoneStatus::InProgress) return $m->status === $column;
                    return $m->status === $column;
                });
                $color = match ($column->value) {
                    'pending' => 'zinc',
                    'in_progress' => 'blue',
                    'completed' => 'lime',
                    default => 'zinc',
                };
            @endphp

            <div class="min-w-80 flex-1 space-y-3" wire:key="column-{{ $column->value }}">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <flux:badge size="sm" :color="$color">{{ str($column->value)->replace('_', ' ')->title() }}</flux:badge>
                        <span class="text-xs text-zinc-500">{{ $items->count() }}</span>
                    </div>
                </div>

                @forelse ($items as $milestone)
                    @php
                        $mTasks = $milestone->tasks;
                        $mDone = $mTasks->where('status', \App\Enums\JobStatus::Done)->count();
                        $mTotal = $mTasks->count();
                        $overdue = $milestone->isOverdue();
                    @endphp
                    <x-panel class="space-y-3" wire:key="milestone-{{ $milestone->id }}">
                        <button type="button" wire:click="toggle({{ $milestone->id }})" class="flex w-full items-start justify-between gap-2 text-left">
                            <div>
                                <div class="font-medium">{{ $milestone->title }}</div>
                                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                                    <span><x-date :date="$milestone->target_date" /></span>
                                    <span>{{ $mDone }}/{{ $mTotal }} {{ __('done') }}</span>
                                    @if ($overdue)
                                        <flux:badge size="sm" color="red">{{ __('Overdue') }}</flux:badge>
                                    @endif
                                </div>
                            </div>
                            <flux:icon.chevron-down class="mt-0.5 size-5 shrink-0 text-zinc-400 transition {{ $expandedId === $milestone->id ? 'rotate-180' : '' }}" />
                        </button>

                        @if ($expandedId === $milestone->id)
                            <div class="space-y-3 border-t border-border pt-3">
                                @if ($milestone->description)
                                    <flux:text class="text-xs">{{ $milestone->description }}</flux:text>
                                @endif

                                <div class="flex flex-wrap gap-2">
                                    @can('manage')
                                        @if ($milestone->status === \App\Enums\MilestoneStatus::Pending)
                                            <flux:button size="xs" variant="primary" wire:click="startMilestone({{ $milestone->id }})">{{ __('Start') }}</flux:button>
                                            <flux:button size="xs" variant="ghost" wire:click="createTask({{ $milestone->id }})">{{ __('Add task') }}</flux:button>
                                            <flux:button size="xs" variant="ghost" wire:click="editMilestone({{ $milestone->id }})">{{ __('Edit') }}</flux:button>
                                            <flux:button size="xs" variant="ghost" wire:click="deleteMilestone({{ $milestone->id }})" wire:confirm="{{ __('Delete this milestone?') }}">{{ __('Delete') }}</flux:button>
                                        @elseif ($milestone->status === \App\Enums\MilestoneStatus::InProgress)
                                            <flux:button size="xs" variant="primary" wire:click="completeMilestone({{ $milestone->id }})">{{ __('Complete') }}</flux:button>
                                            <flux:button size="xs" variant="ghost" wire:click="reopenMilestone({{ $milestone->id }})" wire:confirm="{{ __('Reopen to pending? This resets all task checkboxes and is allowed only when no contractor payments exist on milestone tasks.') }}">{{ __('Reopen to pending') }}</flux:button>
                                        @else
                                            <flux:button size="xs" variant="ghost" wire:click="reopenMilestone({{ $milestone->id }})" wire:confirm="{{ __('Reopen to pending? This resets all task checkboxes and is allowed only when no contractor payments exist on milestone tasks.') }}">{{ __('Reopen to pending') }}</flux:button>
                                        @endif
                                    @endcan
                                </div>

                                <div class="space-y-2">
                                    @forelse ($mTasks as $task)
                                        @php $isPayable = $task->contractor_id !== null; @endphp
                                        <div class="rounded-lg border border-border p-3" wire:key="task-{{ $task->id }}">
                                            <div class="flex items-start gap-3">
                                                <div class="pt-0.5">
                                                    @if ($milestone->status === \App\Enums\MilestoneStatus::InProgress)
                                                        <input type="checkbox" class="size-4 rounded border-zinc-300" @checked($task->status === \App\Enums\JobStatus::Done) wire:click="toggleTaskDone({{ $task->id }})" />
                                                    @else
                                                        <input type="checkbox" class="size-4 rounded border-zinc-300" @checked($task->status === \App\Enums\JobStatus::Done) disabled />
                                                    @endif
                                                </div>
                                                <div class="min-w-0 flex-1 space-y-1">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="text-sm font-medium {{ $task->status === \App\Enums\JobStatus::Done ? 'line-through text-zinc-400' : '' }}">{{ $task->title }}</span>
                                                        @if ($isPayable)
                                                            <flux:badge size="sm" color="blue">{{ __('Contractor') }}</flux:badge>
                                                        @endif
                                                    </div>
                                                    <div class="text-xs text-zinc-500">
                                                        @if ($isPayable)
                                                            {{ $task->contractor?->name ?? __('Contractor') }} · {{ __('Agreed') }} <x-money :paisa="$task->agreed_amount_paisa" /> · {{ __('Paid') }} <x-money :paisa="$task->paid_amount_paisa" /> · {{ __('Balance') }} <x-money :paisa="$task->balance_paisa" />
                                                        @elseif ($task->assignee)
                                                            {{ $task->assignee->name }}
                                                        @else
                                                            {{ __('Unassigned') }}
                                                        @endif
                                                        @if ($task->due_on)
                                                            · {{ __('Due') }} <x-date :date="$task->due_on" />
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="flex shrink-0 gap-1">
                                                    @can('manage')
                                                        @if ($milestone->status === \App\Enums\MilestoneStatus::Pending)
                                                            <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="editTask({{ $task->id }})" />
                                                            <flux:button size="xs" variant="ghost" icon="trash" wire:click="deleteTask({{ $task->id }})" wire:confirm="{{ __('Delete this task?') }}" />
                                                        @endif
                                                    @endcan
                                                    @can('manage')
                                                        @if ($isPayable && $task->balance_paisa > 0)
                                                            <flux:button size="xs" variant="primary" icon="banknotes" :href="route('jobs.payments.create', $task)" wire:navigate>{{ __('Pay') }}</flux:button>
                                                        @endif
                                                    @endcan
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <x-empty-state variant="table" title="{{ __('No tasks yet') }}" description="{{ __('Add tasks while the milestone is pending.') }}" />
                                    @endforelse
                                </div>
                            </div>
                        @endif
                    </x-panel>
                @empty
                    <div class="rounded-xl border border-dashed border-zinc-300 p-4 text-center text-xs text-zinc-500 dark:border-zinc-700">{{ __('Nothing here') }}</div>
                @endforelse
            </div>
        @endforeach
    </div>

    <flux:modal wire:model="showMilestoneForm" class="max-w-xl">
        <div class="space-y-5">
            <flux:heading size="lg">{{ $milestoneId ? __('Edit milestone') : __('Add milestone') }}</flux:heading>
            <form wire:submit="saveMilestone" class="space-y-4">
                <flux:input wire:model="title" :label="__('Title')" required />
                <flux:textarea wire:model="description" :label="__('Description')" rows="3" />
                <flux:input wire:model="targetDate" type="date" :label="__('Target date')" required />
                <flux:select wire:model="milestoneStatus" :label="__('Status')">
                    @foreach (\App\Enums\MilestoneStatus::cases() as $status)
                        <flux:select.option :value="$status->value">{{ str($status->value)->replace('_', ' ')->title() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <x-form-actions>
                    <flux:button variant="ghost" type="button" wire:click="$set('showMilestoneForm', false)">{{ __('Cancel') }}</flux:button>
                    <flux:button variant="primary" type="submit">{{ __('Save milestone') }}</flux:button>
                </x-form-actions>
            </form>
        </div>
    </flux:modal>

    <flux:modal wire:model="showTaskForm" class="max-w-xl">
        <div class="space-y-5">
            <flux:heading size="lg">{{ $taskId ? __('Edit task') : __('Add task') }}</flux:heading>
            <form wire:submit="saveTask" class="space-y-4">
                <flux:input wire:model="taskTitle" :label="__('Title')" required />
                <flux:textarea wire:model="taskDescription" :label="__('Description')" rows="2" />
                <flux:select wire:model.live="taskAssigneeType" :label="__('Assignment')">
                    <flux:select.option value="none">{{ __('Unassigned') }}</flux:select.option>
                    <flux:select.option value="employee">{{ __('Employee / worker') }}</flux:select.option>
                    <flux:select.option value="contractor">{{ __('Contractor (payable)') }}</flux:select.option>
                </flux:select>
                @if ($taskAssigneeType === 'employee')
                    <flux:select wire:model="taskAssigneeEmployeeId" :label="__('Employee')">
                        <flux:select.option value="">{{ __('Select employee') }}</flux:select.option>
                        @foreach ($employees as $employee)
                            <flux:select.option :value="$employee->id">{{ $employee->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif
                @if ($taskAssigneeType === 'contractor')
                    <flux:select wire:model="taskContractorId" :label="__('Contractor')">
                        <flux:select.option value="">{{ __('Select contractor') }}</flux:select.option>
                        @foreach ($contractors as $contractor)
                            <flux:select.option :value="$contractor->id">{{ $contractor->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="taskAgreedAmountRupees" type="number" step="0.01" min="0" :label="__('Agreed amount (रू)')" />
                @endif
                <flux:input wire:model="taskDueDate" type="date" :label="__('Due date')" />
                <x-form-actions>
                    <flux:button variant="ghost" type="button" wire:click="$set('showTaskForm', false)">{{ __('Cancel') }}</flux:button>
                    <flux:button variant="primary" type="submit">{{ __('Save task') }}</flux:button>
                </x-form-actions>
            </form>
        </div>
    </flux:modal>
</div>