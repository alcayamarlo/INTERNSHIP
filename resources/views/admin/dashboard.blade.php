@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')

<x-page-header title="System Dashboard" subtitle="Platform analytics and administration" eyebrow="Command center">
    <span class="sb-status sb-status--success">All systems operational</span>
    <span class="sb-tag"><i class="bi bi-calendar3" aria-hidden="true"></i> {{ now()->format('M d, Y') }}</span>
    <a href="{{ route('admin.skill-categories.index') }}" class="btn btn-outline-primary">Manage Skill Categories</a>
</x-page-header>

<x-dashboard-ojt />


{{-- Platform statistics --}}

<div class="sb-stat-grid sb-stat-grid--3">

    <x-dashboard-stat-card title="Users" :value="$stats['users']" icon="bi-people" :href="route('admin.users.index')" description="Manage accounts →" />

    <x-dashboard-stat-card title="Students" :value="$stats['students']" icon="bi-mortarboard" tone="info" />

    <x-dashboard-stat-card title="Employers" :value="$stats['employers']" icon="bi-building" tone="warning" />

    <x-dashboard-stat-card title="Internships" :value="$stats['internships']" icon="bi-briefcase" description="Active opportunities" />

    <x-dashboard-stat-card title="Applications" :value="$stats['applications']" icon="bi-file-earmark-text" tone="info" description="Applications tracked" />

    <x-dashboard-stat-card title="Placements" :value="$stats['placements']" icon="bi-pin-map" tone="success" description="Completed matches" />

</div>


<div class="sb-layout-2">

    {{-- Quick actions --}}

    <div class="card">
        <div class="card-header">
            <h2><i class="bi bi-lightning-charge" aria-hidden="true"></i> Quick actions</h2>
        </div>
        <div class="card-body">
            <div class="sb-quick-actions">
                <a href="{{ route('admin.users.index') }}" class="sb-quick-action"><span class="sb-quick-action-icon" aria-hidden="true"><i class="bi bi-person-plus"></i></span> Add user</a>
                <a href="{{ route('admin.announcements.index') }}" class="sb-quick-action"><span class="sb-quick-action-icon" aria-hidden="true"><i class="bi bi-megaphone"></i></span> Announcement</a>
                <a href="{{ route('admin.logs') }}" class="sb-quick-action"><span class="sb-quick-action-icon" aria-hidden="true"><i class="bi bi-journal-text"></i></span> System logs</a>
                <a href="{{ route('admin.reports') }}" class="sb-quick-action"><span class="sb-quick-action-icon" aria-hidden="true"><i class="bi bi-file-earmark-bar-graph"></i></span> Reports</a>
            </div>
        </div>
    </div>

    {{-- Recent users --}}

    <div class="card">
        <div class="card-header">
            <h2><i class="bi bi-person-check" aria-hidden="true"></i> Recent users</h2>
        </div>
        <div class="card-body d-flex align-items-center gap-3">
            <div class="sb-avatar-stack" aria-hidden="true">
                @forelse($recentUsers->take(5) as $recentUser)
                    <span class="sb-avatar-sm" title="{{ $recentUser->name }}">{{ strtoupper(substr($recentUser->name, 0, 1)) }}</span>
                @empty
                    <span class="sb-avatar-sm">-</span>
                @endforelse
            </div>
            <div>
                <strong>{{ $recentUsers->count() }} recent accounts</strong>
                <div class="text-muted small">Latest platform registrations</div>
            </div>
        </div>
    </div>

</div>


{{-- Platform insights --}}

<div class="sb-section-heading">
    <h2>Platform insights</h2>
    <p>Track applications, competencies, and the skills employers need most.</p>
</div>

@include('analytics.charts', ['analyticsUrl' => route('analytics.charts')])


<div class="row g-4">

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h2><i class="bi bi-activity" aria-hidden="true"></i> Recent System Logs</h2>
                <a href="{{ route('admin.logs') }}" class="sb-card-link">View all →</a>
            </div>

            @if($recentLogs->count())
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead><tr><th scope="col">User</th><th scope="col">Action</th><th scope="col">Time</th></tr></thead>
                        <tbody>
                            @foreach($recentLogs as $log)
                                <tr>
                                    <td>{{ $log->user?->name ?? 'System' }}</td>
                                    <td><code>{{ $log->action }}</code></td>
                                    <td class="text-nowrap">{{ $log->created_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-empty-state icon="bi-journal-text" title="No logs yet" message="System activity will be recorded here." />
            @endif
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h2><i class="bi bi-megaphone" aria-hidden="true"></i> Recent Announcements</h2>
                <a href="{{ route('admin.announcements.index') }}" class="sb-card-link">Manage →</a>
            </div>

            @if($announcements->count())
                <ul class="sb-list">
                    @foreach($announcements as $announcement)
                        <li class="sb-list-item">
                            <div>
                                <p class="sb-list-title">{{ $announcement->title }}</p>
                                <p class="sb-list-text">{{ Str::limit($announcement->content, 80) }}</p>
                            </div>
                            <span class="sb-list-meta">{{ $announcement->published_at?->format('M d, Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <x-empty-state icon="bi-megaphone" title="No announcements" message="Publish an announcement to reach every user.">
                    <a href="{{ route('admin.announcements.index') }}" class="btn btn-primary">New announcement</a>
                </x-empty-state>
            @endif
        </div>
    </div>

</div>

@endsection
