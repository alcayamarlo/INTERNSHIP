@extends('layouts.app')

@section('title', 'Data access')

@section('content')
<div class="sb-dash">
<x-page-header title="Data access" :subtitle="'Who opened a student\'s profile, files or OJT forms. Kept for '.$keepDays.' days. The same person opening the same thing within '.config('security.access_log.dedupe_minutes').' minutes is listed once.'" />

<form method="GET" action="{{ route('admin.data-access') }}" class="card sb-filterbar" role="search" aria-label="Filter data access">
    <div class="sb-filter-row">
        <div class="sb-filter-field">
            <label class="form-label" for="daStudent">Student name or school ID</label>
            <input id="daStudent" type="search" name="student" value="{{ $filters['student'] }}" class="form-control" maxlength="100">
        </div>
        <div class="sb-filter-field">
            <label class="form-label" for="daViewer">Opened by (name or email)</label>
            <input id="daViewer" type="search" name="viewer" value="{{ $filters['viewer'] }}" class="form-control" maxlength="100">
        </div>
        <div class="sb-filter-field">
            <label class="form-label" for="daAction">What</label>
            <select id="daAction" name="action" class="form-select">
                <option value="">Anything</option>
                @foreach(\App\Models\DataAccessLog::ACTIONS as $key => $label)
                    <option value="{{ $key }}" @selected($filters['action'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="sb-filter-field sb-filter-field--sm">
            <label class="form-label" for="daFrom">From</label>
            <input id="daFrom" type="date" name="from" value="{{ $filters['from'] }}" class="form-control @error('from') is-invalid @enderror">
        </div>
        <div class="sb-filter-field sb-filter-field--sm">
            <label class="form-label" for="daTo">To</label>
            <input id="daTo" type="date" name="to" value="{{ $filters['to'] }}" class="form-control @error('to') is-invalid @enderror">
            @error('to')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="sb-filter-actions">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('admin.data-access') }}" class="btn btn-outline-secondary">Clear</a>
        </div>
    </div>
</form>

<div class="card">
    @if($logs->count())
        <div class="table-responsive">
            <table class="table table-hover mb-0 sb-table-stack" data-access-log>
                <thead>
                    <tr>
                        <th scope="col">Time (Philippine time)</th>
                        <th scope="col">Opened by</th>
                        <th scope="col">Student</th>
                        <th scope="col">What</th>
                        <th scope="col">IP code</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td class="sb-stack-block text-nowrap"><span class="sb-row-title">{{ $log->created_at->timezone('Asia/Manila')->format('M d, Y h:i:s A') }}</span></td>
                            <td data-label="Opened by" class="sb-stack-block text-break">
                                {{ $log->user?->name ?? 'Deleted account' }}
                                <small class="sb-row-sub">{{ ucfirst($log->viewer_role === 'employer' ? 'HR / company' : $log->viewer_role) }}@if($log->user) · {{ $log->user->email }}@endif</small>
                            </td>
                            <td data-label="Student" class="sb-stack-block">
                                @if($log->student)
                                    {{ $log->student->user?->name }}<small class="sb-row-sub">{{ $log->student->student_id_number }}</small>
                                @else
                                    <span class="text-muted">{{ $log->action === 'export' ? 'Several students' : '—' }}</span>
                                @endif
                            </td>
                            <td data-label="What">{{ $log->label() }}@if($log->subject_type && $log->action !== 'profile')<small class="sb-row-sub">{{ \Illuminate\Support\Str::headline($log->subject_type) }} #{{ $log->subject_id }}</small>@endif</td>
                            <td data-label="IP code">{{ \App\Support\IpHash::label($log->ip_address) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <x-empty-state icon="bi-eye" title="Nothing recorded yet" message="Views of student profiles, files and OJT forms by staff and companies appear here." />
    @endif

    @if($logs->hasPages())
        <div class="card-footer">{{ $logs->links() }}</div>
    @endif
</div>
</div>
@endsection
