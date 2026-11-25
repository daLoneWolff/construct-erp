@props(['paisa' => null, 'symbol' => true])

@if ($paisa === null || $paisa === '')
    <span {{ $attributes }}>—</span>
@else
    <span {{ $attributes }}>{{ \App\Support\Npr::format((int) $paisa, (bool) $symbol) }}</span>
@endif
