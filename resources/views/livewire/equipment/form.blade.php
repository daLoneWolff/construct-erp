<div class="space-y-5">
    <x-page-header :title="$isEdit ? __('Edit equipment') : __('Add equipment')">
        <flux:link :href="route('equipment.index')" wire:navigate class="text-sm">{{ __('Back to equipment') }}</flux:link>
    </x-page-header>

    <form wire:submit="save" class="space-y-5">
        <x-panel title="{{ __('Equipment details') }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="code" :label="__('Code')" :readonly="$isEdit" required />
                <flux:input wire:model="name" :label="__('Name')" required />
                <flux:input wire:model="type" :label="__('Type')" placeholder="Excavator" required />

                <flux:select wire:model="ownership" :label="__('Ownership')" required>
                    @foreach ($ownershipTypes as $option)
                        <flux:select.option :value="$option->value">
                            {{ str($option->value)->title() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="hourly_rate" type="number" step="0.01" min="0" :label="__('Hourly rate (NPR)')" />
                <flux:input wire:model="daily_rate" type="number" step="0.01" min="0" :label="__('Daily rate (NPR)')" />

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

        <x-form-actions>
            <flux:button variant="ghost" :href="route('equipment.index')" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check">
                {{ $isEdit ? __('Update equipment') : __('Create equipment') }}
            </flux:button>
        </x-form-actions>
    </form>
</div>
