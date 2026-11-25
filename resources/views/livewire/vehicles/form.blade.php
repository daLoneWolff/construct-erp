<div class="space-y-5">
    <x-page-header :title="$isEdit ? __('Edit vehicle') : __('Add vehicle')">
        <flux:link :href="route('vehicles.index')" wire:navigate class="text-sm">{{ __('Back to vehicles') }}</flux:link>
    </x-page-header>

    <form wire:submit="save" class="space-y-5">
        <x-panel title="{{ __('Vehicle details') }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="registration_no" :label="__('Registration no.')" required />
                <flux:input wire:model="type" :label="__('Type')" placeholder="Tipper" required />
                <flux:input wire:model="make_model" :label="__('Make / model')" required />

                <flux:select wire:model="status" :label="__('Status')" required>
                    @foreach ($statuses as $option)
                        <flux:select.option :value="$option->value">
                            {{ str($option->value)->replace('_', ' ')->title() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="currentProjectId" :label="__('Current project')">
                    <flux:select.option value="">{{ __('Unassigned') }}</flux:select.option>
                    @foreach ($projects as $project)
                        <flux:select.option :value="$project->id">
                            {{ $project->code }} — {{ $project->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </x-panel>

        <x-panel title="{{ __('Document expiry dates') }}" description="{{ __('The vehicle badge uses the worst of these four documents.') }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="bluebook_expiry" type="date" :label="__('Bluebook')" required />
                <flux:input wire:model="insurance_expiry" type="date" :label="__('Insurance')" required />
                <flux:input wire:model="pollution_expiry" type="date" :label="__('Pollution')" required />
                <flux:input wire:model="route_permit_expiry" type="date" :label="__('Route permit (optional)')" />
            </div>
        </x-panel>

        <x-form-actions>
            <flux:button variant="ghost" :href="route('vehicles.index')" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check">
                {{ $isEdit ? __('Update vehicle') : __('Create vehicle') }}
            </flux:button>
        </x-form-actions>
    </form>
</div>
