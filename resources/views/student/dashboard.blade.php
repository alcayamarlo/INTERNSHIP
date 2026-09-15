@extends('layouts.app')

@section('title', 'Student Dashboard')

@push('styles')
<style>
    .student-dashboard-shell { padding: 10px 0 0; }
    .student-dashboard-header { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 26px; }
    .student-dashboard-header h1 { margin: 0; color: #f2f7ff; font-size: clamp(2.1rem, 2.3vw, 3rem); font-weight: 800; letter-spacing: -0.04em; }
    .student-dashboard-header p { margin-top: 8px; color: rgba(197, 214, 234, .8); font-size: 1.05rem; }
    .student-dashboard-search { position: relative; width: min(420px, 42vw); min-width: 260px; }
    .student-dashboard-search .form-control { height: 46px; padding-left: 44px; border: 1px solid rgba(113, 180, 214, .18); border-radius: 12px; background: rgba(15, 32, 47, .9); color: #eaf6ff; }
    .student-dashboard-search i { position: absolute; top: 50%; left: 16px; z-index: 2; color: #88a9c3; transform: translateY(-50%); }
    .student-dashboard-shell .card, .student-dashboard-shell .list-group-item { border-color: rgba(110, 176, 215, .18) !important; background: rgba(10, 22, 35, .82) !important; color: #ebf5ff; }
    .student-dashboard-shell .card { border-radius: 18px; box-shadow: 0 8px 30px rgba(2, 8, 18, .2); }
    .student-dashboard-shell .card-body { padding: 1.35rem 1.25rem; }
    .student-dashboard-shell > .row:first-of-type .card { position: relative; overflow: hidden; background: linear-gradient(145deg, rgba(14,34,51,.96), rgba(8,21,34,.94)) !important; }
    .student-dashboard-shell > .row:first-of-type .card::before { content: ""; position: absolute; inset: 0 0 auto; height: 2px; background: linear-gradient(90deg, transparent, rgba(41,212,255,.9), transparent); }
    .student-dashboard-shell > .row:first-of-type .card .h3 { font-size: 2rem; }
    .student-dashboard-shell .h3 { color: #eef8ff; font-weight: 800; letter-spacing: -.04em; }
    .student-dashboard-shell .btn-primary { border: 0; background: linear-gradient(135deg, #29d4ff, #25c7ff) !important; color: #062338 !important; font-weight: 700; box-shadow: 0 8px 20px rgba(37,194,255,.18); }
    .student-dashboard-shell .btn-outline-primary { border-color: rgba(77,210,255,.5); color: #7fe0ff; }
    .student-dashboard-shell .card:hover { border-color: rgba(49,217,244,.36) !important; }
    .student-dashboard-shell .card-header { border-bottom: 1px solid rgba(110, 176, 215, .16); background: rgba(11, 27, 41, .92) !important; color: #ebf5ff; }
    .student-dashboard-shell .card-title, .student-dashboard-shell h5, .student-dashboard-shell h6 { color: rgba(221, 235, 248, .9); }
    .student-dashboard-shell .text-muted, .student-dashboard-shell small, .student-dashboard-shell .small, .student-dashboard-shell .table td { color: rgba(176, 201, 219, .82) !important; }
    .student-dashboard-shell .progress { background: rgba(120, 148, 175, .18); border-radius: 999px; }
    .student-dashboard-shell .progress-bar { border-radius: 999px; background: linear-gradient(90deg, #27d4ff, #4fd1ff); }
    .student-dashboard-shell .list-group-item { border-top: 1px solid rgba(110, 176, 215, .12); transition: background .18s ease, transform .18s ease; }
    .student-dashboard-shell .list-group-item:hover { background: rgba(16, 39, 59, .9) !important; transform: translateX(2px); }
    .student-dashboard-shell .badge { border-radius: 999px; padding: .45rem .7rem; }
    .student-dashboard-shell .table thead th { background: rgba(12,28,40,.9); color: rgba(220,235,246,.9); border-color: rgba(110,176,215,.16); }
    @media (max-width: 991.98px) { .student-dashboard-header { align-items: flex-start; flex-direction: column; } .student-dashboard-search { width: 100%; min-width: 100%; } }
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

                <ol class="mb-0 ps-3">

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
                                class="badge"
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