<div class="mx-auto max-w-4xl space-y-5">
    <x-page-header title="{{ __('New project') }}" description="{{ __('Create the contract record that BOQ, procurement and billing hang off.') }}">
        <x-slot:actions>
            <flux:button variant="ghost" :href="route('projects.index')" wire:navigate>
                {{ __('Back to projects') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <form wire:submit="save" class="space-y-5">
        <x-panel>
            @include('livewire.projects._form')
        </x-panel>

        <x-form-actions>
            <flux:button variant="ghost" :href="route('projects.index')" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button variant="primary" type="submit">
                {{ __('Create project') }}
            </flux:button>
        </x-form-actions>
    </form>
</div>
