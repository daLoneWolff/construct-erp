<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-100 dark:bg-surface">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-border bg-surface dark:bg-surface-subtle">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Overview')" class="grid gap-0.5">
                    <flux:sidebar.item
                        icon="layout-grid"
                        :href="route('dashboard')"
                        :current="request()->routeIs('dashboard')"
                        wire:navigate
                    >
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="folder"
                        :href="route('projects.index')"
                        :current="request()->routeIs('projects.*', 'jobs.*')"
                        wire:navigate
                    >
                        {{ __('Projects') }}
                    </flux:sidebar.item>

                    @if (Route::has('client-receipts.index'))
                        <flux:sidebar.item
                            icon="arrow-down-tray"
                            :href="route('client-receipts.index')"
                            :current="request()->routeIs('client-receipts.*', 'projects.client-receipts*')"
                            wire:navigate
                        >
                            {{ __('Client receipts') }}
                        </flux:sidebar.item>
                    @endif

                    @if (Route::has('contractors.index'))
                        <flux:sidebar.item
                            icon="users"
                            :href="route('contractors.index')"
                            :current="request()->routeIs('contractors.*', 'print.contractor-ledger')"
                            wire:navigate
                        >
                            {{ __('Contractors') }}
                        </flux:sidebar.item>
                    @endif

                    @if (Route::has('payments.index'))
                        <flux:sidebar.item
                            icon="banknotes"
                            :href="route('payments.index')"
                            :current="request()->routeIs('payments.*', 'print.payment', 'print.project-payments')"
                            wire:navigate
                        >
                            {{ __('Payments') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>


                @if (Route::has('employees.index') || Route::has('attendance.index'))
                    <flux:sidebar.group :heading="__('People')" class="grid gap-0.5">
                        @if (Route::has('employees.index'))
                            <flux:sidebar.item icon="users" :href="route('employees.index')" :current="request()->routeIs('employees.*')" wire:navigate>
                                {{ __('Employees') }}
                            </flux:sidebar.item>
                        @endif

                        @if (Route::has('attendance.index'))
                            <flux:sidebar.item icon="calendar-days" :href="route('attendance.index')" :current="request()->routeIs('attendance.*')" wire:navigate>
                                {{ __('Attendance') }}
                            </flux:sidebar.item>
                        @endif

                    </flux:sidebar.group>
                @endif

                @if (Route::has('vendors.index') || Route::has('requisitions.index') || Route::has('purchase-orders.index'))
                    <flux:sidebar.group :heading="__('Procurement')" class="grid gap-0.5">
                        @if (Route::has('vendors.index'))
                            <flux:sidebar.item icon="building-storefront" :href="route('vendors.index')" :current="request()->routeIs('vendors.*')" wire:navigate>
                                {{ __('Vendors') }}
                            </flux:sidebar.item>
                        @endif

                        @if (Route::has('requisitions.index'))
                            <flux:sidebar.item icon="document-text" :href="route('requisitions.index')" :current="request()->routeIs('requisitions.*')" wire:navigate>
                                {{ __('Requisitions') }}
                            </flux:sidebar.item>
                        @endif

                        @if (Route::has('purchase-orders.index'))
                            <flux:sidebar.item icon="shopping-cart" :href="route('purchase-orders.index')" :current="request()->routeIs('purchase-orders.*')" wire:navigate>
                                {{ __('Purchase orders') }}
                            </flux:sidebar.item>
                        @endif
                    </flux:sidebar.group>
                @endif

                {{-- Warehouse menu hidden for now.
                @if (Route::has('warehouses.index') || Route::has('receipts.index') || Route::has('issues.index') || Route::has('ledger.index'))
                    <flux:sidebar.group :heading="__('Warehouse')" class="grid gap-0.5">
                        @if (Route::has('warehouses.index'))
                            <flux:sidebar.item icon="archive-box" :href="route('warehouses.index')" :current="request()->routeIs('warehouses.*')" wire:navigate>
                                {{ __('Warehouses') }}
                            </flux:sidebar.item>
                        @endif

                        @if (Route::has('receipts.index'))
                            <flux:sidebar.item icon="truck" :href="route('receipts.index')" :current="request()->routeIs('receipts.*')" wire:navigate>
                                {{ __('Receipts') }}
                            </flux:sidebar.item>
                        @endif

                        @if (Route::has('issues.index'))
                            <flux:sidebar.item icon="arrow-up-tray" :href="route('issues.index')" :current="request()->routeIs('issues.*')" wire:navigate>
                                {{ __('Issues') }}
                            </flux:sidebar.item>
                        @endif

                        @if (Route::has('ledger.index'))
                            <flux:sidebar.item icon="queue-list" :href="route('ledger.index')" :current="request()->routeIs('ledger.*')" wire:navigate>
                                {{ __('Stock ledger') }}
                            </flux:sidebar.item>
                        @endif
                    </flux:sidebar.group>
                @endif
                --}}

                @if (Route::has('equipment.index') || Route::has('vehicles.index') || Route::has('documents.index') || Route::has('reports.project-summary'))
                    <flux:sidebar.group :heading="__('Office')" class="grid gap-0.5">
                        @if (Route::has('equipment.index'))
                            <flux:sidebar.item icon="wrench-screwdriver" :href="route('equipment.index')" :current="request()->routeIs('equipment.*')" wire:navigate>
                                {{ __('Equipment') }}
                            </flux:sidebar.item>
                        @endif

                        @if (Route::has('vehicles.index'))
                            <flux:sidebar.item icon="truck" :href="route('vehicles.index')" :current="request()->routeIs('vehicles.*')" wire:navigate>
                                {{ __('Vehicles') }}
                            </flux:sidebar.item>
                        @endif

                        @if (Route::has('documents.index'))
                            <flux:sidebar.item icon="document" :href="route('documents.index')" :current="request()->routeIs('documents.*')" wire:navigate>
                                {{ __('Documents') }}
                            </flux:sidebar.item>
                        @endif

                        @if (Route::has('expenses.index'))
                            <flux:sidebar.item icon="receipt-percent" :href="route('expenses.index')" :current="request()->routeIs('expenses.*')" wire:navigate>
                                {{ __('Expenses') }}
                            </flux:sidebar.item>
                        @endif

                        @if (Route::has('reports.project-summary'))
                            <flux:sidebar.item icon="chart-bar" :href="route('reports.project-summary')" :current="request()->routeIs('reports.*')" wire:navigate>
                                {{ __('Reports') }}
                            </flux:sidebar.item>
                        @endif
                    </flux:sidebar.group>
                @endif

                <flux:sidebar.group :heading="__('Settings')" class="grid gap-0.5">
                    <flux:sidebar.item icon="cog-6-tooth" :href="route('profile.edit')" :current="request()->routeIs('profile.*')" wire:navigate>
                        {{ __('Profile') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="shield-check" :href="route('security.edit')" :current="request()->routeIs('security.*')" wire:navigate>
                        {{ __('Security') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="sun" :href="route('appearance.edit')" :current="request()->routeIs('appearance.*')" wire:navigate>
                        {{ __('Appearance') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
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
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

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

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
