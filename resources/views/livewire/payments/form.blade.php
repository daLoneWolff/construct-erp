<div class="space-y-5">
    <x-page-header :title="$isEdit ? __('Edit payment') : __('Record payment')">
        <div class="flex flex-wrap items-center gap-2">
            @if ($isEdit)
                <flux:link :href="route('payments.show', $payment)" wire:navigate class="text-sm">
                    {{ __('Back to payment') }}
                </flux:link>
            @elseif ($contextContractor)
                <flux:link :href="route('contractors.show', $contextContractor)" wire:navigate class="text-sm">
                    {{ __('Back to contractor') }}
                </flux:link>
            @elseif ($selectedJob)
                <flux:link :href="route('jobs.show', $selectedJob)" wire:navigate class="text-sm">
                    {{ __('Back to task') }}
                </flux:link>
            @else
                <flux:link :href="route('payments.index')" wire:navigate class="text-sm">
                    {{ __('Back to payments') }}
                </flux:link>
            @endif
            <flux:badge color="zinc" size="sm" class="font-mono">{{ $paymentNo }}</flux:badge>
        </div>
    </x-page-header>

    <form wire:submit="save" class="space-y-5">
        <x-panel title="{{ __('Payment details') }}">
                        @if ($isEdit)
                <flux:input
                    :label="__('Task')"
                    :value="$selectedJob?->project?->code.' — '.$selectedJob?->title"
                    readonly
                />
            @elseif ($contextContractor && $jobs->isEmpty())
                <flux:callout variant="warning" icon="exclamation-triangle">
                    <flux:callout.text>
                        {{ __('This contractor has no tasks with remaining balance to record a payment against.') }}
                    </flux:callout.text>
                </flux:callout>
            @else
                <flux:select wire:model.live="jobId" :label="__('Task')" required>
                    <flux:select.option value="">{{ __('Select task') }}</flux:select.option>
                    @foreach ($jobs as $option)
                        <flux:select.option :value="$option->id">
                            {{ $option->project?->code }} — {{ $option->title }}
                            @if ($option->contractor)
                                — {{ $option->contractor->name }}
                            @endif
                        </flux:select.option>
                    @endforeach
                </flux:select>
            @endif

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <flux:input
                    wire:model.live="amount"
                    type="number"
                    step="0.01"
                    min="0.01"
                    :label="__('Amount (NPR)')"
                    required
                />
                <flux:input wire:model.live="paidOn" type="date" :label="__('Paid on')" required />
                <flux:select wire:model="method" :label="__('Method')">
                    @foreach ($methods as $option)
                        <flux:select.option :value="$option->value">
                            {{ str($option->value)->replace('_', ' ')->title() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="referenceNo" :label="__('Reference number')" placeholder="NEFT-00000" />
            </div>

            <div class="mt-4 space-y-4">
                <flux:input
                    :label="__('Payee')"
                    :value="$payee?->name ?? __('Unassigned — choose a task with a contractor')"
                    readonly
                />

                <flux:textarea wire:model="note" :label="__('Note')" rows="3" />
            </div>
        </x-panel>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-stat-card label="Remaining before payment" :paisa="$remainingPaisa" tone="amber" />
            @if ($selectedJob)
                <x-stat-card
                    label="Remaining after this payment"
                    :paisa="$remainingAfterPaisa"
                    hint="Before payment minus amount entered"
                />
            @endif
        </div>

        <flux:callout variant="info" icon="information-circle">
            <flux:callout.text>
                {{ __('A payment cannot exceed the remaining task balance. Correct a mistake by editing or deleting the payment.') }}
            </flux:callout.text>
        </flux:callout>

        <x-form-actions>
            <flux:button
                variant="ghost"
                :href="$contextContractor ? route('contractors.show', $contextContractor) : route('payments.index')"
                wire:navigate
            >
                {{ __('Cancel') }}
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check">
                {{ $isEdit ? __('Update payment') : __('Save payment') }}
            </flux:button>
        </x-form-actions>
    </form>
</div>
