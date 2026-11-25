<x-layouts::auth :title="__('Reset password')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Reset your password')"
            :description="__('Choose a new password for your account.')"
        />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-6">
            @csrf

            <input type="hidden" name="token" value="{{ old('token', request('token')) }}" />

            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email', request('email'))"
                type="email"
                required
                autofocus
                autocomplete="email"
                readonly
            />

            <flux:input
                name="password"
                :label="__('New password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('New password')"
                viewable
            />

            <flux:input
                name="password_confirmation"
                :label="__('Confirm new password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm new password')"
                viewable
            />

            <flux:button variant="primary" type="submit" class="w-full">
                {{ __('Reset password') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
