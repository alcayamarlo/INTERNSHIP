@extends('layouts.app')

@section('title', 'Audit & Security Log')

@section('content')

<x-page-header title="Audit and security log" :subtitle="'Every audited action and sign-in event. Entries are kept for '.$retentionDays.' days, then removed automatically.'">
    <a href="{{ route('admin.logs.export', request()->query()) }}" class="btn btn-outline-primary"><i class="bi bi-filetype-csv" aria-hidden="true"></i> Export CSV</a>
</x-page-header>

<ul class="nav nav-tabs mb-3">
    @foreach(['all' => 'All entries', 'security' => 'Security events', 'audit' => 'Admin and workflow actions'] as $key => $label)
        <li class="nav-item">
            <a class="nav-link {{ $filters['type'] === $key ? 'active' : '' }}" href="{{ route('admin.logs', [...array_filter(\Illuminate\Support\Arr::except($filters, ['type'])), 'type' => $key]) }}" @if($filters['type'] === $key) aria-current="page" @endif>{{ $label }}</a>
        </li>
    @endforeach
</ul>

<form method="GET" action="{{ route('admin.logs') }}" class="card mb-3" role="search" aria-label="Filter the log">
    <input type="hidden" name="type" value="{{ $filters['type'] }}">
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small" for="logSearch">User, email, IP, or detail</label>
            <input id="logSearch" type="search" name="q" value="{{ $filters['q'] }}" class="form-control" maxlength="100">
        </div>
        <div class="col-md-3">
            <label class="form-label small" for="logAction">Action</label>
            <select id="logAction" name="action" class="form-select">
                <option value="">Any action</option>
                @foreach($actions as $action)
                    <option value="{{ $action }}" @selected($filters['action'] === $action)>{{ $action }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small" for="logFrom">From</label>
            <input id="logFrom" type="date" name="from" value="{{ $filters['from'] }}" class="form-control @error('from') is-invalid @enderror">
        </div>
        <div class="col-md-2">
            <label class="form-label small" for="logTo">To</label>
            <input id="logTo" type="date" name="to" value="{{ $filters['to'] }}" class="form-control @error('to') is-invalid @enderror">
            @error('to')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill">Filter</button>
            <a href="{{ route('admin.logs', ['type' => $filters['type']]) }}" class="btn btn-outline-secondary">Clear</a>
        </div>
    </div>
</form>

@if($repeatedFailures->isNotEmpty())
    <div class="alert alert-danger" role="alert" data-repeated-failures>
        <strong><i class="bi bi-shield-exclamation" aria-hidden="true"></i> Repeated failed sign-ins in the last {{ \App\Models\SystemLog::REPEATED_FAILURE_HOURS }} hours</strong>
        <ul class="mb-0 mt-1">
            @foreach($repeatedFailures as $row)
                <li>{{ $row->ip_address ?: 'Unknown IP' }}: {{ $row->failures }} failures, last at {{ \Illuminate\Support\Carbon::parse($row->last_attempt)->timezone('Asia/Manila')->format('M d, h:i A') }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    @if($logs->count())
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th scope="col">Time (Philippine time)</th>
                        <th scope="col">User</th>
                        <th scope="col">Action</th>
                        <th scope="col">IP address</th>
                        <th scope="col">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td class="text-nowrap">{{ $log->created_at->timezone('Asia/Manila')->format('M d, Y h:i:s A') }}</td>
                            <td>{{ $log->user?->name ?? 'System / guest' }}@if($log->user)<small class="d-block text-muted">{{ $log->user->email }}</small>@endif</td>
                            <td>
                                <span class="badge {{ $log->isSecurityEvent() ? (in_array($log->action, ['login_failed', 'login_blocked', 'login_throttled', 'password_reset_failed'], true) ? 'text-bg-danger' : 'text-bg-warning') : 'text-bg-light border' }}">{{ $log->action }}</span>
                            </td>
                            <td>{{ $log->ip_address ?? '—' }}</td>
                            <td>
                                @if($log->details)
                                    <span class="text-muted small text-break">{{ \Illuminate\Support\Str::limit(json_encode($log->details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 120) }}</span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <x-empty-state icon="bi-journal-text" title="No entries match" message="Try a different filter or date range." />
    @endif

    @if($logs->hasPages())
        <div class="card-footer">{{ $logs->links() }}</div>
    @endif
</div>

@endsection
