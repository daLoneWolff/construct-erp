@extends('layouts.print', ['title' => __('Reconciliation report')])

@section('content')
    <div class="muted">{{ $company->name }} · {{ __('Reconciliation report') }}</div>
    <h1 style="margin: 4px 0 16px; font-size: 22px; letter-spacing: 0.04em;">
        {{ __('MATERIAL RECONCILIATION REPORT') }}
    </h1>

    <p class="muted">
        {{ $project ? $project->code.' — '.$project->name : __('No project selected') }}
    </p>

    <table style="margin-bottom: 12px;">
        <tr>
            <td class="num" style="border: 0; padding-left: 0;">
                {{ __('Over consumed') }}: <strong>{{ $summary['over_consumed'] }}</strong>
            </td>
            <td class="num" style="border: 0;">
                {{ __('Shortage') }}: <strong>{{ $summary['shortage'] }}</strong>
            </td>
            <td class="num" style="border: 0;">
                {{ __('OK') }}: <strong>{{ $summary['ok'] }}</strong>
            </td>
            <td class="num" style="border: 0; padding-right: 0;">
                {{ __('Net variance') }}: <strong><x-number :value="$summary['total_variance_qty']" :decimals="2" /></strong>
            </td>
        </tr>
    </table>

    <p class="muted">
        {{ __('Consumption is drawn from issued material. Variance is BOQ quantity minus issued quantity.') }}
    </p>

    <table>
        <thead>
            <tr>
                <th>{{ __('BOQ item') }}</th>
                <th class="num">{{ __('BOQ qty') }}</th>
                <th class="num">{{ __('Requisitioned') }}</th>
                <th class="num">{{ __('Received') }}</th>
                <th class="num">{{ __('Issued') }}</th>
                <th class="num">{{ __('Consumed') }}</th>
                <th class="num">{{ __('Variance') }}</th>
                <th>{{ __('Status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td><strong>{{ $row['code'] }}</strong><br>{{ $row['description'] }} · {{ $row['unit'] }}</td>
                    <td class="num"><x-number :value="$row['boq_qty']" :decimals="2" /></td>
                    <td class="num"><x-number :value="$row['requisitioned_qty']" :decimals="2" /></td>
                    <td class="num"><x-number :value="$row['received_qty']" :decimals="2" /></td>
                    <td class="num"><x-number :value="$row['issued_qty']" :decimals="2" /></td>
                    <td class="num"><x-number :value="$row['consumed_qty']" :decimals="2" /></td>
                    <td class="num"><strong><x-number :value="$row['variance_qty']" :decimals="2" /></strong></td>
                    <td>{{ str($row['status'])->replace('_', ' ')->title() }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">{{ __('No material movement found.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature" style="width: 45%; float: right;">
        {{ __('Prepared by') }}<br>
        ________________________
    </div>

    <div style="clear: both;"></div>
@endsection
