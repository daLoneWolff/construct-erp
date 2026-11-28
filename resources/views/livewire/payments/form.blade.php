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
                @if ($selectedJob)
                    <flux:input
                        :label="__('Task')"
                        :value="$selectedJob->project?->code.' ? '.$selectedJob->title"
                        readonly
                    />
                @else
                    <flux:input
                        :label="__('Payment type')"
                        :value="__('General / upfront payment (no task)')"
                        readonly
                    />
                @endif
            @else
                <flux:select wire:model.live="jobId" :label="__('Task')">
                    <flux:select.option value="">{{ __('None') }}</flux:select.option>
                    @foreach ($jobs as $option)
                        <flux:select.option :value="$option->id">
                            {{ $option->project?->code }} ? {{ $option->title }}
                            @if ($option->contractor)
                                ? {{ $option->contractor->name }}
                            @endif
                        </flux:select.option>
                    @endforeach
                </flux:select>
            @endif

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <flux:input
                    wire:model.live="amount"
                    :label="__('Amount (NPR)')"
                    type="number"
                    step="0.01"
                    min="0.01"
                    required
                />

                <flux:input
                    wire:model.live="paidOn"
                    :label="__('Paid on')"
                    type="date"
                    required
                />

                <flux:select wire:model.live="method" :label="__('Method')" required>
                    @foreach ($methods as $option)
                        <flux:select.option :value="$option->value">
                            {{ str($option->value)->replace('_', ' ')->title() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input
                    wire:model="referenceNo"
                    :label="__('Reference no.')"
                    :placeholder="__('Cheque / bank reference')"
                />
            </div>

            <div class="mt-4">
                <flux:textarea
                    wire:model="note"
                    :label="__('Note')"
                    rows="3"
                    :placeholder="__('What is this payment for?')"
                />
            </div>
        </x-panel>

        <div class="grid gap-4 sm:grid-cols-2">
            @if ($selectedJob)
                <x-stat-card label="Remaining before payment" :paisa="$remainingPaisa" tone="amber" />
                <x-stat-card
                    label="Remaining after this payment"
                    :paisa="$remainingAfterPaisa"
                    hint="Before payment minus amount entered"
                />
            @else
                <x-stat-card label="Payment type" :value="__('General / upfront')" hint="No task selected" />
                <x-stat-card label="Project" :value="__('None')" hint="General payment has no project" />
            @endif
        </div>

        <flux:callout variant="info" icon="information-circle">
            <flux:callout.text>
                @if ($selectedJob)
                    {{ __('A payment cannot exceed the remaining task balance. Correct a mistake by editing or deleting the payment.') }}
                @else
                    {{ __('General payments are not linked to a task. Use the note to describe what this upfront payment is for. Correct a mistake by editing or deleting the payment.') }}
                @endif
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
