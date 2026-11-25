@extends('layouts.print', ['title' => __('Milestone progress')])

@section('content')
    <div class="muted">{{ $company->name }} · {{ __('Milestone progress') }}</div>
    <h1 style="margin: 4px 0 16px; font-size: 22px; letter-spacing: 0.04em;">
        {{ __('MILESTONE PROGRESS REPORT') }}
    </h1>

    <p class="muted">
        {{ $project ? $project->code.' — '.$project->name : __('All projects') }}
    </p>

    <table>
        <thead>
            <tr>
                <th>{{ __('Project') }}</th>
                <th class="num">{{ __('Milestones') }}</th>
                <th class="num">{{ __('Completed') }}</th>
                <th class="num">{{ __('Tasks done') }}</th>
                <th class="num">{{ __('Progress') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td><strong>{{ $row['code'] }}</strong><br>{{ $row['name'] }}</td>
                    <td class="num">{{ $row['milestone_count'] }}</td>
                    <td class="num">{{ $row['status_counts']['completed'] }}</td>
                    <td class="num">{{ $row['tasks_done'] }}/{{ $row['tasks_total'] }}</td>
                    <td class="num"><strong><x-number :value="$row['progress_pct']" :decimals="2" suffix="%" /></strong></td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">{{ __('No milestones found.') }}</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2"><strong>{{ __('Totals') }}</strong></td>
                <td class="num"><strong>{{ $totals['completed_count'] }}</strong></td>
                <td class="num"><strong>{{ $totals['tasks_done'] }}/{{ $totals['tasks_total'] }}</strong></td>
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
