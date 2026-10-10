@extends('layouts.app')

@section('title', 'Employer Dashboard')

@push('styles')
<style>
    .employer-dashboard-shell {
        padding: 6px 0 0;
        color: #edf5fb;
    }

    .employer-dashboard-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
        gap: 1rem;
    }

    .employer-dashboard-header h1 {
        margin: 0;
        color: #f4f9ff;
        font-size: clamp(2.1rem, 2vw + 1rem, 3rem);
        font-weight: 800;
        letter-spacing: -0.05em;
    }

    .employer-dashboard-header p {
        margin-top: 6px;
        color: rgba(196, 214, 228, 0.8);
        font-size: 1rem;
    }

    .employer-dashboard-status {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-top: 10px;
        color: rgba(175, 205, 222, 0.8);
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .employer-dashboard-status::before {
        content: "";
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #7ac6a4;
    }

    .employer-dashboard-shell > .row:first-of-type {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .employer-dashboard-shell .card,
    .employer-dashboard-shell .list-group-item,
    .employer-dashboard-shell .btn {
        border-color: rgba(148, 163, 184, 0.14) !important;
        background: rgba(16, 31, 45, 0.86) !important;
        color: #edf5fb;
    }

    .employer-dashboard-shell .card {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(148, 163, 184, 0.14);
        border-radius: 18px;
        background: rgba(15, 30, 43, 0.88);
        box-shadow: 0 10px 22px rgba(3, 8, 18, 0.14);
    }

    .employer-dashboard-shell .card::before {
        content: "";
        position: absolute;
        inset: 0 0 auto;
        height: 2px;
        background: rgba(148, 163, 184, 0.18);
    }

    .employer-dashboard-shell .card-body {
        padding: 1rem 1.1rem;
    }

    .employer-dashboard-shell .card-header {
        border-bottom: 1px solid rgba(148, 163, 184, 0.12);
        background: rgba(14, 27, 38, 0.9) !important;
        padding: 1rem 1.1rem;
    }

    .employer-dashboard-shell .card-header h5 {
        margin: 0;
        color: #edf8ff;
        font-size: 1.1rem;
        font-weight: 800;
    }

    .employer-dashboard-shell .card-header h5 i {
        margin-right: 0.5rem;
        color: #b9c9d9;
    }

    .employer-dashboard-shell .card-title {
        margin-bottom: 0.75rem;
        font-size: 0.72rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(196, 214, 228, 0.82);
        font-weight: 700;
    }

    .employer-dashboard-shell .h3 {
        color: #f4fbff;
        font-size: clamp(1.4rem, 1vw + 0.8rem, 1.8rem);
        font-weight: 800;
        letter-spacing: -0.04em;
        margin: 0;
    }

    .employer-dashboard-shell .text-muted,
    .employer-dashboard-shell small,
    .employer-dashboard-shell .small {
        color: rgba(176, 201, 219, 0.84) !important;
    }

    .employer-dashboard-shell .list-group-item {
        border-color: rgba(148, 163, 184, 0.1);
        background: transparent;
        color: #dcebf5;
        transition: background 0.18s ease;
    }

    .employer-dashboard-shell .list-group-item:hover {
        background: rgba(148, 163, 184, 0.04);
    }

    .employer-dashboard-shell .list-group-item i {
        width: 24px;
        color: #dfeaf6;
    }

    .employer-dashboard-shell .list-group-item-action {
        display: flex;
        align-items: center;
        min-height: 58px;
        font-size: 1.02rem;
        font-weight: 600;
        padding: 0.9rem 1rem;
    }

    .employer-dashboard-shell .list-group-item-action::after {
        content: "";
        width: 7px;
        height: 7px;
        margin-left: auto;
        border-top: 1px solid rgba(197, 213, 230, 0.9);
        border-right: 1px solid rgba(197, 213, 230, 0.9);
        transform: rotate(45deg);
        opacity: 0.7;
    }

    .employer-dashboard-shell .progress {
        height: 7px !important;
        overflow: hidden;
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.14);
    }

    .employer-dashboard-shell .progress-bar {
        border-radius: 999px;
        background: linear-gradient(90deg, rgba(160, 181, 201, 0.85), rgba(140, 160, 180, 0.95));
    }

    .employer-dashboard-shell .table {
        --bs-table-bg: transparent;
        --bs-table-color: rgba(216, 232, 243, 0.9);
        --bs-table-border-color: rgba(148, 163, 184, 0.1);
    }

    .employer-dashboard-shell .table thead th {
        background: rgba(16, 32, 46, 0.8);
        color: rgba(214, 227, 240, 0.8);
        border-color: rgba(148, 163, 184, 0.12);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding: 0.8rem 0.9rem;
    }

    .employer-dashboard-shell .table td {
        color: rgba(216, 232, 243, 0.9) !important;
        border-color: rgba(148, 163, 184, 0.08);
        vertical-align: middle;
        padding: 0.8rem 0.9rem;
        font-size: 0.92rem;
    }

    .employer-dashboard-shell .table tbody tr:hover {
        background: rgba(148, 163, 184, 0.03);
    }

    .employer-dashboard-shell .btn-primary {
        border: 0;
        background: linear-gradient(135deg, #6ab6d7, #4f9ed1) !important;
        color: #eef9ff !important;
        font-weight: 700;
        box-shadow: 0 8px 20px rgba(37,194,255,.15);
    }

    .employer-dashboard-shell .btn-outline-primary {
        border-color: rgba(148, 163, 184, 0.25);
        color: #ebf4ff;
        background: rgba(30, 46, 60, 0.85);
        border-radius: 8px;
        font-weight: 700;
    }

    .employer-dashboard-shell .status-label {
        color: #dcebf5;
        font-weight: 650;
    }

    .employer-dashboard-shell .status-row {
        padding: 10px 0;
        border-bottom: 1px solid rgba(148, 163, 184, 0.08);
    }

    .employer-dashboard-shell .status-row:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .employer-dashboard-shell .status-count {
        min-width: 28px;
        text-align: center;
    }

    @media (max-width: 991.98px) {
        .employer-dashboard-shell > .row:first-of-type {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .employer-dashboard-shell > .row:first-of-type {
            grid-template-columns: 1fr;
        }

        .employer-dashboard-header {
            align-items: flex-start;
            flex-direction: column;
        }
    }
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
