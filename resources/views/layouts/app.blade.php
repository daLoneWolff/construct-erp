<x-layouts::app.sidebar :title="$title ?? null">
    {{-- Single, quiet context bar. Page titles live in the screen body. --}}
    <div class="sticky top-0 z-30 flex min-h-14 items-center gap-3 border-b border-border bg-surface px-4 py-2.5 lg:px-6">
        <flux:text class="truncate text-sm font-medium text-zinc-600 dark:text-zinc-300">
            {{ auth()->user()?->currentCompany?->name ?? config('app.name') }}
        </flux:text>

        <flux:spacer />

        @if (config('demo.reset_enabled') && auth()->user()?->currentCompany?->is_demo)
            <form
                method="POST"
                action="{{ route('demo.reset') }}"
                onsubmit="return confirm('Reset all demo data back to the seeded state?');"
            >
                @csrf
                <flux:button type="submit" size="sm" variant="danger" icon="arrow-path">
                    {{ __('Reset demo') }}
                </flux:button>
            </form>
        @endif
    </div>

    @if (session('success') || session('error'))
        <div class="px-4 pt-4 lg:px-6">
            <flux:callout
                :variant="session('error') ? 'danger' : 'success'"
                :icon="session('error') ? 'exclamation-circle' : 'check-circle'"
            >
                {{ session('success') ?? session('error') }}
            </flux:callout>
        </div>
    @endif

    <flux:main class="bg-zinc-100 dark:bg-surface">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
