<x-layouts::auth :title="__('Reset password')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Forgot your password?')"
            :description="__('Enter your email address and we will send you a password reset link.')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                :placeholder="__('you@example.com')"
            />

            <flux:button variant="primary" type="submit" class="w-full">
                {{ __('Email password reset link') }}
            </flux:button>
        </form>

        <flux:text class="text-center text-sm">
            <flux:link :href="route('login')" wire:navigate>{{ __('Back to log in') }}</flux:link>
        </flux:text>
    </div>
</x-layouts::auth>
