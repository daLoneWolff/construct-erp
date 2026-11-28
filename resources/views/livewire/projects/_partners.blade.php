<x-panel
    title="{{ __('JV Partners') }}"
    description="{{ __('Add joint venture partners and their share percentage. When partners are present, shares must total 100%.') }}"
>
    <div class="space-y-4">
        @foreach ($partners as $index => $partner)
            <div class="grid gap-3 rounded-xl border border-border bg-surface-subtle p-4 sm:grid-cols-[1fr_180px_auto]" wire:key="partner-{{ $index }}">
                <flux:input
                    wire:model="partners.{{ $index }}.name"
                    :label="__('Partner name')"
                    placeholder="{{ __('ABC Construction') }}"
                    required
                />
                <flux:input
                    wire:model="partners.{{ $index }}.share_percent"
                    type="number"
                    step="0.01"
                    min="0.01"
                    max="100"
                    :label="__('Share %')"
                    placeholder="70.00"
                    required
                />
                <div class="flex items-end">
                    <flux:button
                        type="button"
                        variant="ghost"
                        icon="x-mark"
                        aria-label="{{ __('Remove partner') }}"
                        wire:click="removePartner({{ $index }})"
                    />
                </div>
            </div>
        @endforeach

        @error('partners')
            <x-field-error :message="$message" />
        @enderror

        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:button type="button" variant="ghost" icon="plus" wire:click="addPartner">
                {{ __('Add partner') }}
            </flux:button>

            @if ($partners !== [])
                @php($partnerTotal = collect($partners)->sum(fn ($p) => (float) ($p['share_percent'] ?? 0)))
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Total share') }}:
                    <span class="font-medium tabular-nums {{ abs($partnerTotal - 100) < 0.01 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                        <x-number :value="$partnerTotal" :decimals="2" suffix="%" />
                    </span>
                </flux:text>
            @endif
        </div>
    </div>
</x-panel>
