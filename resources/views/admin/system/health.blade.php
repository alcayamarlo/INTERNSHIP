@extends('layouts.app')

@section('title', 'Usage & Health')

@section('content')
@php
    $bytes = fn (int $value) => \App\Http\Controllers\Admin\HealthController::bytes($value);
@endphp
<x-page-header title="Usage and health" subtitle="How the platform is being used, and whether its parts are running." />

<h2 class="h5 mb-3">Usage</h2>
<div class="row g-3 mb-4 sb-usage">
    <div class="col-6 col-md-4 col-xl-2"><x-dashboard-stat-card title="Active (7 days)" :value="$usage['active_7']" icon="bi-person-check" tone="success" /></div>
    <div class="col-6 col-md-4 col-xl-2"><x-dashboard-stat-card title="Active (30 days)" :value="$usage['active_30']" icon="bi-people" tone="info" /></div>
    <div class="col-6 col-md-4 col-xl-2"><x-dashboard-stat-card title="New accounts (30 days)" :value="$usage['new_accounts_30']" icon="bi-person-plus" tone="info" /></div>
    <div class="col-6 col-md-4 col-xl-2"><x-dashboard-stat-card title="Applications (30 days)" :value="$usage['applications_30']" icon="bi-send" tone="info" /></div>
    <div class="col-6 col-md-4 col-xl-2"><x-dashboard-stat-card title="Uploads (30 days)" :value="$usage['uploads_30']" icon="bi-cloud-upload" tone="info" /></div>
    <div class="col-6 col-md-4 col-xl-2"><x-dashboard-stat-card title="Storage used" :value="$bytes($usage['storage_bytes'])" icon="bi-hdd" tone="warning" /></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header"><h3 class="h6 mb-0">Sign-ins, last 14 days</h3></div>
            <div class="card-body">
                @php
                    $max = max(1, max($usage['sign_ins']));
                @endphp
                <div class="d-flex align-items-end gap-1" style="height: 160px;" role="img" aria-label="Sign-ins per day for the last 14 days">
                    @foreach($usage['sign_ins'] as $day => $count)
                        <div class="flex-fill d-flex flex-column align-items-center justify-content-end h-100" title="{{ \Illuminate\Support\Carbon::parse($day)->format('M d') }}: {{ $count }}">
                            <small class="text-muted">{{ $count ?: '' }}</small>
                            <div class="w-100 rounded-top" style="height: {{ max(2, round($count / $max * 120)) }}px; background: var(--sb-brand, #c8102e);"></div>
                            <small class="text-muted" style="font-size: .7rem;">{{ \Illuminate\Support\Carbon::parse($day)->format('d') }}</small>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="h6 mb-0">Accounts by role</h3></div>
            <ul class="list-group list-group-flush">
                @foreach($usage['users_by_role'] as $role => $count)
                    <li class="list-group-item d-flex justify-content-between"><span>{{ $role }}</span><strong>{{ number_format($count) }}</strong></li>
                @endforeach
                <li class="list-group-item d-flex justify-content-between"><span>Ongoing OJT placements</span><strong>{{ number_format($usage['active_placements']) }}</strong></li>
            </ul>
        </div>
    </div>
</div>

<h2 class="h5 mb-3">Health</h2>
<div class="row g-3 mb-4">
    @php
        $checks = [
            ['Database', $health['database']['ok'], $health['database']['detail']],
            ['Background jobs', ($health['queue']['failed'] ?? 0) === 0, 'Driver: '.$health['queue']['driver'].($health['queue']['pending'] !== null ? ' · '.$health['queue']['pending'].' waiting' : '').($health['queue']['failed'] !== null ? ' · '.$health['queue']['failed'].' failed' : '')],
            ['Scheduler', $health['scheduler']['ok'], $health['scheduler']['last_run'] ? 'Last ran '.$health['scheduler']['last_run']->timezone('Asia/Manila')->format('M d, Y h:i A').' ('.$health['scheduler']['last_run']->diffForHumans().')' : 'Has not run yet. Start it with "php artisan schedule:work" or a cron job.'],
            ['Recent errors', count($health['errors']) === 0, count($health['errors']) ? count($health['errors']).' recent error'.(count($health['errors']) === 1 ? '' : 's').' in the log' : 'None in the latest log entries'],
        ];
    @endphp
    @foreach($checks as [$label, $ok, $detail])
        <div class="col-md-6 col-xl-3">
            <div class="card h-100 {{ $ok ? 'border-success' : 'border-warning' }}" data-health-check="{{ \Illuminate\Support\Str::slug($label) }}">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi {{ $ok ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-warning' }}" aria-hidden="true"></i>
                        <strong>{{ $label }}</strong>
                        <span class="visually-hidden">{{ $ok ? 'OK' : 'Needs attention' }}</span>
                    </div>
                    <p class="small text-muted mb-0">{{ $detail }}</p>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="h6 mb-0">Latest errors (from the application log)</h3></div>
            <ul class="list-group list-group-flush small">
                @forelse($health['errors'] as $error)
                    <li class="list-group-item"><span class="badge text-bg-danger">{{ $error['level'] }}</span> <span class="text-muted">{{ $error['at'] }}</span><div class="text-break">{{ $error['message'] }}</div></li>
                @empty
                    <li class="list-group-item text-muted">No errors found.</li>
                @endforelse
            </ul>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="h6 mb-0">Environment</h3></div>
            <ul class="list-group list-group-flush small">
                @foreach($health['environment'] as $label => $value)
                    <li class="list-group-item d-flex justify-content-between"><span>{{ $label }}</span><strong class="{{ $value === 'Missing' ? 'text-danger' : '' }}">{{ $value }}</strong></li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
