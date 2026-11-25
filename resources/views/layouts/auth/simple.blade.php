<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-100 antialiased text-zinc-900 dark:bg-surface dark:text-zinc-100">
        <div class="flex min-h-svh flex-col items-center justify-center p-6 md:p-10">
            <div class="w-full max-w-lg space-y-6">
                <a href="{{ route('login') }}" class="flex flex-col items-center gap-3 font-medium" wire:navigate>
                    <span class="flex size-11 items-center justify-center rounded-xl bg-brand-600 text-white shadow-sm dark:bg-brand-400 dark:text-zinc-950">
                        <x-app-logo-icon class="size-6 fill-current" />
                    </span>
                    <span class="text-sm font-semibold tracking-tight text-zinc-700 dark:text-zinc-200">
                        {{ config('app.name', 'Laravel') }}
                    </span>
                </a>

                <div class="rounded-2xl border border-border bg-surface p-6 shadow-sm sm:p-8">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
