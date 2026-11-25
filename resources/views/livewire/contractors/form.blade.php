<div class="space-y-5">
    <x-page-header :title="$isEdit ? __('Edit contractor') : __('New contractor')">
        <flux:link :href="route('contractors.index')" wire:navigate class="text-sm">
            {{ __('Back to contractors') }}
        </flux:link>
    </x-page-header>

    <form wire:submit="save" class="space-y-5">
        <x-panel title="{{ __('Contractor details') }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="name" :label="__('Name')" placeholder="Dhan Bahadur Mason" required />
                <flux:input wire:model="phone" :label="__('Phone')" placeholder="9841000000" />
                <flux:input wire:model="pan" :label="__('PAN (optional)')" maxlength="9" placeholder="112233445" />
                <flux:switch wire:model="is_active" :label="__('Active')" />
            </div>

            <flux:input wire:model="address" :label="__('Address')" placeholder="Ward, municipality, district" />

            <flux:textarea wire:model="notes" :label="__('Notes')" rows="3" />
        </x-panel>

        <x-form-actions>
            <flux:button variant="ghost" :href="route('contractors.index')" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check">
                {{ $isEdit ? __('Update contractor') : __('Create contractor') }}
            </flux:button>
        </x-form-actions>
    </form>
</div>
