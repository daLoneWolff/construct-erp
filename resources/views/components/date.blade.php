@props(['date' => null, 'withBadge' => false, 'time' => false])

@php
    $status = $withBadge && $date !== null
        ? app(\App\Services\CalculationService::class)->vehicleDocumentStatus(
            \Illuminate\Support\Carbon::parse($date)
        )
        : null;
@endphp

<span {{ $attributes }}>
    @if ($date === null || $date === '')
        —
    @else
        {{ \Illuminate\Support\Carbon::parse($date)->format($time ? 'd/m/Y H:i' : 'd/m/Y') }}
        @if ($status !== null && $status['badge'] !== 'none')
            <flux:badge
                size="sm"
                :color="match ($status['badge']) {
                    'red' => 'red',
                    'yellow' => 'amber',
                    default => 'lime',
                }"
            >
                @if ($status['days'] < 0)
                    {{ abs($status['days']) }}d overdue
                @else
                    {{ $status['days'] }}d left
                @endif
            </flux:badge>
        @endif
    @endif
</span>
