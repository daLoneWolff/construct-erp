<div class="space-y-5">
    <x-page-header title="{{ __('Bill of quantities') }}">
        <div class="flex flex-wrap items-center gap-2">
            <flux:badge color="zinc" size="sm" class="font-mono">{{ $project->code }}</flux:badge>
            <flux:link :href="route('projects.show', $project)" wire:navigate class="text-sm">
                {{ __('Back to project') }}
            </flux:link>
        </div>
        <div>{{ $project->name }} · {{ $project->client_name }}</div>

        <x-slot:actions>
            <flux:button icon="arrow-down-tray" :href="route('projects.boq.template', $project)">
                {{ __('Download template') }}
            </flux:button>

            @can('manage')
                <flux:button icon="arrow-up-tray" wire:click="$set('showImport', true)">
                    {{ __('Import CSV') }}
                </flux:button>

                <flux:button variant="primary" icon="plus" wire:click="create">
                    {{ __('Add item') }}
                </flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    @if ($headers->isEmpty())
        <x-empty-state
            title="{{ __('No BOQ yet') }}"
            description="{{ __('Import a BOQ CSV or add the first item to start measuring progress.') }}"
        >
            @can('manage')
                <flux:button variant="primary" icon="plus" wire:click="create">
                    {{ __('Add your first item') }}
                </flux:button>
            @endcan
        </x-empty-state>
    @else
        @foreach ($headers as $header)
            <x-panel flush>
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-5 py-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:badge color="zinc" size="sm" class="font-mono">{{ $header->code }}</flux:badge>
                        <flux:heading size="sm">{{ $header->title }}</flux:heading>
                    </div>

                    @can('manage')
                        <flux:button
                            size="sm"
                            variant="ghost"
                            icon="plus"
                            wire:click="create({{ $header->clauses->first()?->id ?? 'null' }})"
                        >
                            {{ __('Add item') }}
                        </flux:button>
                    @endcan
                </div>

                @foreach ($header->clauses as $clause)
                    <div class="border-b border-border px-5 py-4 last:border-0">
                        <div class="flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            <span class="font-mono text-xs text-zinc-500 dark:text-zinc-400">{{ $clause->code }}</span>
                            <span>{{ $clause->title }}</span>
                        </div>

                        @if ($clause->items->isEmpty())
                            <flux:text class="mt-3 text-sm text-zinc-500">{{ __('No items in this clause.') }}</flux:text>
                        @else
                            <div class="mt-3 overflow-x-auto">
                                <flux:table>
                                    <flux:table.columns>
                                        <flux:table.column>{{ __('Code') }}</flux:table.column>
                                        <flux:table.column>{{ __('Description') }}</flux:table.column>
                                        <flux:table.column>{{ __('Unit') }}</flux:table.column>
                                        <flux:table.column align="end" class="text-right">{{ __('Qty') }}</flux:table.column>
                                        <flux:table.column align="end" class="text-right">{{ __('Rate') }}</flux:table.column>
                                        <flux:table.column align="end" class="text-right">{{ __('Amount') }}</flux:table.column>
                                        <flux:table.column class="w-1/4">{{ __('Executed') }}</flux:table.column>
                                        @can('manage')
                                            <flux:table.column align="end" class="text-right">{{ __('Actions') }}</flux:table.column>
                                        @endcan
                                    </flux:table.columns>

                                    <flux:table.rows>
                                        @foreach ($clause->items as $item)
                                            <flux:table.row :key="$item->id">
                                                <flux:table.cell class="font-mono text-xs">{{ $item->code }}</flux:table.cell>
                                                <flux:table.cell>{{ $item->description }}</flux:table.cell>
                                                <flux:table.cell class="text-xs text-zinc-500">{{ $item->unit }}</flux:table.cell>
                                                <flux:table.cell class="text-right tabular-nums"><x-number :value="$item->quantity" :decimals="2" /></flux:table.cell>
                                                <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$item->rate_paisa" /></flux:table.cell>
                                                <flux:table.cell class="text-right tabular-nums"><x-money :paisa="$item->amount_paisa" /></flux:table.cell>
                                                <flux:table.cell>
                                                    <div class="flex items-center gap-2">
                                                        <x-progress-bar :pct="$progress[$item->id]['pct']" class="flex-1" />
                                                        <span class="w-24 text-right text-xs text-zinc-500">
                                                            <x-number :value="$item->executed_quantity" :decimals="1" /> / <x-number :value="$item->quantity" :decimals="1" />
                                                            (<x-number :value="$progress[$item->id]['pct']" :decimals="1" suffix="%" />)
                                                        </span>
                                                    </div>
                                                </flux:table.cell>
                                                @can('manage')
                                                    <flux:table.cell class="text-right">
                                                        <div class="flex justify-end gap-1">
                                                            <flux:button size="sm" variant="ghost" icon="pencil-square" aria-label="{{ __('Edit item') }}" wire:click="edit({{ $item->id }})" />
                                                            <flux:button
                                                                size="sm"
                                                                variant="ghost"
                                                                icon="trash"
                                                                aria-label="{{ __('Delete item') }}"
                                                                wire:click="delete({{ $item->id }})"
                                                                wire:confirm="{{ __('Delete this BOQ item?') }}"
                                                            />
                                                        </div>
                                                    </flux:table.cell>
                                                @endcan
                                            </flux:table.row>
                                        @endforeach
                                    </flux:table.rows>
                                </flux:table>
                            </div>
                        @endif
                    </div>
                @endforeach
            </x-panel>
        @endforeach
    @endif

    <flux:modal wire:model="showForm" class="md:w-[36rem]">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">
                {{ $editingId ? __('Edit BOQ item') : __('Add BOQ item') }}
            </flux:heading>

            <flux:select wire:model="clauseId" :label="__('Clause')">
                @foreach ($clauses as $clause)
                    <flux:select.option :value="$clause->id">
                        {{ $clause->code }} · {{ $clause->title }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="code" :label="__('Item code')" placeholder="A.1.4" />
                <flux:select wire:model="unit" :label="__('Unit')">
                    @foreach ($units as $itemUnit)
                        <flux:select.option :value="$itemUnit">{{ $itemUnit }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:input wire:model="description" :label="__('Description')" />

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="quantity" type="number" step="0.001" min="0" :label="__('Quantity')" />
                <flux:input wire:model="rate" type="number" step="0.01" min="0" :label="__('Rate (NPR)')" />
                <flux:input wire:model="executed" type="number" step="0.001" min="0" :label="__('Executed qty')" />
            </div>

            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Amount and progress are calculated automatically from quantity, rate and executed quantity.') }}
            </flux:text>

            <x-form-actions>
                <flux:button variant="ghost" wire:click="$set('showForm', false)">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Save item') }}</flux:button>
            </x-form-actions>
        </form>
    </flux:modal>

    <flux:modal wire:model="showImport" class="md:w-[32rem]">
        <form
            action="{{ route('projects.boq.import', $project) }}"
            method="POST"
            enctype="multipart/form-data"
            class="space-y-4"
        >
            @csrf

            <flux:heading size="lg">{{ __('Import BOQ CSV') }}</flux:heading>

            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('One row per item. Re-importing the same file updates existing items instead of duplicating them.') }}
            </flux:text>

            <label for="boq-csv-file" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                {{ __('BOQ CSV file') }}
            </label>
            <input
                id="boq-csv-file"
                type="file"
                name="file"
                accept=".csv,text/csv"
                required
                class="block w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm file:me-3 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-1 file:text-sm dark:file:bg-zinc-700"
            >

            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('Header row: header_code,header_title,clause_code,clause_title,item_code,description,unit,quantity,rate_paisa') }}
            </flux:text>

            <x-form-actions>
                <flux:button variant="ghost" wire:click="$set('showImport', false)">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" icon="arrow-up-tray">{{ __('Import') }}</flux:button>
            </x-form-actions>
        </form>
    </flux:modal>
</div>
