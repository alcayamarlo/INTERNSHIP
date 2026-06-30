@extends('layouts.app')

@section('title', 'Coordinator Dashboard')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-1">Institution Dashboard</h1>
    <p class="text-muted mb-0">Manage students and track placements at {{ auth()->user()->coordinator->institution->name ?? 'Your Institution' }}</p>
</div>

<div class="row g-4 mb-4">
    <!-- Total Students -->
    <div class="col-md-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="card-title mb-3 text-muted small">Total Students</h6>
                <div class="h3 mb-0">{{ $stats['total_students'] }}</div>
                <small class="text-muted d-block mt-2">
                    <i class="bi bi-people"></i> Active students
                </small>
            </div>
        </div>
    </div>

    <!-- Total Applications -->
    <div class="col-md-6 col-lg-3">
        <div class="card stat-card accent">
            <div class="card-body">
                <h6 class="card-title mb-3 text-muted small">Applications</h6>
                <div class="h3 mb-0">{{ $stats['total_applications'] }}</div>
                <small class="text-muted d-block mt-2">
                    <i class="bi bi-send"></i> Internship applications
                </small>
            </div>
        </div>
    </div>

    <!-- Accepted -->
    <div class="col-md-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="card-title mb-3 text-muted small">Accepted</h6>
                <div class="h3 mb-0">{{ $stats['accepted'] }}</div>
                <small class="text-muted d-block mt-2">
                    <i class="bi bi-check-circle"></i> Placements
                </small>
            </div>
        </div>
    </div>

    <!-- Placement Rate -->
    <div class="col-md-6 col-lg-3">
        <div class="card stat-card accent">
            <div class="card-body">
                <h6 class="card-title mb-3 text-muted small">Placement Rate</h6>
                <div class="h3 mb-0">{{ $stats['placement_rate'] }}%</div>
                <small class="text-muted d-block mt-2">
                    <i class="bi bi-graph-up"></i> Success rate
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
                <a href="{{ route('coordinator.students.index') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-people"></i> Manage Students
                </a>
                <a href="{{ route('coordinator.reports.index') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-file-earmark-bar-graph"></i> Generate Reports
                </a>
                <a href="{{ route('messages.index') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-chat-dots"></i> Messages & Announcements
                </a>
                <a href="{{ route('notifications.index') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-bell"></i> Notifications
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Institution Info</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Institution:</dt>
                    <dd class="col-sm-8">{{ auth()->user()->coordinator->institution->name ?? 'N/A' }}</dd>
                    <dt class="col-sm-4">Department:</dt>
                    <dd class="col-sm-8">{{ auth()->user()->coordinator->department ?? 'N/A' }}</dd>
                    <dt class="col-sm-4">Contact:</dt>
                    <dd class="col-sm-8">{{ auth()->user()->email }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<!-- Recent Students -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between">
                <h5 class="mb-0"><i class="bi bi-people"></i> Recent Students</h5>
                <a href="{{ route('coordinator.students.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Program</th>
                            <th>Competencies</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentStudents as $student)
                            <tr>
                                <td>{{ $student->user->name }}</td>
                                <td>{{ $student->program ?? 'N/A' }}</td>
                                <td><span class="badge bg-light text-dark">{{ $student->competencies()->count() }}</span></td>
                                <td>
                                    <a href="{{ route('coordinator.students.show', $student) }}" class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No students yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Applications -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-send"></i> Recent Applications</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th>Position</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentApplications as $application)
                            <tr>
                                <td>{{ $application->student->user->name }}</td>
                                <td>{{ $application->internship->title }}</td>
                                <td>
                                    <span class="badge" style="background-color: {{ match($application->status->value) {
                                        'submitted' => '#3B82F6',
                                        'reviewed' => '#F59E0B',
                                        'interview' => '#8B5CF6',
                                        'accepted' => '#10B981',
                                        'rejected' => '#EF4444',
                                        default => '#6B7280'
                                    } }}">
                                        {{ $application->status->label() }}
                                    </span>
                                </td>
                                <td><small class="text-muted">{{ $application->applied_at->format('M d, Y') }}</small></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No applications yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Notifications -->
@if($notifications->count())
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-bell"></i> Recent Notifications</h5>
    </div>
    <div class="list-group list-group-flush">
        @foreach($notifications as $notification)
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1">{{ $notification->title }}</h6>
                        <p class="mb-0 text-muted small">{{ $notification->message }}</p>
                    </div>
                    <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif
@endsection
