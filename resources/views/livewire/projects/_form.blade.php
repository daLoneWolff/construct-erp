@php
    $statusOptions = \App\Enums\ProjectStatus::cases();
@endphp

<div class="grid gap-4 md:grid-cols-2">
    <flux:input wire:model="code" :label="__('Project code')" placeholder="PRJ-001" required />
    <flux:input wire:model="name" :label="__('Project name')" required />
    <flux:input wire:model="client_name" :label="__('Client')" required />
    <flux:input wire:model="contract_value_npr" type="number" step="0.01" min="0" :label="__('Contract value (NPR)')" required />

    <flux:input wire:model="location_ward" :label="__('Ward')" required />
    <flux:input wire:model="location_municipality" :label="__('Municipality')" required />
    <flux:input wire:model="location_district" :label="__('District')" required />

    <flux:select wire:model="project_manager_id" :label="__('Project manager')" required>
        <flux:select.option value="">{{ __('Select a manager') }}</flux:select.option>
        @foreach ($managers as $manager)
            <flux:select.option :value="$manager->id">{{ $manager->name }}</flux:select.option>
        @endforeach
    </flux:select>

    <flux:input wire:model="start_date" type="date" :label="__('Start date')" required />
    <flux:input wire:model="end_date" type="date" :label="__('End date')" required />

    <flux:input wire:model="advance_npr" type="number" step="0.01" min="0" :label="__('Advance received (NPR)')" required />

    <flux:select wire:model="status" :label="__('Status')" required>
        @foreach ($statusOptions as $option)
            <flux:select.option :value="$option->value">
                {{ str($option->value)->replace('_', ' ')->title() }}
            </flux:select.option>
        @endforeach
    </flux:select>
</div>
