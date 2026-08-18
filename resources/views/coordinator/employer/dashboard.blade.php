@extends('layouts.app')

@section('title', 'Employer Dashboard')

@section('content')
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 mb-1">Welcome, {{ Auth::user()->name }}!</h1>
            <p class="text-muted mb-0">Manage your internship listings and applicants</p>
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
                    <div class="d-flex justify-content-between mb-1">
                        <span>Submitted</span>
                        <span class="badge bg-info">{{ $applicationStats['submitted'] ?? 0 }}</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-info" style="width: {{ ($applicationStats['submitted'] ?? 0) / max($applicationStats['total'], 1) * 100 }}%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Under Review</span>
                        <span class="badge bg-warning">{{ $applicationStats['reviewed'] ?? 0 }}</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-warning" style="width: {{ ($applicationStats['reviewed'] ?? 0) / max($applicationStats['total'], 1) * 100 }}%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Interviews</span>
                        <span class="badge bg-purple">{{ $applicationStats['interview'] ?? 0 }}</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar" style="background-color: #8B5CF6; width: {{ ($applicationStats['interview'] ?? 0) / max($applicationStats['total'], 1) * 100 }}%"></div>
                    </div>
                </div>
                <div class="mb-0">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Accepted</span>
                        <span class="badge bg-success">{{ $applicationStats['accepted'] ?? 0 }}</span>
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
@endsection
