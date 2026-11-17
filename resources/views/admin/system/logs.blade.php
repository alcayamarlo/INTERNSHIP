@extends('layouts.app')

@section('title', 'Audit & Security Log')

@section('content')
<div class="sb-dash">
<x-page-header title="Audit and security log" :subtitle="'Every audited action and sign-in event. Entries are kept for '.$retentionDays.' days, then removed automatically.'">
    <a href="{{ route('admin.logs.export', request()->query()) }}" class="btn btn-outline-primary"><i class="bi bi-filetype-csv" aria-hidden="true"></i> Export CSV</a>
</x-page-header>

<nav class="sb-section-tabs mb-0" aria-label="Log type">
    @foreach(['all' => 'All entries', 'security' => 'Security events', 'audit' => 'Admin and workflow actions'] as $key => $label)
        <a class="sb-section-tab {{ $filters['type'] === $key ? 'active' : '' }}" href="{{ route('admin.logs', [...array_filter(\Illuminate\Support\Arr::except($filters, ['type'])), 'type' => $key]) }}" @if($filters['type'] === $key) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>

<form method="GET" action="{{ route('admin.logs') }}" class="card sb-filterbar" role="search" aria-label="Filter the log">
    <input type="hidden" name="type" value="{{ $filters['type'] }}">
    <div class="sb-filter-row">
        <div class="sb-filter-field">
            <label class="form-label" for="logSearch">User, email, IP, or detail</label>
            <input id="logSearch" type="search" name="q" value="{{ $filters['q'] }}" class="form-control" maxlength="100">
        </div>
        <div class="sb-filter-field">
            <label class="form-label" for="logAction">Action</label>
            <select id="logAction" name="action" class="form-select" onchange="this.form.requestSubmit()">
                <option value="">Any action</option>
                @foreach($actions as $action)
                    <option value="{{ $action }}" @selected($filters['action'] === $action)>{{ $action }}</option>
                @endforeach
            </select>
        </div>
        <div class="sb-filter-field sb-filter-field--sm">
            <label class="form-label" for="logFrom">From</label>
            {{-- Apply once the date is complete (year 1900+), or when it is cleared. --}}
            <input id="logFrom" type="date" name="from" value="{{ $filters['from'] }}" class="form-control @error('from') is-invalid @enderror" onchange="if (this.value === '' || this.value.slice(0, 4) >= '1900') this.form.requestSubmit()">
        </div>
        <div class="sb-filter-field sb-filter-field--sm">
            <label class="form-label" for="logTo">To</label>
            <input id="logTo" type="date" name="to" value="{{ $filters['to'] }}" class="form-control @error('to') is-invalid @enderror" onchange="if (this.value === '' || this.value.slice(0, 4) >= '1900') this.form.requestSubmit()">
            @error('to')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="sb-filter-actions">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('admin.logs', ['type' => $filters['type']]) }}" class="btn btn-outline-secondary">Clear</a>
        </div>
    </div>
</form>

@if($repeatedFailures->isNotEmpty())
    <div class="alert alert-danger mb-0" role="alert" data-repeated-failures>
        <strong><i class="bi bi-shield-exclamation" aria-hidden="true"></i> Repeated failed sign-ins in the last {{ \App\Models\SystemLog::REPEATED_FAILURE_HOURS }} hours</strong>
        <ul class="mb-0 mt-1">
            @foreach($repeatedFailures as $row)
                <li>{{ \App\Support\IpHash::label($row->ip_address) }}: {{ $row->failures }} failures, last at {{ \Illuminate\Support\Carbon::parse($row->last_attempt)->timezone('Asia/Manila')->format('M d, h:i A') }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    @if($logs->count())
        <div class="table-responsive">
            <table class="table table-hover mb-0 sb-table-stack">
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
                            <td class="sb-stack-block text-nowrap"><span class="sb-row-title">{{ $log->created_at->timezone('Asia/Manila')->format('M d, Y h:i:s A') }}</span></td>
                            <td data-label="User" class="sb-stack-block text-break">{{ $log->user?->name ?? 'System / guest' }}@if($log->user)<small class="sb-row-sub">{{ $log->user->email }}</small>@endif</td>
                            <td data-label="Action">
                                <span class="sb-status {{ $log->isSecurityEvent() ? (in_array($log->action, ['login_failed', 'login_blocked', 'login_throttled', 'password_reset_failed'], true) ? 'sb-status--danger' : 'sb-status--warning') : 'sb-status--neutral' }}">{{ $log->action }}</span>
                            </td>
                            <td data-label="IP address">{{ \App\Support\IpHash::label($log->ip_address) }}</td>
                            <td data-label="Details" class="sb-stack-block">
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
</div>
@endsection
