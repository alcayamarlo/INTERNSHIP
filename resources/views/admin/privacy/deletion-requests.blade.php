@extends('layouts.app')

@section('title', 'Deletion requests')

@section('content')
<div class="sb-dash">
<x-page-header title="Account deletion requests" subtitle="Approving anonymises the student's account and cannot be undone. An encrypted backup is made first; if it fails, nothing is deleted." />

@forelse($pending as $request)
    @php $user = $request->user; $p = $request->preview; @endphp
    <div class="card" data-deletion-request="{{ $request->id }}">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-2">
                <div>
                    <h2 class="h6 mb-0">{{ $user->name }}</h2>
                    <div class="small text-muted">{{ $user->student?->student_id_number ?? 'No school ID' }} · {{ $user->email ?? 'no email' }}</div>
                </div>
                <div class="small text-muted text-md-end">
                    {{ $request->source === 'retention' ? 'Opened by '.($request->requestedBy?->name ?? 'an admin').' (retention review)' : 'Requested by the student' }}<br>
                    {{ $request->created_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}
                </div>
            </div>
            @if($request->reason)<p class="small mt-2 mb-0"><strong>Reason:</strong> {{ $request->reason }}</p>@endif

            <ul class="small mt-3 mb-2">
                <li><strong>Removed:</strong> profile and contact details; {{ $p['records_removed'] }} records (certificates, resumes, portfolio, competencies, skills, references, ...); {{ $p['files'] }} files; {{ $p['messages'] }} messages.</li>
                <li><strong>Kept, anonymous:</strong> {{ $p['placements_kept'] }} OJT placement{{ $p['placements_kept'] === 1 ? '' : 's' }}, {{ $p['hours_kept'] }} hours, reports and evaluations.</li>
            </ul>
            @if($p['active_placements'] > 0)
                <div class="alert alert-warning small" role="alert" data-active-ojt-warning><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> This student has an OJT in progress ({{ $p['active_placements'] }}). Check with their coordinator before approving.</div>
            @endif

            <div class="d-flex flex-wrap gap-3 align-items-start mt-3">
                <details>
                    <summary class="btn btn-danger btn-sm">Approve and anonymise...</summary>
                    <form method="POST" action="{{ route('admin.deletion-requests.approve', $request) }}" class="mt-2" style="max-width: 28rem">
                        @csrf
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="confirm" value="1" id="confirm{{ $request->id }}" required>
                            <label class="form-check-label small" for="confirm{{ $request->id }}">I understand this anonymises {{ $user->name }} for good. A backup is made first.</label>
                        </div>
                        <label class="form-label small" for="pw{{ $request->id }}">Your password</label>
                        <input id="pw{{ $request->id }}" type="password" name="password" class="form-control form-control-sm mb-2" autocomplete="current-password" required>
                        <button type="submit" class="btn btn-danger btn-sm" data-loading-text="Making a backup, then anonymising... this can take a minute">Anonymise this account</button>
                    </form>
                </details>
                <details>
                    <summary class="btn btn-outline-secondary btn-sm">Reject...</summary>
                    <form method="POST" action="{{ route('admin.deletion-requests.reject', $request) }}" class="mt-2" style="max-width: 28rem">
                        @csrf
                        <label class="form-label small" for="note{{ $request->id }}">Note for the student <span class="text-muted">(optional)</span></label>
                        <textarea id="note{{ $request->id }}" name="note" rows="2" maxlength="500" class="form-control form-control-sm mb-2"></textarea>
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Reject request</button>
                    </form>
                </details>
            </div>
        </div>
    </div>
@empty
    <div class="card"><x-empty-state icon="bi-person-check" title="No requests waiting" message="Account deletion requests from students, and ones opened from the Retention Review, appear here." /></div>
@endforelse

@if($history->isNotEmpty())
<div class="card">
    <div class="card-header"><h2 class="h6 mb-0">Decided</h2></div>
    <div class="table-responsive">
        <table class="table mb-0 sb-table-stack">
            <thead><tr><th scope="col">When</th><th scope="col">Outcome</th><th scope="col">By</th><th scope="col">Backup made first</th></tr></thead>
            <tbody>
                @foreach($history as $item)
                    <tr>
                        <td data-label="When">{{ ($item->decided_at ?? $item->updated_at)->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td>
                        <td data-label="Outcome">{{ ucfirst($item->status) }}@if($item->summary) <small class="sb-row-sub">{{ array_sum($item->summary['removed'] ?? []) }} records and {{ $item->summary['files_deleted'] ?? 0 }} files removed; {{ $item->summary['hours_kept'] ?? 0 }} OJT hours kept</small>@endif</td>
                        <td data-label="By">{{ $item->decidedBy?->name ?? 'The student' }}</td>
                        <td data-label="Backup">{{ $item->backup_name ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
</div>
@endsection
