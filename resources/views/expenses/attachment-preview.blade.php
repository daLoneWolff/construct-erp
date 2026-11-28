<x-layouts::app.sidebar :title="$attachment->filename">
    <div class="mx-auto max-w-3xl space-y-5 px-4 py-6 lg:px-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0 space-y-1">
                <flux:heading size="xl" class="break-words">{{ $attachment->filename }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Mock attachment for expense') }}
                    <flux:link :href="route('expenses.show', $expense)" wire:navigate>{{ $expense->expense_no }}</flux:link>
                </flux:text>
            </div>
            <flux:button variant="ghost" icon="arrow-left" :href="route('expenses.show', $expense)" wire:navigate>
                {{ __('Back to expense') }}
            </flux:button>
        </div>

        <flux:callout variant="warning" icon="information-circle">
            <flux:callout.heading>{{ __('Mock file preview') }}</flux:callout.heading>
            <flux:callout.text>
                {{ __('This system stores attachment metadata only. No real file was uploaded, so there is no original file content to open.') }}
            </flux:callout.text>
        </flux:callout>

        <x-panel title="{{ __('Attachment metadata') }}">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <flux:text class="text-xs text-zinc-500">{{ __('Filename') }}</flux:text>
                    <div class="font-medium break-words">{{ $attachment->filename }}</div>
                </div>
                <div>
                    <flux:text class="text-xs text-zinc-500">{{ __('MIME type') }}</flux:text>
                    <div class="font-medium">{{ $attachment->mime }}</div>
                </div>
                <div>
                    <flux:text class="text-xs text-zinc-500">{{ __('Size') }}</flux:text>
                    <div class="font-medium">{{ $attachment->size_label }}</div>
                </div>
                <div>
                    <flux:text class="text-xs text-zinc-500">{{ __('Mock path') }}</flux:text>
                    <div class="font-mono text-xs break-all">{{ $attachment->path_mock }}</div>
                </div>
            </dl>
        </x-panel>

        @if (str_starts_with($attachment->mime, 'image/'))
            <x-panel title="{{ __('Preview placeholder') }}">
                <div class="flex items-center justify-center rounded-xl border border-dashed border-border bg-surface-subtle p-8">
                    <div class="space-y-2 text-center">
                        <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Image preview placeholder') }}</div>
                        <flux:text class="text-sm text-zinc-500">{{ $attachment->filename }}</flux:text>
                    </div>
                </div>
            </x-panel>
        @endif
    </div>
</x-layouts::app.sidebar>
