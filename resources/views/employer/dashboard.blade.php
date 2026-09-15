@extends('layouts.app')

@section('title', 'Employer Dashboard')

@push('styles')
<style>
    .employer-dashboard-shell { padding: 10px 0; color: #edf8ff; }
    .employer-dashboard-header { position: relative; display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 25px; }
    .employer-dashboard-header::before { content: "EMPLOYER WORKSPACE"; position: absolute; margin-top: -70px; color: #31d9f4; font-size: .68rem; font-weight: 800; letter-spacing: .16em; }
    .employer-dashboard-header h1 { margin: 0; color: #f4f9ff; font-size: clamp(2.1rem,2.5vw,3.3rem); font-weight: 800; letter-spacing: -.05em; }
    .employer-dashboard-header p { margin: 7px 0 0; color: rgba(186,211,228,.82); font-size: 1.05rem; }
    .employer-dashboard-status { display: flex; align-items: center; gap: 7px; margin-top: 10px; color: rgba(175,205,222,.76); font-size: .68rem; font-weight: 700; }
    .employer-dashboard-status::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: #45df9a; box-shadow: 0 0 10px rgba(69,223,154,.7); }
    .employer-dashboard-shell .card { overflow: hidden; border: 1px solid rgba(117,176,215,.18); border-radius: 18px; background: linear-gradient(145deg,rgba(13,35,54,.96),rgba(8,22,37,.96)); box-shadow: 0 10px 28px rgba(2,9,20,.18); }
    .employer-dashboard-shell .card:hover { border-color: rgba(49,217,244,.36); }
    .employer-dashboard-shell .card-header { padding: .95rem 1.1rem; border-bottom: 1px solid rgba(117,176,215,.14); background: rgba(14,35,51,.9) !important; color: #edf8ff; }
    .employer-dashboard-shell .card-header h5 { margin: 0; color: #edf8ff; font-size: .92rem; font-weight: 750; }
    .employer-dashboard-shell .card-header h5 i { margin-right: 7px; color: #31d9f4; }
    .employer-dashboard-shell .card-body { padding: 1.15rem; }
    .employer-dashboard-shell .card .h3 { color: #f3f9ff; font-weight: 800; letter-spacing: -.04em; }
    .employer-dashboard-shell > .row:first-of-type .card { position: relative; background: linear-gradient(145deg,rgba(14,39,60,.98),rgba(8,22,37,.96)); }
    .employer-dashboard-shell > .row:first-of-type .card::before { content: ""; position: absolute; inset: 0 0 auto; height: 2px; background: linear-gradient(90deg,transparent,rgba(49,217,244,.95),transparent); }
    .employer-dashboard-shell > .row:first-of-type .col-md-6:nth-child(2) .card::before { background: linear-gradient(90deg,transparent,rgba(138,169,255,.95),transparent); }
    .employer-dashboard-shell > .row:first-of-type .col-md-6:nth-child(3) .card::before { background: linear-gradient(90deg,transparent,rgba(251,191,36,.95),transparent); }
    .employer-dashboard-shell > .row:first-of-type .col-md-6:nth-child(4) .card::before { background: linear-gradient(90deg,transparent,rgba(69,223,154,.95),transparent); }
    .employer-dashboard-shell > .row:first-of-type .card .h3 { font-size: 2.15rem; }
    .employer-dashboard-shell .text-primary { color: #31d9f4 !important; }
    .employer-dashboard-shell .text-accent { color: #8aa9ff !important; }
    .employer-dashboard-shell .text-warning { color: #fbbf24 !important; }
    .employer-dashboard-shell .text-success { color: #45df9a !important; }
    .employer-dashboard-shell .text-muted, .employer-dashboard-shell small { color: rgba(170,200,218,.78) !important; }
    .employer-dashboard-shell .list-group-item { border-color: rgba(117,176,215,.1); background: transparent; color: #dcebf5; transition: background .18s ease, padding-left .18s ease; }
    .employer-dashboard-shell .list-group-item:hover { background: rgba(49,217,244,.06); padding-left: 1.25rem; }
    .employer-dashboard-shell .list-group-item i { width: 24px; color: #31d9f4; }
    .employer-dashboard-shell .list-group-item-action { display: flex; align-items: center; min-height: 54px; font-size: 1rem; }
    .employer-dashboard-shell .list-group-item-action::after { content: ""; width: 7px; height: 7px; margin-left: auto; border-top: 1px solid #7fe7f8; border-right: 1px solid #7fe7f8; transform: rotate(45deg); opacity: .65; }
    .employer-dashboard-shell .progress { height: 7px !important; overflow: hidden; border-radius: 999px; background: rgba(120,148,175,.18); }
    .employer-dashboard-shell .progress-bar { border-radius: 999px; }
    .employer-dashboard-shell .table { --bs-table-bg: transparent; --bs-table-color: rgba(216,232,243,.9); --bs-table-border-color: rgba(117,176,215,.1); }
    .employer-dashboard-shell .table thead th { background: rgba(12,29,41,.9) !important; color: rgba(185,211,228,.78) !important; border-color: rgba(117,176,215,.12); font-size: .68rem; letter-spacing: .05em; text-transform: uppercase; }
    .employer-dashboard-shell .table td { color: rgba(216,232,243,.9) !important; border-color: rgba(117,176,215,.08); vertical-align: middle; }
    .employer-dashboard-shell .table tbody tr:hover { background: rgba(49,217,244,.045); }
    .employer-dashboard-shell .btn-primary { border: 0; background: linear-gradient(135deg,#29d4ff,#25c7ff) !important; color: #062338 !important; font-weight: 700; box-shadow: 0 9px 20px rgba(37,194,255,.18); }
    .employer-dashboard-shell .btn-outline-primary { border-color: rgba(77,210,255,.55); color: #7fe0ff; }
    .employer-dashboard-shell .status-label { color: #dcebf5; font-weight: 650; }
    .employer-dashboard-shell .status-row { padding: 10px 0; border-bottom: 1px solid rgba(117,176,215,.08); }
    .employer-dashboard-shell .status-row:last-child { padding-bottom: 0; border-bottom: 0; }
    .employer-dashboard-shell .status-count { min-width: 28px; text-align: center; }
    @media (max-width: 767.98px) { .employer-dashboard-header { align-items: flex-start; flex-direction: column; } }
</style>
@endpush

@section('content')
<div class="employer-dashboard-shell">
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 mb-1">Welcome, {{ Auth::user()->name }}!</h1>
            <p class="text-muted mb-0">Manage your internship listings and applicants</p>
            <div class="employer-dashboard-status">Recruitment workspace ready</div>
        </div>
        <a href="{{ route('employer.internships.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Post Internship</a>
    </div>
</div>

<!-- Dashboard Statistics -->
<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3 text-muted small">Active Listings</h6>
                <div class="d-flex align-items-center gap-3">
                    <div class="h3 mb-0 text-primary">{{ $activeInternships }}</div>
                    <div class="text-muted small">
                        <i class="bi bi-briefcase"></i>
                    </div>
                </div>
                <small class="text-muted d-block mt-2">
                    <a href="{{ route('employer.internships.index') }}" class="text-decoration-none">Manage internships →</a>
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3 text-muted small">Total Applicants</h6>
                <div class="d-flex align-items-center gap-3">
                    <div class="h3 mb-0 text-accent">{{ $applicationStats['total'] }}</div>
                    <div class="text-muted small">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
                <small class="text-muted d-block mt-2">
                    <a href="{{ route('employer.applicants.index') }}" class="text-decoration-none">View all →</a>
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3 text-muted small">Pending Review</h6>
                <div class="d-flex align-items-center gap-3">
                    <div class="h3 mb-0 text-warning">{{ $applicationStats['submitted'] ?? 0 }}</div>
                    <div class="text-muted small">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
                <small class="text-muted d-block mt-2">waiting for your decision</small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3 text-muted small">Accepted</h6>
                <div class="d-flex align-items-center gap-3">
                    <div class="h3 mb-0 text-success">{{ $applicationStats['accepted'] ?? 0 }}</div>
                    <div class="text-muted small">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
                <small class="text-muted d-block mt-2">
                    {{ round(($applicationStats['accepted'] ?? 0) / max($applicationStats['total'], 1) * 100) }}% success rate
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-lightning-charge"></i> Quick Actions</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="{{ route('employer.internships.create') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-plus-circle"></i> Create New Listing
                </a>
                <a href="{{ route('employer.applicants.index') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-eye"></i> Review Applicants
                </a>
                <a href="{{ route('employer.profile.edit') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-building"></i> Edit Company Profile
                </a>
                <a href="{{ route('messages.index') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-chat-dots"></i> Messages
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Application Status Breakdown</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1 status-row">
                        <span class="status-label">Submitted</span>
                        <span class="badge bg-info status-count">{{ $applicationStats['submitted'] ?? 0 }}</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-info" style="width: {{ ($applicationStats['submitted'] ?? 0) / max($applicationStats['total'], 1) * 100 }}%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1 status-row">
                        <span class="status-label">Under Review</span>
                        <span class="badge bg-warning status-count">{{ $applicationStats['reviewed'] ?? 0 }}</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-warning" style="width: {{ ($applicationStats['reviewed'] ?? 0) / max($applicationStats['total'], 1) * 100 }}%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1 status-row">
                        <span class="status-label">Interviews</span>
                        <span class="badge bg-purple status-count">{{ $applicationStats['interview'] ?? 0 }}</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar" style="background-color: #8B5CF6; width: {{ ($applicationStats['interview'] ?? 0) / max($applicationStats['total'], 1) * 100 }}%"></div>
                    </div>
                </div>
                <div class="mb-0">
                    <div class="d-flex justify-content-between align-items-center mb-1 status-row">
                        <span class="status-label">Accepted</span>
                        <span class="badge bg-success status-count">{{ $applicationStats['accepted'] ?? 0 }}</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success" style="width: {{ ($applicationStats['accepted'] ?? 0) / max($applicationStats['total'], 1) * 100 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Applications -->
@if($recentApplications->count())
<div class="card mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-send"></i> Recent Applications</h5>
        <a href="{{ route('employer.applicants.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Student</th>
                    <th>Position</th>
                    <th>Status</th>
                    <th>Applied</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentApplications as $application)
                    <tr>
                        <td>
                            <strong>{{ $application->student->user->name }}</strong>
                            <br><small class="text-muted">{{ $application->student->user->email }}</small>
                        </td>
                        <td>{{ $application->internship->title }}</td>
                        <td>
                            @php
                                $statusColors = [
                                    'submitted' => '#3B82F6',
                                    'reviewed' => '#F59E0B',
                                    'interview' => '#8B5CF6',
                                    'accepted' => '#10B981',
                                    'rejected' => '#EF4444',
                                ];
                                $statusColor = $statusColors[$application->status->value] ?? '#6B7280';
                            @endphp
                            <span class="badge" style="background-color: {{ $statusColor }}">
                                {{ $application->status->label() }}
                            </span>
                        </td>
                        <td><small class="text-muted">{{ $application->applied_at->diffForHumans() }}</small></td>
                        <td>
                            <a href="{{ route('employer.applicants.show', $application) }}" class="btn btn-sm btn-outline-primary">
                                View →
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Notifications -->
@if($notifications->count())
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-bell"></i> Recent Notifications</h5>
    </div>
    <div class="list-group list-group-flush">
        @foreach($notifications as $notification)
            <div class="list-group-item">
                <div class="d-flex gap-2">
                    <div class="flex-grow-1">
                        <p class="mb-1">{{ $notification->message }}</p>
                        <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif
</div>
@endsection
