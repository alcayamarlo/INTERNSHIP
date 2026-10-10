@extends('layouts.app')

@section('title', 'Student Dashboard')

@push('styles')
<style>
    .student-dashboard-shell {
        padding: 6px 0 0;
        color: #edf5fb;
    }

    .student-dashboard-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .student-dashboard-header h1 {
        margin: 0;
        color: #f4f9ff;
        font-size: clamp(1.8rem, 1.2vw + 1.1rem, 2.5rem);
        font-weight: 800;
        letter-spacing: -0.05em;
    }

    .student-dashboard-header p {
        margin-top: 6px;
        color: rgba(196, 214, 228, 0.8);
        font-size: 0.95rem;
    }

    .student-dashboard-search {
        position: relative;
        width: min(430px, 42vw);
        min-width: 260px;
    }

    .student-dashboard-search .form-control {
        height: 48px;
        padding-left: 44px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 14px;
        background: rgba(17, 29, 42, 0.9);
        color: #edf5fb;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.02);
    }

    .student-dashboard-search .form-control::placeholder {
        color: rgba(168, 184, 201, 0.8);
    }

    .student-dashboard-search i {
        position: absolute;
        top: 50%;
        left: 16px;
        z-index: 2;
        color: #b4c5d5;
        transform: translateY(-50%);
    }

    .student-dashboard-shell > .row:first-of-type {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .student-dashboard-shell .card,
    .student-dashboard-shell .list-group-item {
        border-color: rgba(148, 163, 184, 0.16) !important;
        background: rgba(16, 31, 45, 0.86) !important;
        color: #edf5fb;
    }

    .student-dashboard-shell .card {
        position: relative;
        overflow: hidden;
        border-radius: 18px;
        border: 1px solid rgba(148, 163, 184, 0.14);
        background: rgba(15, 30, 43, 0.88);
        box-shadow: 0 10px 22px rgba(3, 8, 18, 0.14);
    }

    .student-dashboard-shell .card::before {
        content: "";
        position: absolute;
        inset: 0 0 auto;
        height: 2px;
        background: rgba(148, 163, 184, 0.18);
    }

    .student-dashboard-shell .card-body {
        padding: 1rem 1.1rem;
    }

    .student-dashboard-shell .card-header {
        border-bottom: 1px solid rgba(148, 163, 184, 0.12);
        background: rgba(14, 27, 38, 0.9) !important;
        padding: 1rem 1.1rem;
    }

    .student-dashboard-shell .card-header h5 {
        margin: 0;
        color: #edf8ff;
        font-size: 1.1rem;
        font-weight: 800;
    }

    .student-dashboard-shell .card-header h5 i {
        margin-right: 0.5rem;
        color: #b9c9d9;
    }

    .student-dashboard-shell .card-footer {
        border-top: 1px solid rgba(148, 163, 184, 0.12);
        background: rgba(14, 27, 38, 0.9) !important;
        padding: 0.9rem 1.1rem;
    }

    .student-dashboard-shell .h3 {
        color: #f4fbff;
        font-size: clamp(1.4rem, 1vw + 0.8rem, 1.8rem);
        font-weight: 800;
        letter-spacing: -0.04em;
        margin: 0;
    }

    .student-dashboard-shell .card-title,
    .student-dashboard-shell h5,
    .student-dashboard-shell h6 {
        color: rgba(221, 235, 248, 0.92);
    }

    .student-dashboard-shell .card-title {
        margin-bottom: 0.75rem;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(196, 214, 228, 0.82);
    }

    .student-dashboard-shell .text-muted,
    .student-dashboard-shell small,
    .student-dashboard-shell .small,
    .student-dashboard-shell .table td {
        color: rgba(176, 201, 219, 0.84) !important;
    }

    .student-dashboard-shell .progress {
        background: rgba(148, 163, 184, 0.14);
        border-radius: 999px;
        overflow: hidden;
    }

    .student-dashboard-shell .progress-bar {
        border-radius: 999px;
        background: linear-gradient(90deg, #9ab8d9, #7aa8d8);
    }

    .student-dashboard-shell .list-group-item {
        border-top: 1px solid rgba(148, 163, 184, 0.1);
        transition: background 0.18s ease, border-color 0.18s ease;
    }

    .student-dashboard-shell .list-group-item:hover {
        background: rgba(22, 38, 54, 0.92) !important;
        border-color: rgba(148, 163, 184, 0.12) !important;
    }

    .student-dashboard-shell .list-group-item-action {
        display: flex;
        align-items: center;
        min-height: 54px;
        font-size: 0.98rem;
        font-weight: 600;
        padding: 0.82rem 0.95rem;
    }

    .student-dashboard-shell .list-group-item-action i {
        color: #dfeaf6;
        font-size: 1.1rem;
        margin-right: 0.75rem;
    }

    .student-dashboard-shell .list-group-item-action::after {
        content: "";
        width: 7px;
        height: 7px;
        margin-left: auto;
        border-top: 1px solid rgba(197, 213, 230, 0.9);
        border-right: 1px solid rgba(197, 213, 230, 0.9);
        transform: rotate(45deg);
        opacity: 0.7;
    }

    .student-dashboard-shell .badge {
        border-radius: 999px;
        padding: 0.45rem 0.7rem;
        letter-spacing: 0.02em;
        font-weight: 700;
    }

    .student-dashboard-shell .table thead th {
        background: rgba(16, 32, 46, 0.8);
        color: rgba(214, 227, 240, 0.8);
        border-color: rgba(148, 163, 184, 0.12);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding: 0.8rem 0.9rem;
    }

    .student-dashboard-shell .table {
        --bs-table-bg: transparent;
        --bs-table-border-color: rgba(148, 163, 184, 0.1);
        color: #ebf4ff;
    }

    .student-dashboard-shell .table tbody td {
        padding: 0.8rem 0.82rem;
        color: rgba(239, 247, 255, 0.95);
        font-size: 0.92rem;
    }

    .student-dashboard-shell .table tbody tr:hover {
        background: rgba(148, 163, 184, 0.03);
    }

    .student-dashboard-shell .card-footer a,
    .student-dashboard-shell .text-decoration-none {
        color: #dfeaf6 !important;
        font-weight: 700;
    }

    .student-dashboard-shell .getting-started-list {
        margin: 0;
        padding-left: 1.35rem;
        color: rgba(202, 222, 237, 0.88);
    }

    .student-dashboard-shell .getting-started-list li {
        padding: 0.28rem 0;
        line-height: 1.5;
    }

    .student-dashboard-shell .getting-started-list strong {
        color: #f0f8ff;
    }

    .student-dashboard-shell .match-badge {
        min-width: 42px;
        text-align: center;
        box-shadow: none;
    }

    .student-dashboard-shell .btn-outline-primary {
        border-color: rgba(148, 163, 184, 0.25);
        color: #ebf4ff;
        background: rgba(30, 46, 60, 0.85);
        border-radius: 8px;
        font-weight: 700;
    }

    .student-dashboard-shell .btn-outline-primary:hover {
        border-color: rgba(148, 163, 184, 0.4);
        background: rgba(38, 57, 75, 0.95);
        color: #fff;
    }

    @media (max-width: 991.98px) {
        .student-dashboard-shell > .row:first-of-type {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .student-dashboard-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .student-dashboard-search {
            width: 100%;
            min-width: 100%;
        }
    }

    @media (max-width: 575.98px) {
        .student-dashboard-shell > .row:first-of-type {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="student-dashboard-shell">
<div class="student-dashboard-header">
    <div>
    <h1>
        Welcome, {{ Auth::user()->name }}!
    </h1>

    <p>
        Monitor your progress and explore internship opportunities
    </p>
</div>
<div class="student-dashboard-search"><i class="bi bi-search"></i><input class="form-control" type="search" placeholder="Search internships, skills, or applications..."></div>
</div>


{{-- =========================================================
    DASHBOARD STATISTICS
========================================================= --}}

<div class="row g-4 mb-4">

    {{-- Profile Completion --}}
    <div class="col-md-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body">

                <h6 class="card-title mb-3 text-muted small">
                    Profile Completion
                </h6>

                @php
                    $profileCompletion = (int) ($student->profile_completion ?? 0);

                    if ($profileCompletion < 0) {
                        $profileCompletion = 0;
                    }

                    if ($profileCompletion > 100) {
                        $profileCompletion = 100;
                    }
                @endphp

                <div class="d-flex align-items-end gap-3">

                    <div>
                        <div class="h3 mb-0 text-accent">
                            {{ $profileCompletion }}%
                        </div>
                    </div>

                    <div class="flex-grow-1">

                        <div
                            class="progress"
                            style="height: 4px;"
                            role="progressbar"
                            aria-valuenow="{{ $profileCompletion }}"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        >
                            <div
                                class="progress-bar bg-accent"
                                style="width: {{ $profileCompletion }}%;"
                            ></div>
                        </div>

                    </div>

                </div>


                <small class="text-muted d-block mt-2">

                    @if($profileCompletion < 50)

                        <i class="bi bi-exclamation-circle"></i>
                        Complete your profile

                    @elseif($profileCompletion < 80)

                        <i class="bi bi-info-circle"></i>
                        Keep updating info

                    @else

                        <i class="bi bi-check-circle text-success"></i>
                        Profile complete

                    @endif

                </small>

            </div>
        </div>
    </div>


    {{-- Competency Score --}}
    <div class="col-md-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body">

                <h6 class="card-title mb-3 text-muted small">
                    Competency Score
                </h6>

                @php
                    $competencyScoreValue = $competencyScore ?? 0;

                    try {
                        $competencyCount = $student->competencies()->count();
                    } catch (\Throwable $e) {
                        $competencyCount = 0;
                    }
                @endphp

                <div class="d-flex align-items-center gap-3">

                    <div class="h3 mb-0 text-primary">
                        {{ $competencyScoreValue }}
                    </div>

                    <div class="text-muted small">
                        <div>
                            {{ $competencyCount }} skills
                        </div>
                    </div>

                </div>

                <small class="text-muted d-block mt-2">
                    <i class="bi bi-star-fill text-warning"></i>
                    Avg proficiency
                </small>

            </div>
        </div>
    </div>


    {{-- Active Applications --}}
    <div class="col-md-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body">

                <h6 class="card-title mb-3 text-muted small">
                    Active Applications
                </h6>

                @php
                    $applications = $recentApplications ?? collect();
                @endphp

                <div class="h3 mb-0">
                    {{ $applications->count() }}
                </div>

                <small class="text-muted d-block mt-2">

                    <a
                        href="{{ route('student.applications.index') }}"
                        class="text-decoration-none"
                    >
                        View all →
                    </a>

                </small>

            </div>
        </div>
    </div>


    {{-- Notifications --}}
    <div class="col-md-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body">

                <h6 class="card-title mb-3 text-muted small">
                    Notifications
                </h6>

                @php
                    $notificationCount = (int) ($unreadCount ?? 0);
                @endphp

                <div class="h3 mb-0">
                    {{ $notificationCount }}
                </div>

                <small class="text-muted d-block mt-2">

                    <a
                        href="{{ route('notifications.index') }}"
                        class="text-decoration-none"
                    >
                        View all →
                    </a>

                </small>

            </div>
        </div>
    </div>

</div>


{{-- =========================================================
    QUICK ACTIONS
========================================================= --}}

<div class="row g-4 mb-4">

    {{-- Quick Actions --}}
    <div class="col-md-6">

        <div class="card h-100">

            <div class="card-header bg-white">

                <h5 class="mb-0">
                    <i class="bi bi-lightning-charge"></i>
                    Quick Actions
                </h5>

            </div>

            <div class="list-group list-group-flush">

                <a
                    href="{{ route('student.profile.edit') }}"
                    class="list-group-item list-group-item-action"
                >
                    <i class="bi bi-person-check me-2"></i>
                    Complete Your Profile
                </a>


                <a
                    href="{{ route('student.competencies.index') }}"
                    class="list-group-item list-group-item-action"
                >
                    <i class="bi bi-award me-2"></i>
                    Add / Update Competencies
                </a>


                <a
                    href="{{ route('student.portfolio.index') }}"
                    class="list-group-item list-group-item-action"
                >
                    <i class="bi bi-folder me-2"></i>
                    Manage Portfolio
                </a>


                <a
                    href="{{ route('student.resume.index') }}"
                    class="list-group-item list-group-item-action"
                >
                    <i class="bi bi-file-text me-2"></i>
                    Generate Resume
                </a>

            </div>

        </div>

    </div>


    {{-- Getting Started --}}
    <div class="col-md-6">

        <div class="card h-100">

            <div class="card-header bg-white">

                <h5 class="mb-0">
                    <i class="bi bi-info-circle"></i>
                    Getting Started
                </h5>

            </div>

            <div class="card-body">

                <ol class="getting-started-list">

                    <li class="mb-2">
                        <strong>Complete Profile</strong>
                        - Add your education and background
                    </li>

                    <li class="mb-2">
                        <strong>Add Competencies</strong>
                        - Highlight your skills and expertise
                    </li>

                    <li class="mb-2">
                        <strong>Build Portfolio</strong>
                        - Showcase your projects and achievements
                    </li>

                    <li class="mb-2">
                        <strong>Browse Internships</strong>
                        - Find opportunities matched to your skills
                    </li>

                    <li>
                        <strong>Apply & Track</strong>
                        - Submit applications and monitor progress
                    </li>

                </ol>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
    RECOMMENDED INTERNSHIPS
========================================================= --}}

@php
    $internshipRecommendations = $recommendations ?? collect();
@endphp


@if($internshipRecommendations->count() > 0)

<div class="card mb-4">

    <div class="card-header bg-white">

        <h5 class="mb-0">
            <i class="bi bi-briefcase"></i>
            Recommended for You
        </h5>

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

                @foreach($internshipRecommendations as $internship)

                    @php
                        $matchPercentage = (int) ($internship->match_percentage ?? 0);

                        if ($matchPercentage < 0) {
                            $matchPercentage = 0;
                        }

                        if ($matchPercentage > 100) {
                            $matchPercentage = 100;
                        }

                        if ($matchPercentage >= 80) {
                            $matchColor = '#10B981';
                        } elseif ($matchPercentage >= 60) {
                            $matchColor = '#F59E0B';
                        } else {
                            $matchColor = '#EF4444';
                        }

                        $companyName =
                            optional($internship->employer)->company_name
                            ?? 'Company not available';
                    @endphp


                    <tr>

                        {{-- Position --}}
                        <td>

                            <strong>
                                {{ $internship->title ?? 'Untitled Position' }}
                            </strong>

                            <br>

                            <small class="text-muted">

                                {{ \Illuminate\Support\Str::limit(
                                    $internship->description ?? 'No description available.',
                                    60
                                ) }}

                            </small>

                        </td>


                        {{-- Company --}}
                        <td>
                            {{ $companyName }}
                        </td>


                        {{-- Location --}}
                        <td>
                            {{ $internship->location ?? 'Not specified' }}
                        </td>


                        {{-- Match --}}
                        <td>

                            <span
                                class="badge match-badge"
                                style="background-color: {{ $matchColor }};"
                            >
                                {{ $matchPercentage }}%
                            </span>

                        </td>


                        {{-- Action --}}
                        <td>

                            <a
                                href="{{ route('student.internships.show', $internship) }}"
                                class="btn btn-sm btn-outline-primary"
                            >
                                View →
                            </a>

                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    </div>


    <div class="card-footer bg-white">

        <a
            href="{{ route('student.internships.index') }}"
            class="text-decoration-none"
        >
            Browse all internships →
        </a>

    </div>

</div>

@endif


{{-- =========================================================
    RECENT APPLICATIONS
========================================================= --}}

@if($applications->count() > 0)

<div class="card">

    <div class="card-header bg-white">

        <h5 class="mb-0">
            <i class="bi bi-send"></i>
            Recent Applications
        </h5>

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

                @foreach($applications as $application)

                    @php

                        /*
                        |--------------------------------------------------------------------------
                        | Internship
                        |--------------------------------------------------------------------------
                        */

                        $internship = $application->internship ?? null;


                        /*
                        |--------------------------------------------------------------------------
                        | Company
                        |--------------------------------------------------------------------------
                        */

                        $companyName =
                            optional(optional($internship)->employer)->company_name
                            ?? 'Company not available';


                        /*
                        |--------------------------------------------------------------------------
                        | Status
                        |--------------------------------------------------------------------------
                        */

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


                        /*
                        |--------------------------------------------------------------------------
                        | Status Color
                        |--------------------------------------------------------------------------
                        */

                        switch ($statusValue) {

                            case 'submitted':
                                $statusColor = '#3B82F6';
                                break;

                            case 'reviewed':
                                $statusColor = '#F59E0B';
                                break;

                            case 'interview':
                                $statusColor = '#8B5CF6';
                                break;

                            case 'accepted':
                                $statusColor = '#10B981';
                                break;

                            case 'rejected':
                                $statusColor = '#EF4444';
                                break;

                            default:
                                $statusColor = '#6B7280';
                                break;

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Match Percentage
                        |--------------------------------------------------------------------------
                        */

                        $applicationMatch =
                            (int) ($application->match_percentage ?? 0);


                        /*
                        |--------------------------------------------------------------------------
                        | Applied Date
                        |--------------------------------------------------------------------------
                        */

                        $appliedDate = $application->applied_at ?? null;

                    @endphp


                    <tr>

                        {{-- Position --}}
                        <td>

                            @if($internship)

                                {{ $internship->title ?? 'Untitled Position' }}

                            @else

                                <span class="text-muted">
                                    Internship unavailable
                                </span>

                            @endif

                        </td>


                        {{-- Company --}}
                        <td>
                            {{ $companyName }}
                        </td>


                        {{-- Status --}}
                        <td>

                            <span
                                class="badge"
                                style="background-color: {{ $statusColor }};"
                            >
                                {{ $statusLabel }}
                            </span>

                        </td>


                        {{-- Match --}}
                        <td>
                            {{ $applicationMatch }}%
                        </td>


                        {{-- Applied --}}
                        <td>

                            @if($appliedDate)

                                <small class="text-muted">
                                    {{ $appliedDate->diffForHumans() }}
                                </small>

                            @else

                                <small class="text-muted">
                                    Not available
                                </small>

                            @endif

                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    </div>

</div>

@endif

</div>
@endsection