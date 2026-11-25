@extends('layouts.print', ['title' => $bill->code])

@section('content')
    <div class="muted">{{ $company->name }} · {{ $company->fiscal_year }}</div>
    <h1 style="margin: 4px 0 18px; font-size: 22px; letter-spacing: 0.04em;">
        {{ __('RUNNING BILL') }}
    </h1>

    <table style="border: 0; margin-bottom: 14px;">
        <tr>
            <td style="border: 0; padding-left: 0;">Bill No: <strong>{{ $bill->code }}</strong></td>
            <td style="border: 0;">Bill #{{ $bill->bill_number }}</td>
            <td style="border: 0;">Status: {{ str($bill->status->value)->title() }}</td>
            <td style="border: 0; padding-right: 0;">Certified: <x-date :date="$bill->certified_date" /></td>
        </tr>
        <tr>
            <td style="border: 0; padding-left: 0;" colspan="2">Project: {{ $project->code }} — {{ $project->name }}</td>
            <td style="border: 0;" colspan="2">Period: <x-date :date="$bill->period_from" /> — <x-date :date="$bill->period_to" /></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="num" style="width: 30%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Work certified</td>
                <td class="num"><x-money :paisa="$bill->work_certified_paisa" /></td>
            </tr>
            <tr>
                <td>Advance adjusted</td>
                <td class="num">(<x-money :paisa="$totals['advance_adjusted_paisa']" />)</td>
            </tr>
            <tr>
                <td><strong>Net payable</strong></td>
                <td class="num"><strong><x-money :paisa="$totals['net_payable_paisa']" /></strong></td>
            </tr>
        </tbody>
    </table>

    <div class="rule"></div>

    <table style="border: 0;">
        <tr>
            <td style="border: 0; padding-left: 0;">
                <div class="muted">Formula</div>
                <x-money :paisa="$bill->work_certified_paisa" />
                − <x-money :paisa="$totals['advance_adjusted_paisa']" />
                = <strong><x-money :paisa="$totals['net_payable_paisa']" /></strong>
            </td>
            <td style="border: 0; padding-right: 0;">
                <div class="muted">Amount in words</div>
                {{ $amountInWords }}
            </td>
        </tr>
    </table>

    @if ($bill->notes)
        <p><span class="muted">Notes:</span> {{ $bill->notes }}</p>
    @endif

    <div class="signature" style="width: 45%; float: right;">
        Prepared by / Certified by<br>
        ________________________
    </div>

    <div style="clear: both;"></div>
@endsection
