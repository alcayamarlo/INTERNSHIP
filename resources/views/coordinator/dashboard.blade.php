@extends('layouts.app')

@section('title', 'Coordinator Dashboard')

@section('content')

<x-page-header
    title="Coordinator Dashboard"
    subtitle="Manage students and track placements at St. Cecilia’s College-Cebu, Inc."
>
    <a href="{{ route('coordinator.verification.index') }}" class="btn btn-primary">
        <i class="bi bi-shield-check" aria-hidden="true"></i> Review verification queue
    </a>
</x-page-header>


<div class="sb-stat-grid">

    <x-dashboard-stat-card
        title="Active Students"
        :value="$stats['total_students']"
        icon="bi-people"
        :href="route('coordinator.students.index')"
        description="View all students →"
    />

    <x-dashboard-stat-card
        title="Internship Applications"
        :value="$stats['total_applications']"
        icon="bi-file-earmark-text"
        tone="info"
    />

    <x-dashboard-stat-card
        title="Placements"
        :value="$stats['accepted']"
        icon="bi-check-circle"
        tone="success"
    />

    <x-dashboard-stat-card
        title="Success Rate"
        :value="$stats['placement_rate'].'%'"
        icon="bi-graph-up-arrow"
        tone="warning"
        description="Placements out of applications"
    />

</div>


<div class="sb-layout-2">

    {{-- Recent applications --}}

    <div class="card">

        <div class="card-header">
            <h2><i class="bi bi-send" aria-hidden="true"></i> Recent Applications</h2>
        </div>

        @if($recentApplications->count())

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th scope="col">Student</th>
                            <th scope="col">Position</th>
                            <th scope="col">Status</th>
                            <th scope="col">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentApplications as $application)
                            <tr>
                                <td><strong>{{ $application->student?->user?->name ?? 'N/A' }}</strong></td>
                                <td>{{ $application->internship?->title ?? 'N/A' }}</td>
                                <td><x-status-badge :status="$application->status->value" :label="$application->status->label()" /></td>
                                <td class="text-nowrap">{{ $application->applied_at?->format('M d, Y') ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @else

            <x-empty-state icon="bi-send" title="No applications yet" message="Student applications will appear here." />

        @endif

    </div>


    {{-- Quick actions --}}

    <x-dashboard-quick-actions title="Quick Actions" icon="bi-lightning-charge">

        <a href="{{ route('coordinator.verification.index') }}" class="list-group-item list-group-item-action">
            <i class="bi bi-shield-check" aria-hidden="true"></i> Verification Queue
        </a>
        <a href="{{ route('coordinator.students.index') }}" class="list-group-item list-group-item-action">
            <i class="bi bi-people" aria-hidden="true"></i> Manage Students
        </a>
        <a href="{{ route('coordinator.reports.index') }}" class="list-group-item list-group-item-action">
            <i class="bi bi-file-earmark-bar-graph" aria-hidden="true"></i> Generate Reports
        </a>
        <a href="{{ route('messages.index') }}" class="list-group-item list-group-item-action">
            <i class="bi bi-chat-dots" aria-hidden="true"></i> Messages &amp; Announcements
        </a>
        <a href="{{ route('notifications.index') }}" class="list-group-item list-group-item-action">
            <i class="bi bi-bell" aria-hidden="true"></i> Notifications
        </a>

    </x-dashboard-quick-actions>

</div>


<div class="sb-layout-2">

    {{-- Recent students --}}

    <div class="card">

        <div class="card-header">
            <h2><i class="bi bi-people" aria-hidden="true"></i> Recent Students</h2>
            <a href="{{ route('coordinator.students.index') }}" class="sb-card-link">View all →</a>
        </div>

        @if($recentStudents->count())

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Program</th>
                            <th scope="col">Competencies</th>
                            <th scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentStudents as $student)
                            <tr>
                                <td><strong>{{ $student->user?->name ?? 'N/A' }}</strong></td>
                                <td>{{ $student->program ?? 'N/A' }}</td>
                                <td><span class="sb-tag sb-tag--brand">{{ $student->competencies()->count() }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('coordinator.students.show', $student) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @else

            <x-empty-state icon="bi-people" title="No students yet" message="Registered students will appear here." />

        @endif

    </div>


    {{-- Institution info --}}

    <div class="card">

        <div class="card-header">
            <h2><i class="bi bi-info-circle" aria-hidden="true"></i> Institution Info</h2>
        </div>

        <div class="card-body">
            <dl class="sb-dl">
                <dt>Institution</dt>
                <dd>{{ auth()->user()->coordinator->institution->name ?? 'N/A' }}</dd>
                <dt>Department</dt>
                <dd>{{ auth()->user()->coordinator->department ?? 'N/A' }}</dd>
                <dt>Contact</dt>
                <dd>{{ auth()->user()->email }}</dd>
            </dl>
        </div>

    </div>

</div>

@endsection
