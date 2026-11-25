<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Set up your company')"
        :description="__('Create your workspace. It starts empty so you can add your own projects, contractors and payments.')"
    />

    <x-auth-session-status class="text-center" :status="session('status')" />

    <flux:callout variant="secondary" icon="building-office-2" :heading="__('One company per account')">
        <flux:text class="text-sm">
            {{ __('You will become the Owner/Admin of this workspace. Invitations and company switching are not part of this setup.') }}
        </flux:text>
    </flux:callout>

    <form wire:submit="createCompany" class="flex flex-col gap-5">
        <flux:input wire:model="name" :label="__('Company name')" :placeholder="__('e.g. Kathmandu Builders Pvt. Ltd.')" required />

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="pan" :label="__('PAN')" placeholder="601234567" maxlength="9" required />
            <flux:input wire:model="vat" :label="__('VAT (optional)')" placeholder="601234567" maxlength="9" />
        </div>

        <flux:input wire:model="address" :label="__('Address')" :placeholder="__('Ward, municipality, city')" required />

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="district" :label="__('District')" placeholder="Lalitpur" required />
            <flux:input wire:model="fiscal_year" :label="__('Fiscal year')" placeholder="2083/84" required />
        </div>

        <flux:button variant="primary" type="submit" class="w-full" wire:loading.attr="disabled">
            {{ __('Create company workspace') }}
        </flux:button>
    </form>
</div>
