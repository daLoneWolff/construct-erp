<div class="space-y-5">
    <x-page-header
        title="{{ __('Documents') }}"
        description="{{ __('Metadata-only mock uploads. No file is stored on the server.') }}"
    >
        <x-slot:actions>
            @can('write')
                <flux:button icon="paper-clip" variant="primary" wire:click="openUpload">
                    {{ __('Upload document') }}
                </flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-panel flush>
        <x-filter-bar>
            <flux:select wire:model.live="category" :label="__('Category')" class="w-full sm:max-w-xs">
                <flux:select.option value="">{{ __('All categories') }}</flux:select.option>
                @foreach ($categories as $option)
                    <flux:select.option :value="$option->value">
                        {{ str($option->value)->replace('_', ' ')->title() }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="projectId" :label="__('Project')" class="w-full sm:max-w-sm">
                <flux:select.option value="">{{ __('All projects') }}</flux:select.option>
                @foreach ($projects as $project)
                    <flux:select.option :value="$project->id">
                        {{ $project->code }} — {{ $project->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </x-filter-bar>

        <div class="overflow-x-auto border-t border-border">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Title') }}</flux:table.column>
                    <flux:table.column>{{ __('Category') }}</flux:table.column>
                    <flux:table.column>{{ __('Project') }}</flux:table.column>
                    <flux:table.column>{{ __('Filename') }}</flux:table.column>
                    <flux:table.column align="end" class="text-right">{{ __('Size') }}</flux:table.column>
                    <flux:table.column>{{ __('Uploaded by') }}</flux:table.column>
                    <flux:table.column>{{ __('Uploaded') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($documents as $document)
                        <flux:table.row :key="$document->id">
                            <flux:table.cell class="font-medium">{{ $document->title }}</flux:table.cell>
                            <flux:table.cell>
                                <x-status-badge tone="neutral" size="sm">
                                    {{ str($document->category->value)->replace('_', ' ')->title() }}
                                </x-status-badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $document->project?->code ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:text class="font-mono text-xs">{{ $document->filename }}</flux:text>
                                <flux:text class="block font-mono text-xs text-zinc-500">{{ $document->path_mock }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">
                                {{ $document->size_label }}
                            </flux:table.cell>
                            <flux:table.cell>{{ $document->uploader?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell><x-date :date="$document->created_at" :time="true" /></flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7">
                                <x-empty-state variant="table" title="{{ __('No documents found') }}" description="{{ __('Upload a document to capture its metadata.') }}" />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        @if ($documents->hasPages())
            <div class="border-t border-border px-5 py-3">
                {{ $documents->links() }}
            </div>
        @endif
    </x-panel>

    <flux:modal wire:model="showUpload" class="md:w-[36rem]">
        <form wire:submit="saveUpload" class="space-y-4">
            <flux:heading size="lg">{{ __('Mock document upload') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Select files to capture their metadata. The browser sends name, size, and MIME only.') }}
            </flux:text>

            <flux:input wire:model="title" :label="__('Title')" required />
            <flux:select wire:model="uploadCategory" :label="__('Category')" required>
                @foreach ($categories as $option)
                    <flux:select.option :value="$option->value">
                        {{ str($option->value)->replace('_', ' ')->title() }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="uploadProjectId" :label="__('Project')">
                <flux:select.option value="">{{ __('Not project-specific') }}</flux:select.option>
                @foreach ($projects as $project)
                    <flux:select.option :value="$project->id">
                        {{ $project->code }} — {{ $project->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <div>
                <label for="document-files" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Files') }}
                </label>
                <input
                    id="document-files"
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
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-border bg-surface-subtle px-3 py-2 text-sm">
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

            <x-form-actions>
                <flux:button variant="ghost" x-on:click="$wire.showUpload = false">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" icon="check">{{ __('Save metadata') }}</flux:button>
            </x-form-actions>
        </form>
    </flux:modal>
</div>
