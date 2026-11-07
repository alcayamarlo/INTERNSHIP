@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')

{{-- Layout E: main column (header, OJT, KPIs, charts) + right rail. Styles: public/css/admin-dashboard.css --}}
<div class="ad-dashboard">

    {{-- =========================================================
         MAIN COLUMN
    ========================================================== --}}
    <div class="ad-main">

        <header class="ad-header ad-reveal" style="--ad-i: 0">
            <div class="ad-header-text">
                <span class="ad-eyebrow">Command center</span>
                <h1 class="ad-title">System Dashboard</h1>
                <p class="ad-subtitle">Platform analytics and administration</p>
            </div>
            <div class="ad-header-actions">
                <span class="ad-status">
                    <span class="ad-status-dot" aria-hidden="true"></span>
                    All systems operational
                </span>
                <span class="ad-date"><i class="bi bi-calendar3" aria-hidden="true"></i> {{ now()->format('M d, Y') }}</span>
                <a href="{{ route('admin.skill-categories.index') }}" class="ad-btn-outline">Manage Skill Categories</a>
            </div>
        </header>

        <div class="ad-reveal ad-ojt" style="--ad-i: 1">
            <x-dashboard-ojt />
        </div>

        {{-- Platform statistics --}}
        <section class="ad-kpis" aria-label="Platform statistics">
            <x-dashboard-stat-card title="Users" :value="$stats['users']" icon="bi-people" :href="route('admin.users.index')" description="Manage accounts →" />
            <x-dashboard-stat-card title="Students" :value="$stats['students']" icon="bi-mortarboard" tone="info" />
            <x-dashboard-stat-card title="Employers" :value="$stats['employers']" icon="bi-building" tone="warning" />
            <x-dashboard-stat-card title="Internships" :value="$stats['internships']" icon="bi-briefcase" description="Active opportunities" />
            <x-dashboard-stat-card title="Applications" :value="$stats['applications']" icon="bi-file-earmark-text" tone="info" description="Applications tracked" />
            <x-dashboard-stat-card title="Placements" :value="$stats['placements']" icon="bi-pin-map" tone="success" description="Completed matches" />
        </section>

        {{-- Platform insights (same charts and data, admin layout) --}}
        @include('analytics.charts', ['analyticsUrl' => route('analytics.charts'), 'variant' => 'admin'])

    </div>

    {{-- =========================================================
         RIGHT RAIL
    ========================================================== --}}
    <aside class="ad-rail" aria-label="Shortcuts and recent activity">

        {{-- Quick actions --}}
        <section class="ad-card ad-reveal" style="--ad-i: 2" aria-labelledby="adQuickActions">
            <div class="ad-card-head">
                <h2 id="adQuickActions"><i class="bi bi-lightning-charge" aria-hidden="true"></i> Quick actions</h2>
            </div>
            <div class="ad-actions">
                <a href="{{ route('admin.users.index') }}" class="ad-action"><i class="bi bi-person-plus" aria-hidden="true"></i><span>Add user</span></a>
                <a href="{{ route('admin.announcements.index') }}" class="ad-action"><i class="bi bi-megaphone" aria-hidden="true"></i><span>Announcement</span></a>
                <a href="{{ route('admin.logs') }}" class="ad-action"><i class="bi bi-journal-text" aria-hidden="true"></i><span>System logs</span></a>
                <a href="{{ route('admin.reports') }}" class="ad-action"><i class="bi bi-file-earmark-bar-graph" aria-hidden="true"></i><span>Reports</span></a>
            </div>
        </section>

        {{-- Recent users --}}
        <section class="ad-card ad-reveal" style="--ad-i: 3" aria-labelledby="adRecentUsers">
            <div class="ad-card-head">
                <h2 id="adRecentUsers"><i class="bi bi-person-check" aria-hidden="true"></i> Recent users</h2>
                <a href="{{ route('admin.users.index') }}" class="ad-card-link">View all →</a>
            </div>
            @if($recentUsers->count())
                <ul class="ad-list">
                    @foreach($recentUsers->take(5) as $recentUser)
                        <li class="ad-list-item">
                            <span class="ad-avatar" aria-hidden="true">{{ strtoupper(substr($recentUser->name, 0, 1)) }}</span>
                            <div class="ad-list-body">
                                <p class="ad-list-title">{{ $recentUser->name }}</p>
                                <p class="ad-list-text">{{ $recentUser->role?->label() ?? 'User' }}</p>
                            </div>
                            <span class="ad-list-meta">{{ $recentUser->created_at?->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="ad-empty">No accounts yet.</p>
            @endif
        </section>

        {{-- Recent announcements --}}
        <section class="ad-card ad-reveal" style="--ad-i: 4" aria-labelledby="adAnnouncements">
            <div class="ad-card-head">
                <h2 id="adAnnouncements"><i class="bi bi-megaphone" aria-hidden="true"></i> Recent Announcements</h2>
                <a href="{{ route('admin.announcements.index') }}" class="ad-card-link">Manage →</a>
            </div>
            @if($announcements->count())
                <ul class="ad-list">
                    @foreach($announcements as $announcement)
                        <li class="ad-list-item">
                            <div class="ad-list-body">
                                <p class="ad-list-title">{{ $announcement->title }}</p>
                                <p class="ad-list-text">{{ Str::limit($announcement->content, 80) }}</p>
                            </div>
                            <span class="ad-list-meta">{{ $announcement->published_at?->format('M d, Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="ad-empty">
                    <p>No announcements. Publish one to reach every user.</p>
                    <a href="{{ route('admin.announcements.index') }}" class="ad-btn-outline">New announcement</a>
                </div>
            @endif
        </section>

        {{-- Recent system logs --}}
        <section class="ad-card ad-reveal" style="--ad-i: 5" aria-labelledby="adLogs">
            <div class="ad-card-head">
                <h2 id="adLogs"><i class="bi bi-activity" aria-hidden="true"></i> Recent System Logs</h2>
                <a href="{{ route('admin.logs') }}" class="ad-card-link">View all →</a>
            </div>
            @if($recentLogs->count())
                <ul class="ad-list">
                    @foreach($recentLogs as $log)
                        <li class="ad-list-item">
                            <div class="ad-list-body">
                                <p class="ad-list-title"><code>{{ $log->action }}</code></p>
                                <p class="ad-list-text">{{ $log->user?->name ?? 'System' }}</p>
                            </div>
                            <span class="ad-list-meta">{{ $log->created_at->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="ad-empty">No logs yet. System activity will be recorded here.</p>
            @endif
        </section>

    </aside>

</div>

@endsection
