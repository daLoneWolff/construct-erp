@extends('layouts.print', ['title' => __('Attendance summary')])

@section('content')
    <div class="muted">{{ $company->name }} · {{ __('Attendance summary') }}</div>
    <h1 style="margin: 4px 0 16px; font-size: 22px; letter-spacing: 0.04em;">
        {{ __('ATTENDANCE SUMMARY REPORT') }}
    </h1>

    <p class="muted">
        {{ $project ? $project->code.' — '.$project->name : __('No project selected') }}
        · <x-date :date="$from" /> — <x-date :date="$to" />
    </p>

    <table style="margin-bottom: 12px;">
        <tr>
            <td class="num" style="border: 0; padding-left: 0;">{{ __('Present') }}: <strong>{{ $summary['present'] }}</strong></td>
            <td class="num" style="border: 0;">{{ __('Absent') }}: <strong>{{ $summary['absent'] }}</strong></td>
            <td class="num" style="border: 0;">{{ __('Half day') }}: <strong>{{ $summary['half_day'] }}</strong></td>
            <td class="num" style="border: 0;">{{ __('Leave') }}: <strong>{{ $summary['leave'] }}</strong></td>
            <td class="num" style="border: 0; padding-right: 0;">
                {{ __('Overtime') }}: <strong><x-number :value="$summary['overtime_hours']" :decimals="1" /> h</strong>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>{{ __('Employee') }}</th>
                <th>{{ __('Trade') }}</th>
                <th class="num">{{ __('Present') }}</th>
                <th class="num">{{ __('Absent') }}</th>
                <th class="num">{{ __('Half day') }}</th>
                <th class="num">{{ __('Leave') }}</th>
                <th class="num">{{ __('Overtime (h)') }}</th>
                <th class="num">{{ __('Days recorded') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td><strong>{{ $row['name'] }}</strong><br>{{ $row['employee_code'] }}</td>
                    <td>{{ $row['trade'] }}</td>
                    <td class="num">{{ $row['present'] }}</td>
                    <td class="num">{{ $row['absent'] }}</td>
                    <td class="num">{{ $row['half_day'] }}</td>
                    <td class="num">{{ $row['leave'] }}</td>
                    <td class="num"><x-number :value="$row['overtime_hours']" :decimals="1" /></td>
                    <td class="num">{{ $row['days'] }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">{{ __('No attendance found in this range.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature" style="width: 45%; float: right;">
        {{ __('Prepared by') }}<br>
        ________________________
    </div>

    <div style="clear: both;"></div>
@endsection
