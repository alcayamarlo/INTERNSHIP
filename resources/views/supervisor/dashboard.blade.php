@extends('layouts.app')

@section('title', 'Supervisor Dashboard')

@section('content')
<div class="sb-dash">
<x-page-header eyebrow="Supervisor workspace" title="Dashboard" :subtitle="trim(($supervisor->position ? $supervisor->position.' · ' : '').($supervisor->employer?->company_name ?? ''))" />

<div class="mb-4"><x-dashboard-ojt /></div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0"><i class="bi bi-person-workspace" aria-hidden="true"></i> Your interns</h2>
        <a href="{{ route('supervisor.interns.index') }}" class="sb-card-link">See all →</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle sb-table-stack">
            <thead>
                <tr><th scope="col">Intern</th><th scope="col">Internship</th><th scope="col">Hours</th><th scope="col">To review</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr>
            </thead>
            <tbody>
                @forelse($placements as $placement)
                    <tr>
                        <td class="sb-stack-block"><strong>{{ $placement->student?->user?->name ?? 'Intern' }}</strong><small class="sb-row-sub">{{ $placement->statusLabel() }}</small></td>
                        <td data-label="Internship">{{ $placement->internship?->title }}</td>
                        <td data-label="Hours" class="small">{{ number_format($placement->approvedHours(), 1) }} / {{ $placement->required_hours }} h</td>
                        <td data-label="To review" class="small">
                            @if($placement->pending_logs_count)<span class="sb-status sb-status--warning">{{ $placement->pending_logs_count }} DTR</span>@endif
                            @if($placement->unread_journals_count)<span class="sb-status sb-status--info">{{ $placement->unread_journals_count }} report</span>@endif
                            @if(! $placement->pending_logs_count && ! $placement->unread_journals_count)<span class="text-muted">—</span>@endif
                        </td>
                        <td class="sb-stack-block text-nowrap"><a href="{{ route('supervisor.interns.show', $placement) }}" class="btn btn-sm btn-outline-primary">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="sb-stack-block"><x-empty-state icon="bi-person-workspace" title="No interns assigned yet" message="Your HR account assigns interns to you. They appear here once they do." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection
