@extends('layouts.app')

@section('title', 'Student Dashboard')

@section('content')

@php
    $profileCompletion = max(0, min(100, (int) ($student->profile_completion ?? 0)));

    $competencyScoreValue = $competencyScore ?? 0;

    try {
        $competencyCount = $student->competencies()->count();
    } catch (\Throwable $e) {
        $competencyCount = 0;
    }

    $applications = $recentApplications ?? collect();
    $notificationCount = (int) ($unreadCount ?? 0);
    $internshipRecommendations = $recommendations ?? collect();
@endphp

<x-page-header
    title="Student Dashboard"
    subtitle="Track your profile, competencies, and internship applications"
    eyebrow="My workspace"
>
    @if($profileCompletion >= 80)
        <span class="sb-status sb-status--success">Profile complete</span>
    @else
        <span class="sb-status sb-status--brand">Profile {{ $profileCompletion }}% complete</span>
    @endif
    <span class="sb-tag"><i class="bi bi-calendar3" aria-hidden="true"></i> {{ now()->format('M d, Y') }}</span>
    <a href="{{ route('student.internships.index') }}" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Browse Internships</a>
</x-page-header>

<x-dashboard-announcements />
<x-dashboard-ojt />


{{-- =========================================================
    DASHBOARD STATISTICS
========================================================= --}}

<div class="sb-stat-grid sb-stat-grid--3">

    <x-dashboard-stat-card
        title="Profile Completion"
        :value="$profileCompletion.'%'"
        icon="bi-person-check"
        :show-progress="true"
    >
        <x-slot:description>
            @if($profileCompletion < 50)
                <i class="bi bi-exclamation-circle" aria-hidden="true"></i> Complete your profile
            @elseif($profileCompletion < 80)
                <i class="bi bi-info-circle" aria-hidden="true"></i> Keep updating info
            @else
                <i class="bi bi-check-circle" aria-hidden="true"></i> Profile complete
            @endif
        </x-slot:description>
    </x-dashboard-stat-card>

    <x-dashboard-stat-card
        title="Competency Score"
        :value="$competencyScoreValue"
        icon="bi-award"
        tone="warning"
        :description="$competencyCount.' skills · avg proficiency'"
    />

    <x-dashboard-stat-card
        title="Active Applications"
        :value="$applications->count()"
        icon="bi-send"
        tone="info"
        :href="route('student.applications.index')"
        description="View all applications →"
    />

    <x-dashboard-stat-card
        title="Notifications"
        :value="$notificationCount"
        icon="bi-bell"
        tone="success"
        :href="route('notifications.index')"
        description="View all notifications →"
    />

</div>


<div class="sb-layout-2">

    {{-- =====================================================
        RECENT APPLICATIONS
    ====================================================== --}}

    <div class="card">

        <div class="card-header">
            <h2>
                <i class="bi bi-send" aria-hidden="true"></i>
                Recent Applications
            </h2>

            @if($applications->count() > 0)
                <a href="{{ route('student.applications.index') }}" class="sb-card-link">View all →</a>
            @endif
        </div>

        @if($applications->count() > 0)

            <div class="table-responsive">

                <table class="table table-hover">

                    <thead>
                        <tr>
                            <th scope="col">Position</th>
                            <th scope="col">Company</th>
                            <th scope="col">Status</th>
                            <th scope="col">Match</th>
                            <th scope="col">Applied</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($applications as $application)

                            @php
                                $internship = $application->internship ?? null;

                                $companyName =
                                    optional(optional($internship)->employer)->company_name
                                    ?? 'Company not available';

                                $statusValue = 'unknown';
                                $statusLabel = 'Unknown';

                                if ($application->status) {

                                    if (is_object($application->status) && method_exists($application->status, 'value')) {
                                        $statusValue = $application->status->value;
                                    } elseif (is_object($application->status) && isset($application->status->value)) {
                                        $statusValue = $application->status->value;
                                    } else {
                                        $statusValue = (string) $application->status;
                                    }

                                    if (
                                        is_object($application->status)
                                        && method_exists($application->status, 'label')
                                    ) {
                                        $statusLabel = $application->status->label();
                                    } else {
                                        $statusLabel = ucfirst(str_replace('_', ' ', $statusValue));
                                    }

                                }

                                $applicationMatch = (int) ($application->match_percentage ?? 0);
                                $appliedDate = $application->applied_at ?? null;
                            @endphp

                            <tr>

                                <td>
                                    @if($internship)
                                        <strong>{{ $internship->title ?? 'Untitled Position' }}</strong>
                                    @else
                                        <span class="text-muted">Internship unavailable</span>
                                    @endif
                                </td>

                                <td>{{ $companyName }}</td>

                                <td>
                                    <x-status-badge :status="$statusValue" :label="$statusLabel" />
                                </td>

                                <td>{{ $applicationMatch }}%</td>

                                <td>
                                    @if($appliedDate)
                                        <span class="text-muted">{{ $appliedDate->diffForHumans() }}</span>
                                    @else
                                        <span class="text-muted">Not available</span>
                                    @endif
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @else

            <x-empty-state
                icon="bi-send"
                title="No applications yet"
                message="When you apply to an internship, you can follow its status here."
            >
                <a href="{{ route('student.internships.index') }}" class="btn btn-primary">Browse internships</a>
            </x-empty-state>

        @endif

    </div>


    {{-- =====================================================
        QUICK ACTIONS
    ====================================================== --}}

    <x-dashboard-quick-actions title="Quick Actions" icon="bi-lightning-charge">

        <a href="{{ route('student.profile.edit') }}" class="list-group-item list-group-item-action">
            <i class="bi bi-person-check" aria-hidden="true"></i>
            Complete Your Profile
        </a>

        <a href="{{ route('student.competencies.index') }}" class="list-group-item list-group-item-action">
            <i class="bi bi-award" aria-hidden="true"></i>
            Add / Update Certificates
        </a>

        <a href="{{ route('student.profile.edit') }}#portfolio" class="list-group-item list-group-item-action">
            <i class="bi bi-folder" aria-hidden="true"></i>
            Manage Portfolio
        </a>

        <a href="{{ route('student.resume.index') }}" class="list-group-item list-group-item-action">
            <i class="bi bi-file-text" aria-hidden="true"></i>
            Generate Resume
        </a>

    </x-dashboard-quick-actions>

</div>


<div class="sb-layout-2">

    {{-- =====================================================
        RECOMMENDED INTERNSHIPS
    ====================================================== --}}

    <div class="card">

        <div class="card-header">
            <h2>
                <i class="bi bi-briefcase" aria-hidden="true"></i>
                Recommended for You
            </h2>

            <a href="{{ route('student.internships.index') }}" class="sb-card-link">Browse all internships →</a>
        </div>

        @if($internshipRecommendations->count() > 0)

            <div class="table-responsive">

                <table class="table table-hover">

                    <thead>
                        <tr>
                            <th scope="col">Position</th>
                            <th scope="col">Company</th>
                            <th scope="col">Location</th>
                            <th scope="col">Match</th>
                            <th scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($internshipRecommendations as $internship)

                            @php
                                $matchPercentage = max(0, min(100, (int) ($internship->match_percentage ?? 0)));

                                $matchTone = $matchPercentage >= 80 ? 'success' : ($matchPercentage >= 60 ? 'warning' : 'danger');

                                $companyName =
                                    optional($internship->employer)->company_name
                                    ?? 'Company not available';
                            @endphp

                            <tr>

                                <td>
                                    <strong>{{ $internship->title ?? 'Untitled Position' }}</strong>
                                    <div class="text-muted small">
                                        {{ \Illuminate\Support\Str::limit($internship->description ?? 'No description available.', 60) }}
                                    </div>
                                </td>

                                <td>{{ $companyName }}</td>

                                <td>{{ $internship->location ?? 'Not specified' }}</td>

                                <td>
                                    <x-status-badge :status="$matchTone" :tone="$matchTone" :label="$matchPercentage.'%'" />
                                </td>

                                <td class="text-end">
                                    <a href="{{ route('student.internships.show', $internship) }}" class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @else

            <x-empty-state
                icon="bi-briefcase"
                title="No recommendations yet"
                message="Add certificates to your profile and we will match you with internships."
            >
                <a href="{{ route('student.competencies.index') }}" class="btn btn-primary">Add a certificate</a>
            </x-empty-state>

        @endif

    </div>


    {{-- =====================================================
        GETTING STARTED
    ====================================================== --}}

    <div class="card">

        <div class="card-header">
            <h2>
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                Getting Started
            </h2>
        </div>

        <div class="card-body">

            <ol class="sb-steps">

                <li>
                    <div>
                        <strong>Complete Profile</strong>
                        <span>Add your education and background</span>
                    </div>
                </li>

                <li>
                    <div>
                        <strong>Add Competencies</strong>
                        <span>Highlight your skills and expertise</span>
                    </div>
                </li>

                <li>
                    <div>
                        <strong>Build Portfolio</strong>
                        <span>Showcase your projects and achievements</span>
                    </div>
                </li>

                <li>
                    <div>
                        <strong>Browse Internships</strong>
                        <span>Find opportunities matched to your skills</span>
                    </div>
                </li>

                <li>
                    <div>
                        <strong>Apply &amp; Track</strong>
                        <span>Submit applications and monitor progress</span>
                    </div>
                </li>

            </ol>

        </div>

    </div>

</div>

@endsection
