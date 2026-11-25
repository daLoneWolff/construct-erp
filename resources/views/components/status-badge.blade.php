@props(['tone' => 'neutral', 'size' => 'sm'])

@php
    $color = match ($tone) {
        'success' => 'lime',
        'warning' => 'amber',
        'danger' => 'red',
        'info' => 'blue',
        default => 'zinc',
    };
@endphp

<flux:badge :color="$color" :size="$size" {{ $attributes }}>
    {{ $slot }}
</flux:badge>
