@extends('layouts.print', ['title' => $po->code])

@section('content')
    <div class="muted">{{ $company->fiscal_year }}</div>
    <h1 style="margin: 4px 0 18px; font-size: 22px; letter-spacing: 0.04em;">
        कर बीजक / TAX INVOICE
    </h1>

    <table style="border: 0; margin-bottom: 18px;">
        <tr>
            <td style="width: 50%; border: 0; padding-left: 0; vertical-align: top;">
                <div class="muted">Seller</div>
                <strong>{{ $company->name }}</strong><br>
                PAN: {{ $company->pan ?: '—' }} &nbsp; VAT: {{ $company->vat ?: '—' }}<br>
                {{ $company->address }} {{ $company->district }}
            </td>
            <td style="width: 50%; border: 0; padding-right: 0; vertical-align: top;">
                <div class="muted">Buyer</div>
                <strong>{{ $po->vendor->name }}</strong><br>
                PAN: {{ $po->vendor->pan }} &nbsp; VAT: {{ $po->vendor->vat ?: '—' }}<br>
                {{ $po->vendor->address }}
            </td>
        </tr>
    </table>

    <table style="border: 0; margin-bottom: 14px;">
        <tr>
            <td style="border: 0; padding-left: 0;">Invoice No: <strong>{{ $po->code }}</strong></td>
            <td style="border: 0;">Date: <x-date :date="$po->order_date" /></td>
            <td style="border: 0; padding-right: 0;">Project: {{ $po->project->code }} — {{ $po->project->name }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 8%;">#</th>
                <th>Description</th>
                <th class="num" style="width: 12%;">Qty</th>
                <th class="num" style="width: 12%;">Rate</th>
                <th class="num" style="width: 16%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        {{ $item->description }}
                        @if ($item->boqItem)
                            <div class="muted">BOQ {{ $item->boqItem->code }}</div>
                        @endif
                    </td>
                    <td class="num">{{ $item->quantity }} {{ $item->unit }}</td>
                    <td class="num"><x-money :paisa="$item->rate_paisa" /></td>
                    <td class="num"><x-money :paisa="$item->amount_paisa" /></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table style="margin-top: 14px; border: 0;">
        <tr>
            <td style="border: 0;"></td>
            <td class="num" style="border: 0; width: 42%;">
                <div>Subtotal: <x-money :paisa="$totals['subtotal_paisa']" /></div>
                <div>VAT @ {{ $vatRate }}%: <x-money :paisa="$totals['vat_paisa']" /></div>
                <div class="rule"></div>
                <div><strong>Invoice Total: <x-money :paisa="$totals['invoice_total_paisa']" /></strong></div>
                <div class="muted">
                    TDS withheld @ {{ $po->vendor->tds_rate }}%:
                    <x-money :paisa="$totals['tds_paisa']" /> (deducted at payment)
                </div>
                <div class="rule"></div>
                <div><strong>Net Payable to Vendor: <x-money :paisa="$totals['net_payable_paisa']" /></strong></div>
            </td>
        </tr>
    </table>

    <p style="margin-top: 18px;">
        <strong>Amount in words:</strong> {{ $amountInWords }}
    </p>

    <table style="border: 0; margin-top: 48px;">
        <tr>
            <td class="signature" style="width: 50%; border: 0;">Prepared by</td>
            <td class="signature" style="width: 50%; border: 0;">Authorised signatory</td>
        </tr>
    </table>
@endsection
