@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')

{{-- Main column (OJT, platform statistics, charts) + right rail. Shared components only (public/css/sb-theme.css). --}}
<div class="sb-dash">

<x-page-header title="System Dashboard" subtitle="Platform analytics and administration" eyebrow="Command center">
    <span class="sb-tag"><i class="bi bi-calendar3" aria-hidden="true"></i> {{ now()->format('M d, Y') }}</span>
    <a href="{{ route('admin.skill-tags.index') }}" class="btn btn-outline-primary">Manage Student Skill Tags</a>
</x-page-header>

<div class="sb-layout-2">

    {{-- =========================================================
         MAIN COLUMN
    ========================================================== --}}
    <div class="sb-dash">

        <x-dashboard-ojt />

        {{-- Platform statistics --}}
        <section class="sb-grid sb-grid--3" aria-label="Platform statistics">
            <x-dashboard-stat-card title="Users" :value="$stats['users']" icon="bi-people" :href="route('admin.users.index')" description="Manage accounts →" />
            <x-dashboard-stat-card title="Students" :value="$stats['students']" icon="bi-mortarboard" tone="info" />
            <x-dashboard-stat-card title="Employers" :value="$stats['employers']" icon="bi-building" tone="warning" />
            <x-dashboard-stat-card title="Internships" :value="$stats['internships']" icon="bi-briefcase" description="Active opportunities" />
            <x-dashboard-stat-card title="Applications" :value="$stats['applications']" icon="bi-file-earmark-text" tone="info" description="Applications tracked" />
            <x-dashboard-stat-card title="Placements" :value="$stats['placements']" icon="bi-pin-map" tone="success" description="Accepted applications" />
        </section>

        {{-- Platform insights (same charts and data) --}}
        @include('analytics.charts', ['analyticsUrl' => route('analytics.charts'), 'variant' => 'admin'])

    </div>

    {{-- =========================================================
         RIGHT RAIL
    ========================================================== --}}
    <aside class="sb-dash" aria-label="Shortcuts and recent activity">

        {{-- Quick actions --}}
        <x-dashboard-quick-actions title="Quick actions" icon="bi-lightning-charge">
            <a href="{{ route('admin.users.index') }}" class="list-group-item list-group-item-action"><i class="bi bi-person-plus" aria-hidden="true"></i> Add user</a>
            <a href="{{ route('admin.announcements.index') }}" class="list-group-item list-group-item-action"><i class="bi bi-megaphone" aria-hidden="true"></i> Announcement</a>
            <a href="{{ route('admin.logs') }}" class="list-group-item list-group-item-action"><i class="bi bi-journal-text" aria-hidden="true"></i> System logs</a>
            <a href="{{ route('admin.reports') }}" class="list-group-item list-group-item-action"><i class="bi bi-file-earmark-bar-graph" aria-hidden="true"></i> Reports</a>
        </x-dashboard-quick-actions>

        {{-- Recent users --}}
        <section class="card" aria-labelledby="adRecentUsers">
            <div class="card-header">
                <h2 id="adRecentUsers"><i class="bi bi-person-check" aria-hidden="true"></i> Recent users</h2>
                <a href="{{ route('admin.users.index') }}" class="sb-card-link">View all →</a>
            </div>
            @if($recentUsers->count())
                <ul class="sb-list">
                    @foreach($recentUsers->take(5) as $recentUser)
                        <li class="sb-list-item sb-list-item--avatar">
                            <span class="sb-initials" aria-hidden="true">{{ strtoupper(substr($recentUser->name, 0, 1)) }}</span>
                            <div>
                                <p class="sb-list-title"><strong>{{ $recentUser->name }}</strong></p>
                                <div class="small text-muted">{{ $recentUser->role?->label() ?? 'User' }}</div>
                            </div>
                            <span class="sb-list-meta">{{ $recentUser->created_at?->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <x-empty-state icon="bi-person-check" title="No accounts yet." />
            @endif
        </section>

        {{-- Recent announcements --}}
        <section class="card" aria-labelledby="adAnnouncements">
            <div class="card-header">
                <h2 id="adAnnouncements"><i class="bi bi-megaphone" aria-hidden="true"></i> Recent Announcements</h2>
                <a href="{{ route('admin.announcements.index') }}" class="sb-card-link">Manage →</a>
            </div>
            @if($announcements->count())
                <ul class="sb-list">
                    @foreach($announcements as $announcement)
                        <li class="sb-list-item">
                            <div>
                                <p class="sb-list-title"><strong>{{ $announcement->title }}</strong></p>
                                <div class="small text-muted">{{ Str::limit($announcement->content, 80) }}</div>
                            </div>
                            <span class="sb-list-meta">{{ $announcement->published_at?->format('M d, Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <x-empty-state icon="bi-megaphone" title="No announcements." message="Publish one to reach every user.">
                    <a href="{{ route('admin.announcements.index') }}" class="btn btn-primary">New announcement</a>
                </x-empty-state>
            @endif
        </section>

        {{-- Recent system logs --}}
        <section class="card" aria-labelledby="adLogs">
            <div class="card-header">
                <h2 id="adLogs"><i class="bi bi-activity" aria-hidden="true"></i> Recent System Logs</h2>
                <a href="{{ route('admin.logs') }}" class="sb-card-link">View all →</a>
            </div>
            @if($recentLogs->count())
                <ul class="sb-list">
                    @foreach($recentLogs as $log)
                        <li class="sb-list-item">
                            <div>
                                <p class="sb-list-title"><code>{{ $log->action }}</code></p>
                                <div class="small text-muted">{{ $log->user?->name ?? 'System' }}</div>
                            </div>
                            <span class="sb-list-meta">{{ $log->created_at->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <x-empty-state icon="bi-activity" title="No logs yet." message="System activity will be recorded here." />
            @endif
        </section>

    </aside>

</div>

</div>{{-- /.sb-dash --}}

@endsection
