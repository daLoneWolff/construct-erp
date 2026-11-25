<x-layouts::auth :title="__('Create an account')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Create your account')"
            :description="__('Start tracking contractor payments in your own company workspace.')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        @if (session('error'))
            <flux:callout variant="danger" icon="exclamation-triangle" :heading="session('error')" />
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="name"
                :label="__('Name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Your full name')"
            />

            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                :placeholder="__('you@example.com')"
            />

            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                viewable
            />

            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                viewable
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="register-button">
                {{ __('Create account') }}
            </flux:button>
        </form>

        <div class="flex items-center gap-3">
            <div class="h-px flex-1 bg-border"></div>
            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('or sign up with Google') }}</flux:text>
            <div class="h-px flex-1 bg-border"></div>
        </div>

        <flux:button
            variant="outline"
            icon="globe-alt"
            :href="route('auth.google.redirect')"
            class="w-full"
        >
            {{ __('Continue with Google') }}
        </flux:button>

        <flux:text class="text-center text-sm">
            {{ __('Already have an account?') }}
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </flux:text>
    </div>
</x-layouts::auth>
