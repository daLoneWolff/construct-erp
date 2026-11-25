@extends('layouts.print', ['title' => $payment->payment_no])

@section('content')
    <div class="muted">
        {{ $company->name }}
        @if ($company->pan) · PAN {{ $company->pan }} @endif
        · {{ $company->fiscal_year }}
    </div>
    <h1 style="margin: 4px 0 18px; font-size: 22px; letter-spacing: 0.04em;">
        {{ __('PAYMENT RECEIPT') }}
    </h1>

    <table style="border: 0; margin-bottom: 14px;">
        <tr>
            <td style="border: 0; padding-left: 0;">
                {{ __('Payment No') }}: <strong>{{ $payment->payment_no }}</strong>
            </td>
            <td style="border: 0;">
                {{ __('Paid on') }}: <x-date :date="$payment->paid_on" />
            </td>
            <td style="border: 0;">
                {{ __('Method') }}: {{ str($payment->method->value)->replace('_', ' ')->title() }}
            </td>
            <td style="border: 0; padding-right: 0;">
                {{ __('Reference') }}: {{ $payment->reference_no ?: '—' }}
            </td>
        </tr>
    </table>

    <table>
        <tbody>
            <tr>
                <th style="width: 22%;">{{ __('Project') }}</th>
                <td>
                    @if ($project)
                        <strong>{{ $project->code }}</strong> — {{ $project->name }}
                    @else
                        —
                    @endif
                </td>
            </tr>
            <tr>
                <th>{{ __('Task') }}</th>
                <td>{{ $job?->title ?: '—' }}</td>
            </tr>
            <tr>
                <th>{{ __('Contractor') }}</th>
                <td>
                    {{ $contractor?->name ?: '—' }}
                    @if ($contractor?->pan)
                        <span class="muted">· PAN {{ $contractor->pan }}</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>{{ __('Recorded by') }}</th>
                <td>{{ $payment->createdBy?->name ?: '—' }}</td>
            </tr>
        </tbody>
    </table>

    <table style="margin-top: 16px;">
        <thead>
            <tr>
                <th>{{ __('Amount paid') }}</th>
                <th class="num">{{ __('Job agreed') }}</th>
                <th class="num">{{ __('Job paid to date') }}</th>
                <th class="num">{{ __('Job balance') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong><x-money :paisa="$payment->amount_paisa" /></strong></td>
                <td class="num"><x-money :paisa="$jobTotals['agreed_paisa'] ?? null" /></td>
                <td class="num"><x-money :paisa="$jobTotals['paid_paisa'] ?? null" /></td>
                <td class="num"><x-money :paisa="$jobTotals['balance_paisa'] ?? null" /></td>
            </tr>
        </tbody>
    </table>

    @if ($payment->note)
        <p style="margin-top: 14px;">
            <strong>{{ __('Note') }}:</strong> {{ $payment->note }}
        </p>
    @endif

    <p class="muted" style="margin-top: 16px;">
        {{ __('Job balance is the agreed amount minus all recorded payments. TDS, variations and hired-worker wages are excluded.') }}
    </p>

    <div class="signature" style="width: 45%; float: left;">
        {{ __('Received by') }}<br>
        ________________________
    </div>

    <div class="signature" style="width: 45%; float: right;">
        {{ __('Paid by') }}<br>
        ________________________
    </div>

    <div style="clear: both;"></div>
@endsection
