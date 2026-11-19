@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="sb-dash">
<x-page-header :title="$title" :subtitle="$intro" />

@php
    // Section-specific columns; the last column is always what still needs to be done or improved (OjtOverview).
    $columns = [
        'progress' => ['Hours', 'Dates'],
        'time-records' => ['Last time record', 'Approved hours', 'Waiting for approval'],
        'weekly-reports' => ['Submitted', 'Certified', 'Latest report'],
        'monthly-evaluations' => ['Latest evaluation', 'Where it stands', 'Done'],
        'final-evaluations' => ['Status', 'Score', 'Due'],
    ][$section];
    $colspan = 1 + count($columns) + ($section === 'progress' && $isHr ? 1 : 0) + 2;
    $toneLabel = ['danger' => 'Late', 'warning' => 'To do', 'info' => 'Note'];
@endphp

<div class="card" data-live-region="ojt-overview-{{ $section }}" data-live-topics="placements">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle sb-table-stack">
            <thead>
                <tr>
                    <th scope="col">Intern</th>
                    @if($section === 'progress' && $isHr)<th scope="col">Supervisor</th>@endif
                    @foreach($columns as $column)<th scope="col">{{ $column }}</th>@endforeach
                    <th scope="col">What needs to improve</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php
                        $placement = $row['placement'];
                        $cells = $row['cells'];
                        $name = $placement->company_profile['name'] ?? $placement->student?->user?->name ?? 'Intern';
                    @endphp
                    <tr>
                        <td class="sb-stack-block">
                            <strong>{{ $name }}</strong>
                            <div class="small text-muted">{{ $placement->internship?->title }}@if($placement->isCompleted()) · Completed @endif</div>
                        </td>

                        @if($section === 'progress')
                            @if($isHr)
                                @php $assigned = $placement->supervisorUser; @endphp
                                <td data-label="Supervisor">
                                    @if(! $assigned)<span class="sb-status sb-status--warning">No supervisor assigned</span>
                                    @elseif(! $assigned->is_active)<span class="sb-status sb-status--danger">Supervisor deactivated</span>
                                    @else{{ $assigned->name }}@endif
                                </td>
                            @endif
                            <td data-label="Hours" class="sb-stack-block sb-progress-cell">
                                <div class="small">{{ number_format($cells['hours'], 1) }} / {{ $cells['required'] }} h · {{ $cells['percent'] }}%</div>
                                <div class="progress progress--thin" role="progressbar" aria-label="Hours progress of {{ $name }}" aria-valuenow="{{ $cells['percent'] }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: {{ $cells['percent'] }}%"></div></div>
                                @if($cells['pending_hours'] > 0)<small class="sb-row-sub">{{ rtrim(rtrim(number_format($cells['pending_hours'], 1), '0'), '.') }} h waiting for approval</small>@endif
                            </td>
                            <td data-label="Dates" class="small">{{ $cells['started']?->format('M j, Y') ?? '—' }} to {{ $cells['ends']?->format('M j, Y') ?? 'ongoing' }}</td>

                        @elseif($section === 'time-records')
                            <td data-label="Last time record">{{ $cells['last']?->format('M j, Y') ?? '—' }}</td>
                            <td data-label="Approved hours">{{ number_format($cells['approved_hours'], 1) }} h</td>
                            <td data-label="Waiting for approval">{{ $cells['pending'] ?: '—' }}</td>

                        @elseif($section === 'weekly-reports')
                            <td data-label="Submitted">{{ $cells['submitted'] }}</td>
                            <td data-label="Certified">{{ $cells['certified'] }}</td>
                            <td data-label="Latest report">
                                @if($cells['latest'])
                                    Week {{ $cells['latest']->week_number ?? '—' }}<small class="sb-row-sub">{{ $cells['latest']->isCertified() ? 'Certified' : 'Waiting to be certified' }}</small>
                                @else — @endif
                            </td>

                        @elseif($section === 'monthly-evaluations')
                            @php $latest = $cells['latest']; @endphp
                            <td data-label="Latest evaluation">
                                @if($latest){{ $latest->period_label }}<small class="sb-row-sub">{{ $latest->period_month?->format('F Y') }}</small>@else — @endif
                            </td>
                            <td data-label="Where it stands">
                                @if($latest){{ $latest->stageLabel() }}@if($latest->due_on && ! $latest->isComplete())<small class="sb-row-sub">Due {{ $latest->due_on->format('M j, Y') }}</small>@endif @else — @endif
                            </td>
                            <td data-label="Done">{{ $cells['complete'] }} of {{ $cells['total'] }}</td>

                        @else
                            @php $evaluation = $cells['evaluation']; @endphp
                            <td data-label="Status">
                                @if($evaluation)<span class="sb-status sb-status--success">Submitted</span>
                                @elseif($cells['requested_at'])<span class="sb-status sb-status--warning">Requested</span>
                                @else<span class="sb-status sb-status--neutral">Not requested</span>@endif
                            </td>
                            <td data-label="Score">{{ $evaluation ? number_format((float) $evaluation->average_score, 2).' / 5' : '—' }}</td>
                            <td data-label="Due">{{ $cells['due_on']?->format('M j, Y') ?? '—' }}</td>
                        @endif

                        <td data-label="What needs to improve" class="sb-stack-block">
                            @if($row['needs'] === [])
                                <span class="sb-status sb-status--success"><i class="bi bi-check-lg" aria-hidden="true"></i> Nothing needed</span>
                            @else
                                <ul class="list-unstyled small mb-0 d-grid gap-1">
                                    @foreach($row['needs'] as $need)
                                        <li class="d-flex gap-2 align-items-start">
                                            <span class="sb-status sb-status--{{ $need['tone'] }} flex-shrink-0">{{ $toneLabel[$need['tone']] ?? 'Note' }}</span>
                                            <span>{{ $need['text'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                        <td class="sb-stack-block text-nowrap"><a href="{{ route($prefix.'.interns.show', ['placement' => $placement, 'tab' => $tab]) }}" class="btn btn-sm btn-outline-primary">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $colspan }}" class="sb-stack-block"><x-empty-state :icon="$icon" title="No interns yet" :message="$isHr ? 'Students appear here once you accept their application.' : 'Interns appear here once your HR account assigns them to you.'" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($placements->hasPages())<div class="card-footer">{{ $placements->links() }}</div>@endif
</div>
</div>
@endsection
