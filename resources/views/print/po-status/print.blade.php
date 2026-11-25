@extends('layouts.print', ['title' => __('PO status')])

@section('content')
    <div class="muted">{{ $company->name }} · {{ __('PO status') }}</div>
    <h1 style="margin: 4px 0 16px; font-size: 22px; letter-spacing: 0.04em;">
        {{ __('PURCHASE ORDER STATUS REPORT') }}
    </h1>

    <p class="muted">
        {{ $project ? $project->code.' — '.$project->name : __('All projects') }}
        · <x-date :date="$from" /> — <x-date :date="$to" />
    </p>

    <table>
        <thead>
            <tr>
                <th>{{ __('PO') }}</th>
                <th>{{ __('Project') }}</th>
                <th>{{ __('Vendor') }}</th>
                <th>{{ __('Order date') }}</th>
                <th>{{ __('Expected') }}</th>
                <th>{{ __('Status') }}</th>
                <th class="num">{{ __('Net payable') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td><strong>{{ $row['code'] }}</strong></td>
                    <td>{{ $row['project_code'] }}</td>
                    <td>{{ $row['vendor_name'] }}</td>
                    <td><x-date :date="$row['order_date']" /></td>
                    <td><x-date :date="$row['expected_delivery']" /></td>
                    <td>{{ str($row['status'])->replace('_', ' ')->title() }}</td>
                    <td class="num"><x-money :paisa="$row['total_paisa']" /></td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">{{ __('No purchase orders in this range.') }}</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6"><strong>{{ __('Total') }} · {{ $totals['po_count'] }} {{ __('orders') }}</strong></td>
                <td class="num"><strong><x-money :paisa="$totals['net_payable_paisa']" /></strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="signature" style="width: 45%; float: right;">
        {{ __('Prepared by') }}<br>
        ________________________
    </div>

    <div style="clear: both;"></div>
@endsection
