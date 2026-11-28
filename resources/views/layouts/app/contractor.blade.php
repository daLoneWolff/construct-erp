<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-100 dark:bg-surface">
        @php
            $contractor = auth()->user()?->contractor;
            $company = app(\App\Services\TenantService::class)->currentCompany();
        @endphp

        <flux:sidebar sticky collapsible="mobile" class="border-e border-border bg-surface dark:bg-surface-subtle">
            <flux:sidebar.header>
                <a href="{{ route('portal.dashboard') }}" wire:navigate class="flex items-center gap-2 px-1">
                    <x-app-logo-icon class="size-8 shrink-0" />
                    <div class="min-w-0">
                        <div class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $company?->name ?? config('app.name') }}
                        </div>
                        <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Contractor portal') }}
                        </div>
                    </div>
                </a>
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('My portal')" class="grid gap-0.5">
                    <flux:sidebar.item
                        icon="home"
                        :href="route('portal.dashboard')"
                        :current="request()->routeIs('portal.dashboard')"
                        wire:navigate
                    >
                        {{ __('Home') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="banknotes"
                        :href="route('portal.payments')"
                        :current="request()->routeIs('portal.payments*')"
                        wire:navigate
                    >
                        {{ __('Payments') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="clipboard-document-list"
                        :href="route('portal.jobs')"
                        :current="request()->routeIs('portal.jobs*')"
                        wire:navigate
                    >
                        {{ __('Jobs') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <div class="hidden px-2 pb-2 lg:block">
                <div class="rounded-xl border border-border bg-surface-subtle px-3 py-2">
                    <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $contractor?->name ?? auth()->user()?->name }}</div>
                    <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ auth()->user()?->email }}</div>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="hidden px-2 pb-2 lg:block">
                @csrf
                <flux:button type="submit" variant="ghost" icon="arrow-right-start-on-rectangle" class="w-full justify-start">
                    {{ __('Log out') }}
                </flux:button>
            </form>
        </flux:sidebar>

        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <div class="min-w-0 flex-1 px-2">
                <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $contractor?->name ?? auth()->user()?->name }}</div>
                <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $company?->name ?? __('Contractor portal') }}</div>
            </div>

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar
                                :name="auth()->user()->name"
                                :initials="auth()->user()->initials()"
                            />
                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                            </div>
                        </div>
                    </div>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <flux:main class="bg-zinc-100 dark:bg-surface">
            {{ $slot }}
        </flux:main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
