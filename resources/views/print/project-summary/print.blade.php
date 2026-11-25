@extends('layouts.print', ['title' => __('Project summary')])

@section('content')
    <div class="muted">{{ $company->name }} · {{ __('Project summary') }}</div>
    <h1 style="margin: 4px 0 16px; font-size: 22px; letter-spacing: 0.04em;">
        {{ __('PROJECT SUMMARY REPORT') }}
    </h1>

    <p class="muted">
        {{ $project ? $project->code.' — '.$project->name : __('All projects') }}
    </p>

    <table>
        <thead>
            <tr>
                <th>{{ __('Project') }}</th>
                <th>{{ __('Status') }}</th>
                <th class="num">{{ __('Contract value') }}</th>
                <th class="num">{{ __('BOQ value') }}</th>
                <th class="num">{{ __('Executed') }}</th>
                <th class="num">{{ __('Certified') }}</th>
                <th class="num">{{ __('Advance left') }}</th>
                <th class="num">{{ __('Milestones') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td><strong>{{ $row['code'] }}</strong><br>{{ $row['name'] }}</td>
                    <td>{{ str($row['status'])->replace('_', ' ')->title() }}</td>
                    <td class="num"><x-money :paisa="$row['contract_value_paisa']" /></td>
                    <td class="num"><x-money :paisa="$row['boq_value_paisa']" /></td>
                    <td class="num"><x-number :value="$row['executed_pct']" :decimals="2" suffix="%" /></td>
                    <td class="num"><x-money :paisa="$row['certified_paisa']" /></td>
                    <td class="num"><x-money :paisa="$row['advance_remaining_paisa']" /></td>
                    <td class="num"><x-number :value="$row['milestone_pct']" :decimals="2" suffix="%" /></td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">{{ __('No projects found.') }}</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2"><strong>{{ __('Totals') }}</strong></td>
                <td class="num"><strong><x-money :paisa="$totals['contract_value_paisa']" /></strong></td>
                <td class="num"><strong><x-money :paisa="$totals['boq_value_paisa']" /></strong></td>
                <td class="num">—</td>
                <td class="num"><strong><x-money :paisa="$totals['certified_paisa']" /></strong></td>
                <td class="num">—</td>
                <td class="num">—</td>
            </tr>
        </tfoot>
    </table>

    <div class="signature" style="width: 45%; float: right;">
        {{ __('Prepared by') }}<br>
        ________________________
    </div>

    <div style="clear: both;"></div>
@endsection
