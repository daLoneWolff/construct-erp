<div class="space-y-5">
    <x-page-header title="{{ $receipt->receipt_no }}">
        <div class="flex flex-wrap items-center gap-2">
            <flux:link :href="route('projects.client-receipts', $receipt->project)" wire:navigate class="text-sm">
                {{ __('Back to client receipts') }}
            </flux:link>
            <flux:badge color="zinc" size="sm">{{ __($receipt->type->label()) }}</flux:badge>
        </div>

        <x-slot:actions>
            <flux:button icon="pencil-square" :href="route('client-receipts.edit', $receipt)" wire:navigate>
                {{ __('Edit') }}
            </flux:button>
            <flux:button variant="danger" icon="trash" wire:click="delete" wire:confirm="{{ __('Delete this client receipt?') }}">
                {{ __('Delete') }}
            </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-5 lg:grid-cols-3">
        <x-panel title="{{ __('Receipt details') }}" class="lg:col-span-2">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <flux:text class="text-xs text-zinc-500">{{ __('Project') }}</flux:text>
                    <div class="font-medium">{{ $receipt->project?->name }}</div>
                    <div class="text-xs text-zinc-500 font-mono">{{ $receipt->project?->code }}</div>
                </div>
                <div>
                    <flux:text class="text-xs text-zinc-500">{{ __('Running bill') }}</flux:text>
                    <div class="font-medium">{{ $receipt->runningBill?->code ?? __('None') }}</div>
                </div>
                <div>
                    <flux:text class="text-xs text-zinc-500">{{ __('Received on') }}</flux:text>
                    <div class="font-medium"><x-date :date="$receipt->received_on" /></div>
                </div>
                <div>
                    <flux:text class="text-xs text-zinc-500">{{ __('Method') }}</flux:text>
                    <div class="font-medium">{{ str($receipt->method->value)->replace('_', ' ')->title() }}</div>
                </div>
                <div>
                    <flux:text class="text-xs text-zinc-500">{{ __('Reference') }}</flux:text>
                    <div class="font-medium">{{ $receipt->reference_no ?: '—' }}</div>
                </div>
                <div>
                    <flux:text class="text-xs text-zinc-500">{{ __('Recorded by') }}</flux:text>
                    <div class="font-medium">{{ $receipt->creator?->name ?? '—' }}</div>
                </div>
                <div class="sm:col-span-2">
                    <flux:text class="text-xs text-zinc-500">{{ __('Note') }}</flux:text>
                    <div class="font-medium whitespace-pre-line">{{ $receipt->note ?: '—' }}</div>
                </div>
            </dl>
        </x-panel>

        <x-panel title="{{ __('Amount') }}">
            <div class="text-2xl font-semibold tabular-nums"><x-money :paisa="$receipt->amount_paisa" /></div>
            <flux:text class="mt-2 text-xs text-zinc-500">{{ __('Client receipt amount') }}</flux:text>
        </x-panel>
    </div>
</div>
