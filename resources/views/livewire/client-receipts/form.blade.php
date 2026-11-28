<div class="space-y-5">
    <x-page-header :title="$isEdit ? __('Edit client receipt') : __('Record client receipt')">
        <div class="flex flex-wrap items-center gap-2">
            @if ($clientMoney && isset($projects))
                <flux:link :href="route('projects.client-receipts', $projectId)" wire:navigate class="text-sm">
                    {{ __('Back to client receipts') }}
                </flux:link>
            @endif
        </div>
    </x-page-header>

    <form wire:submit.prevent="save" class="space-y-5">
        <x-panel title="{{ __('Receipt details') }}">
            <div class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:select wire:model.live="projectId" :label="__('Project')" required>
                        <flux:select.option value="">{{ __('Select project') }}</flux:select.option>
                        @foreach ($projects as $option)
                            <flux:select.option :value="$option->id">
                                {{ $option->code }} — {{ $option->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="type" :label="__('Receipt type')" required>
                        @foreach ($types as $receiptType)
                            <flux:select.option :value="$receiptType->value">{{ __($receiptType->label()) }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model.live.debounce.300ms="amount" :label="__('Amount (NPR)')" placeholder="50000" required />
                    <flux:input wire:model="receivedOn" type="date" :label="__('Received on')" required />

                    <flux:select wire:model="method" :label="__('Method')" required>
                        @foreach ($methods as $paymentMethod)
                            <flux:select.option :value="$paymentMethod->value">
                                {{ str($paymentMethod->value)->replace('_', ' ')->title() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="runningBillId" :label="__('Running bill')">
                        <flux:select.option value="">{{ __('No running bill') }}</flux:select.option>
                        @foreach ($runningBills as $bill)
                            <flux:select.option :value="$bill->id">
                                {{ $bill->code }} — Bill #{{ $bill->bill_number }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="referenceNo" :label="__('Reference no.')" placeholder="Bank ref / cheque no." />
                    <div></div>
                </div>

                <flux:textarea wire:model="note" :label="__('Note')" rows="3" />
            </div>
        </x-panel>

        @if ($clientMoney)
            <x-panel title="{{ __('Project collection status') }}" description="{{ __('Collected against the full contract value, including advances.') }}">
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-stat-card label="Contract value" :paisa="$clientMoney['contract_value_paisa']" />
                    <x-stat-card label="Collected from client" :paisa="$clientMoney['collected_paisa']" />
                    <x-stat-card label="Remaining to collect" :paisa="$clientMoney['remaining_to_collect_paisa']" :tone="$clientMoney['remaining_to_collect_paisa'] > 0 ? 'amber' : 'green'" />
                </div>
            </x-panel>
        @endif

        <div class="flex items-center gap-3">
            <flux:button type="submit" variant="primary">
                {{ $isEdit ? __('Update receipt') : __('Record receipt') }}
            </flux:button>
            @if ($projectId !== '')
                <flux:button variant="ghost" :href="route('projects.client-receipts', $projectId)" wire:navigate>
                    {{ __('Cancel') }}
                </flux:button>
            @else
                <flux:button variant="ghost" :href="route('client-receipts.index')" wire:navigate>
                    {{ __('Cancel') }}
                </flux:button>
            @endif
        </div>
    </form>
</div>
