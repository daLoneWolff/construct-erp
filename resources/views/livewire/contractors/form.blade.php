<div class="space-y-5">
    <x-page-header :title="$isEdit ? __('Edit contractor') : __('New contractor')">
        <flux:link :href="route('contractors.index')" wire:navigate class="text-sm">
            {{ __('Back to contractors') }}
        </flux:link>
    </x-page-header>

    <form wire:submit="save" class="space-y-5">
        <x-panel title="{{ __('Contractor details') }}">
            <div class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="name" :label="__('Name')" placeholder="Dhan Bahadur Mason" required />
                    <flux:input wire:model="phone" :label="__('Phone')" placeholder="9841000000" />
                    <flux:input wire:model="email" type="email" :label="__('Email')" placeholder="contractor@example.com" />
                    <flux:input wire:model="pan" :label="__('PAN (optional)')" maxlength="9" placeholder="112233445" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="address" :label="__('Address')" placeholder="Ward, municipality, district" />
                    <div class="flex items-end pb-2">
                        <flux:switch wire:model="is_active" :label="__('Active')" />
                    </div>
                </div>

                <flux:textarea wire:model="notes" :label="__('Notes')" rows="3" />
            </div>
        </x-panel>

        <x-panel title="{{ __('Portal access') }}" description="{{ __('Allow this contractor to sign in and view their own payments and assigned jobs.') }}">
            <div class="space-y-4">
                <flux:switch wire:model.live="enable_portal_access" :label="__('Enable contractor portal login')" />

                @if ($enable_portal_access)
                    <flux:callout variant="secondary" icon="information-circle">
                        <flux:callout.heading>{{ __('Contractor sign-in') }}</flux:callout.heading>
                        <flux:callout.text>
                            {{ __('Use the contractor email above and this password on the normal login page. The contractor will only see their own records.') }}
                        </flux:callout.text>
                    </flux:callout>

                    @if ($linkedUser)
                        <div class="rounded-xl border border-border bg-surface-subtle px-4 py-3 text-sm">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Portal account linked') }}</div>
                            <div class="text-zinc-500 dark:text-zinc-400">{{ $linkedUser->email }}</div>
                        </div>
                    @endif

                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:input
                            wire:model="portal_password"
                            type="password"
                            :label="$linkedUser ? __('New password (optional)') : __('Portal password')"
                            :placeholder="$linkedUser ? __('Leave blank to keep current password') : __('Set contractor password')"
                            viewable
                        />
                        <flux:input
                            wire:model="portal_password_confirmation"
                            type="password"
                            :label="__('Confirm portal password')"
                            :placeholder="__('Confirm password')"
                            viewable
                        />
                    </div>
                @elseif ($linkedUser)
                    <flux:callout variant="warning" icon="exclamation-triangle">
                        <flux:callout.heading>{{ __('Portal access will be removed') }}</flux:callout.heading>
                        <flux:callout.text>{{ __('Saving with portal access disabled will unlink this contractor login from this company.') }}</flux:callout.text>
                    </flux:callout>
                @endif
            </div>
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
