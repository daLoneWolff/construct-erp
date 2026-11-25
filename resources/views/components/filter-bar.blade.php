<div {{ $attributes->merge(['class' => 'flex flex-col gap-3 border-b border-border px-5 py-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="flex flex-1 flex-wrap items-end gap-3">
        {{ $slot }}
    </div>

    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
