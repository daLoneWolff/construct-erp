<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Log in to your account')"
            :description="__('Enter your email and password below to access your workspace.')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        @if (session('error'))
            <flux:callout variant="danger" icon="exclamation-triangle" :heading="session('error')" />
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
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

            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Password')"
                viewable
            />

            <div class="flex items-center justify-between gap-3">
                <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

                @if (Route::has('password.request'))
                    <flux:link :href="route('password.request')" wire:navigate class="text-sm">
                        {{ __('Forgot your password?') }}
                    </flux:link>
                @endif
            </div>

            <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                {{ __('Log in') }}
            </flux:button>
        </form>

        @if (Route::has('register'))
            <flux:text class="text-center text-sm">
                {{ __('New to the app?') }}
                <flux:link :href="route('register')" wire:navigate>{{ __('Create an account') }}</flux:link>
            </flux:text>
        @endif

        @if (config('demo.reset_enabled'))
            <flux:callout variant="secondary" icon="information-circle" :heading="__('Local demo account')">
                <flux:text class="text-sm">
                    {{ __('The bundled fictional demo can still be opened with admin@ranta.test / password.') }}
                </flux:text>
            </flux:callout>
        @endif
    </div>
</x-layouts::auth>
