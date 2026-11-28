<div class="mx-auto max-w-4xl space-y-5">
    <x-page-header title="{{ __('Edit project') }}" description="{{ $project->code }} · {{ $project->name }}">
        <x-slot:actions>
            <flux:button variant="ghost" :href="route('projects.show', $project)" wire:navigate>
                {{ __('Back to project') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <form wire:submit="save" class="space-y-5">
        <x-panel>
            @include('livewire.projects._form')
        </x-panel>

        @include('livewire.projects._partners')

        <x-form-actions>
            <flux:button variant="ghost" :href="route('projects.show', $project)" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button variant="primary" type="submit">
                {{ __('Save changes') }}
            </flux:button>
        </x-form-actions>
    </form>
</div>
