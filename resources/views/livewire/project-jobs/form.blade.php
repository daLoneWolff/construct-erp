<div class="space-y-5">
    <x-page-header :title="$isEdit ? __('Edit task') : __('New task')">
        <div class="flex flex-wrap items-center gap-2">
            @if ($isEdit)
                <flux:link :href="route('jobs.show', $job)" wire:navigate class="text-sm">
                    {{ __('Back to task') }}
                </flux:link>
            @else
                <flux:link :href="route('projects.milestones', $project)" wire:navigate class="text-sm">
                    {{ __('Back to milestones') }}
                </flux:link>
                <flux:badge color="zinc" size="sm" class="font-mono">{{ $project?->code }}</flux:badge>
            @endif
        </div>
    </x-page-header>

    <form wire:submit="save" class="space-y-5">
        <x-panel title="{{ __('Task details') }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="title" :label="__('Title')" required />
                <flux:select wire:model="status" :label="__('Status')">
                    @foreach ($statuses as $option)
                        <flux:select.option :value="$option->value">
                            {{ str($option->value)->replace('_', ' ')->title() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="mt-4">
                <flux:textarea wire:model="description" :label="__('Description')" rows="3" />
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="milestoneId" :label="__('Milestone')" required>
                    <flux:select.option value="">{{ __('Select milestone') }}</flux:select.option>
                    @foreach ($milestones as $milestone)
                        <flux:select.option :value="$milestone->id">{{ $milestone->title }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="assigneeType" :label="__('Assignment')">
                    <flux:select.option value="none">{{ __('Unassigned') }}</flux:select.option>
                    <flux:select.option value="employee">{{ __('Employee / worker') }}</flux:select.option>
                    <flux:select.option value="contractor">{{ __('Contractor (payable)') }}</flux:select.option>
                </flux:select>

                @if ($assigneeType === 'employee')
                    <flux:select wire:model="assigneeEmployeeId" :label="__('Employee')">
                        <flux:select.option value="">{{ __('Select employee') }}</flux:select.option>
                        @foreach ($employees as $employee)
                            <flux:select.option :value="$employee->id">{{ $employee->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                @if ($assigneeType === 'contractor')
                    <flux:select wire:model="contractorId" :label="__('Contractor')">
                        <flux:select.option value="">{{ __('Select contractor') }}</flux:select.option>
                        @foreach ($contractors as $contractor)
                            <flux:select.option :value="$contractor->id">{{ $contractor->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input
                        wire:model="agreedAmount"
                        type="number"
                        step="0.01"
                        min="0"
                        :label="__('Agreed amount (NPR)')"
                    />
                @endif

                <flux:input wire:model="startsOn" type="date" :label="__('Starts on')" />
                <flux:input wire:model="dueOn" type="date" :label="__('Due on')" />
            </div>

            @if ($isEdit && $job->isPayable())
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <x-stat-card label="Paid" :paisa="$job->paid_amount_paisa" />
                    <x-stat-card label="Balance" :paisa="$job->balance_paisa" tone="amber" />
                </div>
            @endif

            @if ($assigneeType === 'contractor')
                <flux:callout icon="banknotes" color="amber" class="mt-4 !py-2">
                    <flux:callout.text>
                        {{ __('Contractor tasks are payable. Balance is agreed amount minus recorded payments.') }}
                    </flux:callout.text>
                </flux:callout>
            @endif
        </x-panel>

        <x-form-actions>
            <flux:button variant="ghost" :href="$isEdit ? route('jobs.show', $job) : route('projects.milestones', $project)" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check">
                {{ $isEdit ? __('Update task') : __('Create task') }}
            </flux:button>
        </x-form-actions>
    </form>
</div>