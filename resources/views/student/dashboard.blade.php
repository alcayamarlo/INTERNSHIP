@extends('layouts.app')

@section('title', 'Student Dashboard')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-1">Welcome, {{ Auth::user()->name }}!</h1>
    <p class="text-muted mb-0">Monitor your progress and explore internship opportunities</p>
</div>

<div class="row g-4 mb-4">
    <!-- Profile Completion -->
    <div class="col-md-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3 text-muted small">Profile Completion</h6>
                <div class="d-flex align-items-end gap-3">
                    <div>
                        <div class="h3 mb-0 text-accent">{{ $student->profile_completion }}%</div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="progress" style="height: 4px;">
                            <div class="progress-bar bg-accent" style="width: {{ $student->profile_completion }}%"></div>
                        </div>
                    </div>
                </div>
                <small class="text-muted d-block mt-2">
                    @if($student->profile_completion < 50)
                        <i class="bi bi-exclamation-circle"></i> Complete your profile
                    @elseif($student->profile_completion < 80)
                        <i class="bi bi-info-circle"></i> Keep updating info
                    @else
                        <i class="bi bi-check-circle text-success"></i> Profile complete
                    @endif
                </small>
            </div>
        </div>
    </div>

    <!-- Competency Score -->
    <div class="col-md-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3 text-muted small">Competency Score</h6>
                <div class="d-flex align-items-center gap-3">
                    <div class="h3 mb-0 text-primary">{{ $competencyScore }}</div>
                    <div class="text-muted small">
                        <div>{{ $student->competencies()->count() }} skills</div>
                    </div>
                </div>
                <small class="text-muted d-block mt-2">
                    <i class="bi bi-star-fill text-warning"></i> Avg proficiency
                </small>
            </div>
        </div>
    </div>

    <!-- Active Applications -->
    <div class="col-md-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3 text-muted small">Active Applications</h6>
                <div class="h3 mb-0">{{ $recentApplications->count() }}</div>
                <small class="text-muted d-block mt-2">
                    <a href="{{ route('student.applications.index') }}" class="text-decoration-none">View all →</a>
                </small>
            </div>
        </div>
    </div>

    <!-- Unread Notifications -->
    <div class="col-md-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3 text-muted small">Notifications</h6>
                <div class="h3 mb-0">{{ $unreadCount }}</div>
                <small class="text-muted d-block mt-2">
                    <a href="{{ route('notifications.index') }}" class="text-decoration-none">View all →</a>
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
                <a href="{{ route('student.profile.edit') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-person-check"></i> Complete Your Profile
                </a>
                <a href="{{ route('student.competencies.index') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-award"></i> Add / Update Competencies
                </a>
                <a href="{{ route('student.portfolio.index') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-folder"></i> Manage Portfolio
                </a>
                <a href="{{ route('student.resume.index') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-file-text"></i> Generate Resume
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Getting Started</h5>
            </div>
            <div class="card-body">
                <ol class="mb-0 ps-3">
                    <li class="mb-2"><strong>Complete Profile</strong> - Add your education and background</li>
                    <li class="mb-2"><strong>Add Competencies</strong> - Highlight your skills and expertise</li>
                    <li class="mb-2"><strong>Build Portfolio</strong> - Showcase your projects and achievements</li>
                    <li class="mb-2"><strong>Browse Internships</strong> - Find opportunities matched to your skills</li>
                    <li><strong>Apply & Track</strong> - Submit applications and monitor progress</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Recommended Internships -->
@if($recommendations && $recommendations->count() > 0)
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-briefcase"></i> Recommended for You</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Position</th>
                    <th>Company</th>
                    <th>Location</th>
                    <th>Match</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($recommendations as $internship)
                    <tr>
                        <td>
                            <strong>{{ $internship->title }}</strong>
                            <br><small class="text-muted">{{ Str::limit($internship->description, 60) }}</small>
                        </td>
                        <td>{{ $internship->employer->company_name }}</td>
                        <td>{{ $internship->location }}</td>
                        <td>
                            <span class="badge" style="background-color: {{ $internship->match_percentage >= 80 ? '#10B981' : ($internship->match_percentage >= 60 ? '#F59E0B' : '#EF4444') }}">
                                {{ $internship->match_percentage }}%
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('student.internships.show', $internship) }}" class="btn btn-sm btn-outline-primary">
                                View →
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">
        <a href="{{ route('student.internships.index') }}" class="text-decoration-none">Browse all internships →</a>
    </div>
</div>
@endif

<!-- Recent Applications -->
@if($recentApplications->count())
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-send"></i> Recent Applications</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Position</th>
                    <th>Company</th>
                    <th>Status</th>
                    <th>Match</th>
                    <th>Applied</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentApplications as $application)
                    <tr>
                        <td>{{ $application->internship->title }}</td>
                        <td>{{ $application->internship->employer->company_name }}</td>
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
                        <td>{{ $application->match_percentage }}%</td>
                        <td><small class="text-muted">{{ $application->applied_at->diffForHumans() }}</small></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
