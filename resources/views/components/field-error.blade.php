@props(['message' => null])

@if ($message)
    <flux:text {{ $attributes->merge(['class' => 'text-xs text-danger']) }}>
        {{ $message }}
    </flux:text>
@endif
