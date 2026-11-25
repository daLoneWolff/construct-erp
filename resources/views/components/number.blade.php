@props(['value', 'decimals' => 0, 'suffix' => ''])

<span {{ $attributes }}>{{ \App\Support\DecimalFormat::format($value, (int) $decimals) }}{{ $suffix }}</span>
