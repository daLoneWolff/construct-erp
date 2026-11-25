<x-layouts::auth :title="__('Verify email')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Verify your email address')"
            :description="__('Check your inbox for the verification link we just sent.')"
        />

        @if (session('status') === 'verification-link-sent')
            <flux:callout variant="success" icon="check-circle" :heading="__('A new verification link has been sent.')" />
        @endif

        @if (config('mail.default') === 'log')
            <flux:callout variant="warning" icon="information-circle" :heading="__('Local verification email')">
                <flux:text class="text-sm">
                    {{ __('This local app logs email instead of sending it. Open') }}
                    <code class="rounded bg-zinc-100 px-1 py-0.5 text-xs dark:bg-zinc-700">storage/logs/laravel.log</code>,
                    {{ __('search for "Verify your email address", and open the newest link.') }}
                </flux:text>
            </flux:callout>
        @endif

        <form method="POST" action="{{ route('verification.send') }}" class="flex flex-col gap-4">
            @csrf

            <flux:button variant="primary" type="submit" class="w-full">
                {{ __('Resend verification email') }}
            </flux:button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="flex justify-center">
            @csrf

            <flux:button variant="ghost" type="submit">
                {{ __('Log out') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
