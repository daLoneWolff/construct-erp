<div class="space-y-5">
    <x-page-header :title="$isEdit ? __('Edit expense') : __('Record expense')">
        <div class="flex flex-wrap items-center gap-2">
            @if ($isEdit)
                <flux:link :href="route('expenses.show', $expense)" wire:navigate class="text-sm">
                    {{ __('Back to expense') }}
                </flux:link>
            @else
                <flux:link :href="route('expenses.index')" wire:navigate class="text-sm">
                    {{ __('Back to expenses') }}
                </flux:link>
            @endif
        </div>
    </x-page-header>

    <form wire:submit.prevent="save" class="space-y-5">
        <x-panel title="{{ __('Expense details') }}">
            <div class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="expenseDate" type="date" :label="__('Expense date')" required />
                    <flux:input wire:model="amount" :label="__('Amount (NPR)')" placeholder="5000" required />

                    <flux:select wire:model="category" :label="__('Category')" required>
                        @foreach ($categories as $option)
                            <flux:select.option :value="$option->value">{{ __($option->label()) }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="method" :label="__('Payment method')" required>
                        @foreach ($methods as $paymentMethod)
                            <flux:select.option :value="$paymentMethod->value">
                                {{ str($paymentMethod->value)->replace('_', ' ')->title() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="payee" :label="__('Payee')" placeholder="Name or business" />
                    <flux:input wire:model="referenceNo" :label="__('Reference no.')" placeholder="Bill / voucher / receipt" />
                </div>

                <flux:textarea wire:model="description" :label="__('Description')" rows="3" />
            </div>
        </x-panel>

        <x-panel title="{{ __('Attachments') }}" description="{{ __('Mock attachments only. The browser sends name, size, and MIME; no file is uploaded.') }}">
            <div class="space-y-4">
                @if ($existingAttachments->isNotEmpty())
                    <div class="space-y-2">
                        @foreach ($existingAttachments as $attachment)
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-border bg-surface-subtle px-3 py-2 text-sm" wire:key="existing-attachment-{{ $attachment->id }}">
                                <div class="min-w-0">
                                    <div class="truncate font-mono">{{ $attachment->filename }}</div>
                                    <div class="text-xs text-zinc-500">{{ $attachment->size_label }}</div>
                                </div>
                                <flux:button
                                    size="xs"
                                    variant="ghost"
                                    icon="x-mark"
                                    aria-label="{{ __('Remove attachment') }}"
                                    wire:click="removeExistingAttachment({{ $attachment->id }})"
                                    wire:confirm="{{ __('Remove this attachment?') }}"
                                />
                            </div>
                        @endforeach
                    </div>
                @endif

                <div>
                    <label for="expense-attachments" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Add attachments') }}
                    </label>
                    <input
                        id="expense-attachments"
                        type="file"
                        multiple
                        class="mt-1 block w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-zinc-500 file:me-3 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-1 file:text-sm dark:file:bg-zinc-700"
                        x-on:change="
                            $wire.set('uploads', Array.from($event.target.files).map(file => ({
                                name: file.name,
                                size: file.size,
                                mime: file.type || 'application/octet-stream'
                            })))
                        "
                    >
                    <x-field-error :message="$errors->first('uploads')" class="mt-1" />
                </div>

                @if ($uploads !== [])
                    <div class="space-y-2">
                        @foreach ($uploads as $index => $upload)
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-border bg-surface-subtle px-3 py-2 text-sm" wire:key="upload-{{ $index }}">
                                <span class="min-w-0 truncate font-mono">{{ $upload['name'] }}</span>
                                <flux:button
                                    size="xs"
                                    variant="ghost"
                                    icon="x-mark"
                                    aria-label="{{ __('Remove file') }}"
                                    wire:click="removeUpload({{ $index }})"
                                />
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-panel>

        <div class="flex items-center gap-3">
            <flux:button type="submit" variant="primary">
                {{ $isEdit ? __('Update expense') : __('Record expense') }}
            </flux:button>
            <flux:button variant="ghost" :href="$isEdit ? route('expenses.show', $expense) : route('expenses.index')" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>
</div>
