@extends('layouts.print', ['title' => __('Contractor ledger')])

@section('content')
    <div class="muted">
        {{ $company->name }}
        @if ($company->pan) · PAN {{ $company->pan }} @endif
        · {{ __('Contractor ledger') }}
    </div>
    <h1 style="margin: 4px 0 14px; font-size: 22px; letter-spacing: 0.04em;">
        {{ __('CONTRACTOR LEDGER') }}
    </h1>

    <p style="margin: 0 0 4px;">
        <strong>{{ $ledger['contractor']->name }}</strong>
        @if ($ledger['contractor']->phone)
            <span class="muted">· {{ $ledger['contractor']->phone }}</span>
        @endif
        @if ($ledger['contractor']->pan)
            <span class="muted">· PAN {{ $ledger['contractor']->pan }}</span>
        @endif
    </p>

    <p class="muted" style="margin: 0 0 14px;">
        {{ __('Scope') }}: {{ $project?->name ?? __('All projects') }}
        · {{ __('Payment dates') }}:
        @if ($from)
            <x-date :date="$from" />
        @else
            {{ __('Any') }}
        @endif
        —
        @if ($to)
            <x-date :date="$to" />
        @else
            {{ __('Any') }}
        @endif
        · {{ __('Printed') }}: <x-date :date="$generatedOn" />
    </p>

    <table>
        <thead>
            <tr>
                <th>{{ __('Agreed task value') }}</th>
                <th class="num">{{ __('Paid') }}</th>
                <th class="num">{{ __('Outstanding') }}</th>
                <th class="num">{{ __('Tasks') }}</th>
                <th class="num">{{ __('Payments in scope') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong><x-money :paisa="$ledger['agreed_paisa']" /></strong></td>
                <td class="num"><x-money :paisa="$ledger['paid_paisa']" /></td>
                <td class="num"><x-money :paisa="$ledger['balance_paisa']" /></td>
                <td class="num">{{ $jobCount }}</td>
                <td class="num">{{ $ledger['payment_count'] }}</td>
            </tr>
        </tbody>
    </table>

    <h2 style="margin: 20px 0 8px; font-size: 15px;">{{ __('Assigned tasks') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('Task') }}</th>
                <th>{{ __('Project') }}</th>
                <th>{{ __('Status') }}</th>
                <th class="num">{{ __('Agreed') }}</th>
                <th class="num">{{ __('Paid') }}</th>
                <th class="num">{{ __('Balance') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($ledger['jobs'] as $row)
                <tr>
                    <td><strong>{{ $row['job']->title }}</strong></td>
                    <td>{{ $row['project']?->code ?? '—' }}</td>
                    <td>{{ str($row['job']->status->value)->replace('_', ' ')->title() }}</td>
                    <td class="num"><x-money :paisa="$row['agreed_paisa']" /></td>
                    <td class="num"><x-money :paisa="$row['paid_paisa']" /></td>
                    <td class="num"><x-money :paisa="$row['balance_paisa']" /></td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">{{ __('No assigned tasks.') }}</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3"><strong>{{ __('Totals') }}</strong></td>
                <td class="num"><strong><x-money :paisa="$ledger['agreed_paisa']" /></strong></td>
                <td class="num"><strong><x-money :paisa="$ledger['paid_paisa']" /></strong></td>
                <td class="num"><strong><x-money :paisa="$ledger['balance_paisa']" /></strong></td>
            </tr>
        </tfoot>
    </table>

    <h2 style="margin: 20px 0 8px; font-size: 15px;">{{ __('Payments in scope') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('Payment No') }}</th>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Project') }}</th>
                <th>{{ __('Task') }}</th>
                <th>{{ __('Method') }}</th>
                <th>{{ __('Reference') }}</th>
                <th class="num">{{ __('Amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($ledger['payments'] as $payment)
                <tr>
                    <td class="font-mono">{{ $payment->payment_no }}</td>
                    <td><x-date :date="$payment->paid_on" /></td>
                    <td>{{ $payment->project?->name ?? '—' }}</td>
                    <td>{{ $payment->job?->title ?? '—' }}</td>
                    <td>{{ str($payment->method->value)->replace('_', ' ')->title() }}</td>
                    <td>{{ $payment->reference_no ?: '—' }}</td>
                    <td class="num"><x-money :paisa="$payment->amount_paisa" /></td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">{{ __('No payments in this scope.') }}</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6"><strong>{{ __('Selected payments total') }}</strong></td>
                <td class="num"><strong><x-money :paisa="$ledger['filtered_paid_paisa']" /></strong></td>
            </tr>
        </tfoot>
    </table>

    <p class="muted" style="margin-top: 14px;">
        {{ __('Date filters affect only the payment list. Selecting a project scopes the tasks and balances shown.') }}
    </p>

    <div class="signature" style="width: 45%; float: right;">
        {{ __('Contractor signature') }}<br>
        ________________________
    </div>

    <div style="clear: both;"></div>
@endsection
