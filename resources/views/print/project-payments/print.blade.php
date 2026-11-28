@extends('layouts.print', ['title' => __('Project payments')])

@section('content')
    <div class="muted">
        {{ $company->name }}
        @if ($company->pan) · PAN {{ $company->pan }} @endif
        · {{ __('Contractor payments') }}
    </div>
    <h1 style="margin: 4px 0 14px; font-size: 22px; letter-spacing: 0.04em;">
        {{ __('PROJECT PAYMENT SUMMARY') }}
    </h1>

    <p style="margin: 0 0 4px;">
        <strong>{{ $project->code }}</strong> — {{ $project->name }}
    </p>
    <p class="muted" style="margin: 0 0 14px;">
        {{ $project->client_name }}
        · {{ __('Printed') }}: <x-date :date="$generatedOn" />
    </p>

    <table>
        <thead>
            <tr>
                <th>{{ __('Agreed task value') }}</th>
                <th class="num">{{ __('Paid') }}</th>
                <th class="num">{{ __('Outstanding') }}</th>
                <th class="num">{{ __('Tasks') }}</th>
                <th class="num">{{ __('Contractors') }}</th>
                <th class="num">{{ __('Unassigned tasks') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong><x-money :paisa="$summary['agreed_paisa']" /></strong></td>
                <td class="num"><x-money :paisa="$summary['paid_paisa']" /></td>
                <td class="num"><x-money :paisa="$summary['balance_paisa']" /></td>
                <td class="num">{{ $summary['job_count'] }}</td>
                <td class="num">{{ $summary['contractor_count'] }}</td>
                <td class="num">{{ $summary['unassigned_job_count'] }}</td>
            </tr>
        </tbody>
    </table>

    <h2 style="margin: 20px 0 8px; font-size: 15px;">{{ __('By contractor') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('Contractor') }}</th>
                <th class="num">{{ __('Tasks') }}</th>
                <th class="num">{{ __('Agreed') }}</th>
                <th class="num">{{ __('Paid') }}</th>
                <th class="num">{{ __('Balance') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($summary['contractors'] as $row)
                <tr>
                    <td><strong>{{ $row['contractor']?->name ?? '—' }}</strong></td>
                    <td class="num">{{ $row['job_count'] }}</td>
                    <td class="num"><x-money :paisa="$row['agreed_paisa']" /></td>
                    <td class="num"><x-money :paisa="$row['paid_paisa']" /></td>
                    <td class="num"><x-money :paisa="$row['balance_paisa']" /></td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">{{ __('No contractors assigned in this project.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2 style="margin: 20px 0 8px; font-size: 15px;">{{ __('Tasks') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('Task') }}</th>
                <th>{{ __('Contractor') }}</th>
                <th class="num">{{ __('Agreed') }}</th>
                <th class="num">{{ __('Paid') }}</th>
                <th class="num">{{ __('Balance') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($summary['jobs'] as $row)
                <tr>
                    <td><strong>{{ $row['job']->title }}</strong></td>
                    <td>{{ $row['contractor']?->name ?? __('Unassigned') }}</td>
                    <td class="num"><x-money :paisa="$row['agreed_paisa']" /></td>
                    <td class="num"><x-money :paisa="$row['paid_paisa']" /></td>
                    <td class="num"><x-money :paisa="$row['balance_paisa']" /></td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">{{ __('No tasks yet.') }}</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2"><strong>{{ __('Totals') }}</strong></td>
                <td class="num"><strong><x-money :paisa="$summary['agreed_paisa']" /></strong></td>
                <td class="num"><strong><x-money :paisa="$summary['paid_paisa']" /></strong></td>
                <td class="num"><strong><x-money :paisa="$summary['balance_paisa']" /></strong></td>
            </tr>
        </tfoot>
    </table>

    <h2 style="margin: 20px 0 8px; font-size: 15px;">{{ __('Payments') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('Payment No') }}</th>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Task') }}</th>
                <th>{{ __('Contractor') }}</th>
                <th>{{ __('Method') }}</th>
                <th>{{ __('Reference') }}</th>
                <th class="num">{{ __('Amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payments as $payment)
                <tr>
                    <td>{{ $payment->payment_no }}</td>
                    <td><x-date :date="$payment->paid_on" /></td>
                    <td>{{ $payment->job?->title ?? __('General payment') }}</td>
                    <td>{{ $payment->contractor?->name ?? __('?') }}</td>
                    <td>{{ str($payment->method->value)->replace('_', ' ')->title() }}</td>
                    <td>{{ $payment->reference_no ?: '—' }}</td>
                    <td class="num"><x-money :paisa="$payment->amount_paisa" /></td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">{{ __('No payments recorded.') }}</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6"><strong>{{ __('Payments total') }}</strong></td>
                <td class="num"><strong><x-money :paisa="$paymentTotalPaisa" /></strong></td>
            </tr>
        </tfoot>
    </table>

    <p class="muted" style="margin-top: 14px;">
        {{ __('Balance is the agreed amount minus recorded payments. TDS, variations and hired-worker wages are excluded.') }}
    </p>

    <div class="signature" style="width: 45%; float: right;">
        {{ __('Prepared by') }}<br>
        ________________________
    </div>

    <div style="clear: both;"></div>
@endsection
