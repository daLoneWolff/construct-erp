@props(['pct' => 0])

@php
    $value = max(0, min(100, round((float) $pct, 2)));
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
        <div
            class="h-full rounded-full bg-brand-600 transition-[width] duration-200 ease-out"
            style="width: {{ $value }}%"
        ></div>
    </div>
</div>
