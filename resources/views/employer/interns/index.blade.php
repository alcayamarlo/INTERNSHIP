@extends('layouts.app')

@section('title', 'Interns')

@section('content')
<x-page-header title="Interns" subtitle="Supervise accepted students: approve daily time records, read journals and submit evaluations." />

<div class="mb-3 d-flex gap-2 flex-wrap">
    <a href="{{ route('employer.interns.index') }}" class="btn btn-sm {{ ! $status ? 'btn-primary' : 'btn-outline-primary' }}">All</a>
    <a href="{{ route('employer.interns.index', ['status' => 'active']) }}" class="btn btn-sm {{ $status === 'active' ? 'btn-primary' : 'btn-outline-primary' }}">Ongoing</a>
    <a href="{{ route('employer.interns.index', ['status' => 'completed']) }}" class="btn btn-sm {{ $status === 'completed' ? 'btn-primary' : 'btn-outline-primary' }}">Completed</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle sb-table-stack">
            <thead>
                <tr>
                    <th scope="col">Intern</th>
                    <th scope="col">Internship</th>
                    <th scope="col">Hours</th>
                    <th scope="col">To review</th>
                    <th scope="col">Evaluation</th>
                    <th scope="col">Status</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse($placements as $placement)
                    @php $percent = $placement->progressPercent(); @endphp
                    <tr>
                        <td class="sb-stack-block"><strong>{{ $placement->student?->user?->name ?? '—' }}</strong><div class="small text-muted">{{ $placement->student?->program }}</div></td>
                        <td data-label="Internship">{{ $placement->internship?->title }}</td>
                        <td data-label="Hours" class="sb-stack-block" style="min-width: 150px;">
                            <div class="small">{{ number_format($placement->approvedHours(), 1) }} / {{ $placement->required_hours }} h</div>
                            <div class="progress" style="height: 6px;" role="progressbar" aria-label="Hours progress" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: {{ $percent }}%"></div></div>
                        </td>
                        <td data-label="To review" class="small">
                            @if($placement->pending_logs_count)<span class="badge bg-warning text-dark">{{ $placement->pending_logs_count }} DTR</span>@endif
                            @if($placement->unread_journals_count)<span class="badge bg-info text-dark">{{ $placement->unread_journals_count }} journal</span>@endif
                            @if(! $placement->pending_logs_count && ! $placement->unread_journals_count)<span class="text-muted">—</span>@endif
                        </td>
                        <td data-label="Evaluation">{{ $placement->evaluation ? number_format((float) $placement->evaluation->average_score, 2).' / 5' : '—' }}</td>
                        <td data-label="Status"><x-status-badge :status="$placement->isCleared() ? 'verified' : ($placement->isCompleted() ? 'completed' : 'active')" :label="$placement->statusLabel()" /></td>
                        <td class="sb-stack-block"><a href="{{ route('employer.interns.show', $placement) }}" class="btn btn-sm btn-outline-primary">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="sb-stack-block"><x-empty-state icon="bi-person-workspace" title="No interns yet" message="Students appear here once you accept their application." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($placements->hasPages())<div class="card-footer">{{ $placements->links() }}</div>@endif
</div>
@endsection
