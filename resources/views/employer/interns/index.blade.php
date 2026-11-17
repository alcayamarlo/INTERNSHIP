@extends('layouts.app')

@section('title', 'Interns')

@section('content')
<div class="sb-dash">
<x-page-header title="Interns" subtitle="Supervise accepted students: approve daily time records, certify weekly reports and submit evaluations." />

@php
    // HR (the company account) assigns supervisors; a supervisor only sees their own interns.
    $isHr = auth()->user()->isRole(\App\Enums\UserRole::Employer);
    $prefix = auth()->user()->role->ojtRoutePrefix();
@endphp
<nav class="sb-pill-tabs mb-0" aria-label="Filter interns">
    <a href="{{ route($prefix.'.interns.index', array_filter(['supervisor' => $supervisorFilter ?? null])) }}" class="sb-pill-tab {{ ! $status ? 'is-active' : '' }}" @if(! $status) aria-current="page" @endif>All</a>
    <a href="{{ route($prefix.'.interns.index', array_filter(['status' => 'active', 'supervisor' => $supervisorFilter ?? null])) }}" class="sb-pill-tab {{ $status === 'active' ? 'is-active' : '' }}" @if($status === 'active') aria-current="page" @endif>Ongoing</a>
    <a href="{{ route($prefix.'.interns.index', array_filter(['status' => 'completed', 'supervisor' => $supervisorFilter ?? null])) }}" class="sb-pill-tab {{ $status === 'completed' ? 'is-active' : '' }}" @if($status === 'completed') aria-current="page" @endif>Completed</a>
</nav>

@if($isHr)
    {{-- Filter by supervisor. "No supervisor assigned" lists the interns HR still covers itself. --}}
    <form method="GET" action="{{ route('employer.interns.index') }}" class="card sb-filterbar" role="search" aria-label="Filter by supervisor">
        @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
        <div class="sb-filter-row">
            <div class="sb-filter-field">
                <label class="form-label" for="internSupervisor">Supervisor</label>
                <select id="internSupervisor" name="supervisor" class="form-select" onchange="this.form.submit()">
                    <option value="">All interns</option>
                    <option value="none" @selected(($supervisorFilter ?? null) === 'none')>No supervisor assigned ({{ $withoutSupervisor }})</option>
                    @foreach($supervisors as $supervisor)
                        <option value="{{ $supervisor->user_id }}" @selected((string) ($supervisorFilter ?? '') === (string) $supervisor->user_id)>{{ $supervisor->user->name }}@unless($supervisor->user->is_active) (deactivated)@endunless</option>
                    @endforeach
                </select>
            </div>
            <noscript><div class="sb-filter-actions"><button type="submit" class="btn btn-primary">Filter</button></div></noscript>
        </div>
    </form>
    @if($withoutSupervisor > 0 && ($supervisorFilter ?? null) !== 'none')
        <p class="small text-muted mb-2"><i class="bi bi-info-circle" aria-hidden="true"></i> {{ $withoutSupervisor }} intern{{ $withoutSupervisor === 1 ? ' has' : 's have' }} no supervisor assigned, so your HR account handles them. <a href="{{ route('employer.interns.index', ['supervisor' => 'none']) }}">Show them</a></p>
    @endif

    {{-- Assign one supervisor to the ticked interns. The checkboxes belong to this form through their form attribute. --}}
    @if($supervisors->where('user.is_active', true)->isNotEmpty())
        <form method="POST" action="{{ route('employer.interns.supervisor.bulk') }}" id="bulkAssignForm" class="d-flex flex-wrap align-items-end gap-2 mb-2">
            @csrf
            <div>
                <label class="form-label mb-1" for="bulkSupervisor">Assign ticked interns to</label>
                <select id="bulkSupervisor" name="supervisor_user_id" class="form-select form-select-sm" required>
                    <option value="">Choose a supervisor</option>
                    @foreach($supervisors->where('user.is_active', true) as $supervisor)
                        <option value="{{ $supervisor->user_id }}">{{ $supervisor->user->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-sm btn-primary">Assign</button>
            @error('placements')<span class="text-danger small">{{ $message }}</span>@enderror
            @error('supervisor_user_id')<span class="text-danger small">{{ $message }}</span>@enderror
        </form>
    @endif
@endif

<div class="card" data-live-region="interns" data-live-topics="placements">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle sb-table-stack">
            <thead>
                <tr>
                    @if($isHr)<th scope="col"><input type="checkbox" class="form-check-input" aria-label="Tick all interns on this page" onclick="document.querySelectorAll('[data-live-hold]').forEach(function (b) { b.checked = this.checked; }, this)"></th>@endif
                    <th scope="col">Intern</th>
                    <th scope="col">Internship</th>
                    @if($isHr)<th scope="col">Supervisor</th>@endif
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
                        @if($isHr)<td data-label="Select"><input type="checkbox" class="form-check-input" name="placements[]" value="{{ $placement->id }}" form="bulkAssignForm" data-live-hold aria-label="Tick {{ $placement->company_profile['name'] ?? 'intern' }}"></td>@endif
                        <td class="sb-stack-block"><strong>{{ $placement->company_profile['name'] ?? '—' }}</strong><div class="small text-muted">{{ $placement->company_profile['program'] ?? '' }}</div></td>
                        <td data-label="Internship">{{ $placement->internship?->title }}</td>
                        @if($isHr)
                            @php $assigned = $placement->supervisorUser; @endphp
                            <td data-label="Supervisor">
                                @if(! $assigned)
                                    <span class="sb-status sb-status--warning">No supervisor assigned</span>
                                @elseif(! $assigned->is_active)
                                    <span class="sb-status sb-status--danger">Supervisor deactivated</span><small class="sb-row-sub">{{ $assigned->name }} · you cover this intern</small>
                                @else
                                    {{ $assigned->name }}
                                @endif
                                <form method="POST" action="{{ route('employer.interns.supervisor', $placement) }}" class="d-flex gap-1 mt-1">
                                    @csrf @method('PUT')
                                    <select name="supervisor_user_id" class="form-select form-select-sm" aria-label="Supervisor for {{ $placement->company_profile['name'] ?? 'this intern' }}">
                                        <option value="">No supervisor</option>
                                        @foreach($supervisors->where('user.is_active', true) as $supervisor)
                                            <option value="{{ $supervisor->user_id }}" @selected((int) $placement->supervisor_user_id === (int) $supervisor->user_id)>{{ $supervisor->user->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Save</button>
                                </form>
                            </td>
                        @endif
                        <td data-label="Hours" class="sb-stack-block sb-progress-cell">
                            <div class="small">{{ number_format($placement->approvedHours(), 1) }} / {{ $placement->required_hours }} h</div>
                            <div class="progress progress--thin" role="progressbar" aria-label="Hours progress" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: {{ $percent }}%"></div></div>
                        </td>
                        <td data-label="To review" class="small">
                            @if($placement->pending_logs_count)<span class="sb-status sb-status--warning">{{ $placement->pending_logs_count }} DTR</span>@endif
                            @if($placement->unread_journals_count)<span class="sb-status sb-status--info">{{ $placement->unread_journals_count }} journal</span>@endif
                            @if(! $placement->pending_logs_count && ! $placement->unread_journals_count)<span class="text-muted">—</span>@endif
                        </td>
                        <td data-label="Evaluation">{{ $placement->evaluation ? number_format((float) $placement->evaluation->average_score, 2).' / 5' : '—' }}</td>
                        <td data-label="Status"><x-status-badge :status="$placement->isCleared() ? 'verified' : ($placement->isCompleted() ? 'completed' : 'active')" :label="$placement->statusLabel()" /></td>
                        <td class="sb-stack-block text-nowrap"><a href="{{ route($prefix.'.interns.show', $placement) }}" class="btn btn-sm btn-outline-primary">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $isHr ? 9 : 7 }}" class="sb-stack-block"><x-empty-state icon="bi-person-workspace" title="No interns yet" message="Students appear here once you accept their application." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($placements->hasPages())<div class="card-footer">{{ $placements->links() }}</div>@endif
</div>
</div>
@endsection
