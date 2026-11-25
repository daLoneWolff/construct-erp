<div class="space-y-5">
    <x-page-header :title="$isEdit ? __('Edit employee') : __('Add employee')">
        <flux:link :href="route('employees.index')" wire:navigate class="text-sm">
            {{ __('Back to employees') }}
        </flux:link>
    </x-page-header>

    <form wire:submit="save" class="space-y-5">
        <x-panel title="{{ __('Identity') }}">
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="employee_code" :label="__('Employee code')" placeholder="EMP-027" />
                <flux:input wire:model="name" :label="__('Full name')" />
                <flux:input wire:model="phone" :label="__('Phone')" placeholder="98########" />
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="designation" :label="__('Designation')" />
                <flux:input wire:model="trade" :label="__('Trade')" placeholder="Mason, Electrician…" />
                <flux:input wire:model="department" :label="__('Department')" />
            </div>
        </x-panel>

        <x-panel title="{{ __('Employment') }}">
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:select wire:model.live="employment_type" :label="__('Employment type')">
                    @foreach ($employmentTypes as $type)
                        <flux:select.option :value="$type->value">
                            {{ str($type->value)->title() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                @if ($employment_type === 'daily')
                    <flux:input wire:model="daily_wage" type="number" step="0.01" min="0" :label="__('Daily wage (NPR)')" />
                @else
                    <flux:input wire:model="monthly_basic" type="number" step="0.01" min="0" :label="__('Monthly basic (NPR)')" />
                @endif

                <flux:input wire:model="joined_at" type="date" :label="__('Joined on')" />
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <flux:select wire:model="status" :label="__('Status')">
                    <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                    <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
                </flux:select>
            </div>
        </x-panel>

        <x-panel title="{{ __('SSF & PAN') }}">
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="pan" :label="__('PAN')" maxlength="9" placeholder="601234567" />
                <flux:input wire:model="ssf_no" :label="__('SSF number')" placeholder="SSF-000027" />
                <div class="flex items-end pb-2">
                    <flux:switch wire:model="ssf_enrolled" :label="__('SSF enrolled')" />
                </div>
            </div>
        </x-panel>

        <x-form-actions>
            <flux:button variant="ghost" :href="route('employees.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" icon="check">{{ __('Save employee') }}</flux:button>
        </x-form-actions>
    </form>
</div>
