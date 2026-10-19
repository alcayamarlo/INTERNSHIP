@extends('layouts.app')

@section('title', 'Employer Dashboard')

@section('content')

@php
    $statTotal = max($applicationStats['total'], 1);
    $successRate = round(($applicationStats['accepted'] ?? 0) / $statTotal * 100);
@endphp

<x-page-header
    :title="'Welcome, '.Auth::user()->name.'!'"
    subtitle="Manage your internship listings and applicants"
>
    <span class="sb-status sb-status--success">Recruitment workspace ready</span>
    <a href="{{ route('employer.internships.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Post Internship</a>
</x-page-header>


{{-- Dashboard statistics --}}

<div class="sb-stat-grid">

    <x-dashboard-stat-card
        title="Pending Review"
        :value="$applicationStats['submitted'] ?? 0"
        icon="bi-hourglass-split"
        tone="warning"
        description="Waiting for your decision"
    />

    <x-dashboard-stat-card
        title="Total Applicants"
        :value="$applicationStats['total']"
        icon="bi-people"
        :href="route('employer.applicants.index')"
        description="View all applicants →"
    />

    <x-dashboard-stat-card
        title="Active Listings"
        :value="$activeInternships"
        icon="bi-briefcase"
        tone="info"
        :href="route('employer.internships.index')"
        description="Manage internships →"
    />

    <x-dashboard-stat-card
        title="Accepted"
        :value="$applicationStats['accepted'] ?? 0"
        icon="bi-check-circle"
        tone="success"
        :description="$successRate.'% success rate'"
    />

</div>


<div class="sb-layout-2">

    {{-- Recent applications --}}

    <div class="card">

        <div class="card-header">
            <h2><i class="bi bi-send" aria-hidden="true"></i> Recent Applications</h2>
            @if($recentApplications->count())
                <a href="{{ route('employer.applicants.index') }}" class="sb-card-link">View all →</a>
            @endif
        </div>

        @if($recentApplications->count())

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th scope="col">Student</th>
                            <th scope="col">Position</th>
                            <th scope="col">Status</th>
                            <th scope="col">Applied</th>
                            <th scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentApplications as $application)
                            <tr>
                                <td>
                                    <strong>{{ $application->student->user->name }}</strong>
                                    <div class="text-muted small">{{ $application->student->user->email }}</div>
                                </td>
                                <td>{{ $application->internship->title }}</td>
                                <td><x-status-badge :status="$application->status->value" :label="$application->status->label()" /></td>
                                <td><span class="text-muted">{{ $application->applied_at->diffForHumans() }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('employer.applicants.show', $application) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @else

            <x-empty-state icon="bi-send" title="No applications yet" message="Applications to your internships will appear here as students apply.">
                <a href="{{ route('employer.internships.create') }}" class="btn btn-primary">Post an internship</a>
            </x-empty-state>

        @endif

    </div>


    {{-- Quick actions --}}

    <x-dashboard-quick-actions title="Quick Actions" icon="bi-lightning-charge">

        <a href="{{ route('employer.internships.create') }}" class="list-group-item list-group-item-action">
            <i class="bi bi-plus-circle" aria-hidden="true"></i> Create New Listing
        </a>
        <a href="{{ route('employer.applicants.index') }}" class="list-group-item list-group-item-action">
            <i class="bi bi-eye" aria-hidden="true"></i> Review Applicants
        </a>
        <a href="{{ route('employer.profile.edit') }}" class="list-group-item list-group-item-action">
            <i class="bi bi-building" aria-hidden="true"></i> Edit Company Profile
        </a>
        <a href="{{ route('messages.index') }}" class="list-group-item list-group-item-action">
            <i class="bi bi-chat-dots" aria-hidden="true"></i> Messages
        </a>

    </x-dashboard-quick-actions>

</div>


<div class="sb-layout-2">

    {{-- Application status breakdown --}}

    <div class="card">

        <div class="card-header">
            <h2><i class="bi bi-bar-chart" aria-hidden="true"></i> Application Status Breakdown</h2>
        </div>

        <div class="card-body">
            <div class="sb-meter">

                @foreach([
                    ['Submitted', $applicationStats['submitted'] ?? 0, 'is-warning'],
                    ['Under Review', $applicationStats['reviewed'] ?? 0, 'is-info'],
                    ['Interviews', $applicationStats['interview'] ?? 0, ''],
                    ['Accepted', $applicationStats['accepted'] ?? 0, 'is-success'],
                ] as [$meterLabel, $meterValue, $meterTone])

                    <div class="sb-meter-row">
                        <div class="sb-meter-head">
                            <span>{{ $meterLabel }}</span>
                            <span class="sb-tag">{{ $meterValue }}</span>
                        </div>
                        <div class="sb-meter-bar" role="progressbar" aria-label="{{ $meterLabel }}" aria-valuenow="{{ round($meterValue / $statTotal * 100) }}" aria-valuemin="0" aria-valuemax="100">
                            <span class="{{ $meterTone }}" style="width: {{ $meterValue / $statTotal * 100 }}%"></span>
                        </div>
                    </div>

                @endforeach

            </div>
        </div>

    </div>


    {{-- Notifications --}}

    <div class="card">

        <div class="card-header">
            <h2><i class="bi bi-bell" aria-hidden="true"></i> Recent Notifications</h2>
        </div>

        @if($notifications->count())
            <ul class="sb-list">
                @foreach($notifications as $notification)
                    <li class="sb-list-item">
                        <div>
                            <p class="sb-list-title">{{ $notification->message }}</p>
                        </div>
                        <time class="sb-list-meta" datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time>
                    </li>
                @endforeach
            </ul>
        @else
            <x-empty-state icon="bi-bell" title="No notifications" message="You're all caught up." />
        @endif

    </div>

</div>

@endsection
