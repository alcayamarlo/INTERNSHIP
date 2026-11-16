@extends('layouts.app')

@section('title', 'Usage & Health')

@section('content')
@php
    $bytes = fn (int $value) => \App\Http\Controllers\Admin\HealthController::bytes($value);
@endphp
<div class="sb-dash">
<x-page-header title="Usage and health" subtitle="How the platform is being used, and whether its parts are running." />

<section class="sb-dash" aria-labelledby="usageHeading">
    <div class="sb-section-head"><h2 id="usageHeading">Usage</h2></div>
    <div class="sb-grid sb-grid--3">
        <x-dashboard-stat-card title="Active (7 days)" :value="$usage['active_7']" icon="bi-person-check" tone="success" />
        <x-dashboard-stat-card title="Active (30 days)" :value="$usage['active_30']" icon="bi-people" tone="info" />
        <x-dashboard-stat-card title="New accounts (30 days)" :value="$usage['new_accounts_30']" icon="bi-person-plus" tone="info" />
        <x-dashboard-stat-card title="Applications (30 days)" :value="$usage['applications_30']" icon="bi-send" tone="info" />
        <x-dashboard-stat-card title="Uploads (30 days)" :value="$usage['uploads_30']" icon="bi-cloud-upload" tone="info" />
        <x-dashboard-stat-card title="Storage used" :value="$bytes($usage['storage_bytes'])" icon="bi-hdd" tone="warning" />
    </div>

    <div class="sb-split-table">
        <div class="card">
            <div class="card-header"><h3 class="h6 mb-0">Sign-ins, last 14 days</h3></div>
            <div class="card-body">
                @php
                    $max = max(1, max($usage['sign_ins']));
                @endphp
                {{-- --sb-bar is each day's share of the busiest day (0 to 1). --}}
                <div class="sb-minibars" role="img" aria-label="Sign-ins per day for the last 14 days">
                    @foreach($usage['sign_ins'] as $day => $count)
                        <div class="sb-minibar" style="--sb-bar: {{ round($count / $max, 3) }}" title="{{ \Illuminate\Support\Carbon::parse($day)->format('M d') }}: {{ $count }}">
                            <small>{{ $count ?: '' }}</small>
                            <span class="sb-minibar-fill"></span>
                            <small>{{ \Illuminate\Support\Carbon::parse($day)->format('d') }}</small>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="h6 mb-0">Accounts by role</h3></div>
            <ul class="list-group list-group-flush">
                @foreach($usage['users_by_role'] as $role => $count)
                    <li class="list-group-item d-flex justify-content-between gap-2"><span>{{ $role }}</span><strong>{{ number_format($count) }}</strong></li>
                @endforeach
                <li class="list-group-item d-flex justify-content-between gap-2"><span>Ongoing OJT placements</span><strong>{{ number_format($usage['active_placements']) }}</strong></li>
            </ul>
        </div>
    </div>
</section>

<section class="sb-dash" aria-labelledby="healthHeading">
    <div class="sb-section-head"><h2 id="healthHeading">Health</h2></div>
    @php
        $checks = [
            ['Database', $health['database']['ok'], $health['database']['detail']],
            ['Background jobs', ($health['queue']['failed'] ?? 0) === 0, 'Driver: '.$health['queue']['driver'].($health['queue']['pending'] !== null ? ' · '.$health['queue']['pending'].' waiting' : '').($health['queue']['failed'] !== null ? ' · '.$health['queue']['failed'].' failed' : '')],
            ['Scheduler', $health['scheduler']['ok'], $health['scheduler']['last_run'] ? 'Last ran '.$health['scheduler']['last_run']->timezone('Asia/Manila')->format('M d, Y h:i A').' ('.$health['scheduler']['last_run']->diffForHumans().')' : 'Has not run yet. Start it with "php artisan schedule:work" or a cron job.'],
            ['Recent errors', count($health['errors']) === 0, count($health['errors']) ? count($health['errors']).' recent error'.(count($health['errors']) === 1 ? '' : 's').' in the log' : 'None in the latest log entries'],
        ];
    @endphp
    <div class="sb-kpis">
        @foreach($checks as [$label, $ok, $detail])
            <div class="card sb-kpi" data-health-check="{{ \Illuminate\Support\Str::slug($label) }}">
                <i class="bi {{ $ok ? 'bi-check-circle' : 'bi-exclamation-triangle' }}" aria-hidden="true"></i>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <strong>{{ $label }}</strong>
                    <x-status-badge :status="$ok ? 'success' : 'danger'" :label="$ok ? 'OK' : 'Needs attention'" />
                </div>
                <p class="sb-row-sub mb-0 text-break">{{ $detail }}</p>
            </div>
        @endforeach
    </div>

    <div class="sb-split-table">
        <div class="card">
            <div class="card-header"><h3 class="h6 mb-0">Latest errors (from the application log)</h3></div>
            <ul class="list-group list-group-flush small">
                @forelse($health['errors'] as $error)
                    <li class="list-group-item"><span class="sb-status sb-status--danger">{{ $error['level'] }}</span> <span class="text-muted">{{ $error['at'] }}</span><div class="text-break">{{ $error['message'] }}</div></li>
                @empty
                    <li class="list-group-item text-muted">No errors found.</li>
                @endforelse
            </ul>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="h6 mb-0">Environment</h3></div>
            <ul class="list-group list-group-flush small">
                @foreach($health['environment'] as $label => $value)
                    <li class="list-group-item d-flex justify-content-between gap-2"><span>{{ $label }}</span><strong class="text-break text-end {{ $value === 'Missing' ? 'text-danger' : '' }}">{{ $value }}</strong></li>
                @endforeach
            </ul>
        </div>
    </div>
</section>
</div>
@endsection
