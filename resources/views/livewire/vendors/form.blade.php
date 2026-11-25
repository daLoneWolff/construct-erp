<div class="space-y-5">
    <x-page-header :title="$isEdit ? __('Edit vendor') : __('New vendor')">
        <flux:link :href="route('vendors.index')" wire:navigate class="text-sm">{{ __('Back to vendors') }}</flux:link>
    </x-page-header>

    <form wire:submit="save" class="space-y-5">
        <x-panel title="{{ __('Supplier details') }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="name" :label="__('Name')" required />
                <flux:input wire:model="pan" :label="__('PAN')" maxlength="9" required />
                <flux:input wire:model="vat" :label="__('VAT number')" maxlength="9" />
                <flux:input wire:model="contact_person" :label="__('Contact person')" required />
                <flux:input wire:model="phone" :label="__('Phone')" required />
                <flux:input wire:model="email" type="email" :label="__('Email')" />
                <div class="sm:col-span-2">
                    <flux:input wire:model="address" :label="__('Address')" required />
                </div>
            </div>
        </x-panel>

        <x-panel title="{{ __('Tax settings') }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="tds_rate" :label="__('TDS rate')">
                    @foreach ($tdsRates as $rate)
                        <flux:select.option :value="$rate">{{ $rate }}%</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="status" :label="__('Status')">
                    @foreach ($statuses as $status)
                        <flux:select.option :value="$status->value">
                            {{ str($status->value)->title() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </x-panel>

        <x-form-actions>
            <flux:button variant="ghost" :href="route('vendors.index')" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check">
                {{ __('Save vendor') }}
            </flux:button>
        </x-form-actions>
    </form>
</div>
