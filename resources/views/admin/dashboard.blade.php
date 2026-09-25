@extends('layouts.app')

@section('title', 'Admin Dashboard')

@push('styles')
<style>
    .admin-dashboard-shell {
        position: relative;
        isolation: isolate;
        min-height: calc(100vh - 72px);
        margin: -30px;
        padding: 30px;
        color: #edf8ff;
        background:
            linear-gradient(145deg, #0b1722 0%, #101d2a 36%, #122230 100%);
    }

    .admin-dashboard-shell::before {
        content: "";
        position: absolute;
        inset: 0;
        z-index: -1;
        opacity: 0.18;
        background-image:
            linear-gradient(rgba(148, 163, 184, 0.04) 1px, transparent 1px),
            linear-gradient(90deg, rgba(148, 163, 184, 0.04) 1px, transparent 1px);
        background-size: 34px 34px;
        mask-image: linear-gradient(to bottom, black, transparent 84%);
        pointer-events: none;
    }

    .admin-dashboard-shell::after {
        content: "";
        position: absolute;
        inset: 0 auto auto 0;
        width: 100%;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(148, 163, 184, 0.35), transparent);
        pointer-events: none;
    }

    .admin-dashboard-header {
        position: relative;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 28px;
    }

    .admin-dashboard-eyebrow {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
        color: #b7c7d8;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.16em;
        text-transform: uppercase;
    }

    .admin-dashboard-eyebrow::before {
        content: "";
        width: 28px;
        height: 2px;
        border-radius: 999px;
        background: rgba(183, 199, 216, 0.85);
        box-shadow: none;
    }

    .admin-dashboard-header h1 {
        margin: 0;
        color: #f5fbff;
        font-size: clamp(2.2rem, 2.5vw, 3.3rem);
        font-weight: 800;
        letter-spacing: -0.05em;
    }

    .admin-dashboard-header p {
        max-width: 620px;
        margin: 7px 0 0;
        color: rgba(191, 215, 232, 0.8);
        font-size: 1rem;
        line-height: 1.55;
    }

    .admin-dashboard-date {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0.7rem 0.95rem;
        border: 1px solid rgba(148, 180, 212, 0.22);
        border-radius: 12px;
        background: rgba(10, 24, 37, 0.72);
        color: rgba(222, 236, 247, 0.9);
        font-size: 0.75rem;
        font-weight: 700;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
    }

    .admin-dashboard-date i {
        color: #b7c7d8;
    }

    .admin-dashboard-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-top: 10px;
        color: rgba(189, 216, 233, 0.8);
        font-size: 0.68rem;
        font-weight: 700;
    }

    .admin-dashboard-status::before {
        content: "";
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #7ac6a4;
        box-shadow: none;
    }

    .admin-dashboard-help {
        display: block;
        margin-top: 6px;
        color: rgba(167, 197, 218, 0.72);
        font-size: 0.66rem;
    }

    .admin-dashboard-ribbon {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 24px;
    }

    .admin-ribbon-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        border: 1px solid rgba(148, 180, 212, 0.18);
        border-radius: 14px;
        background: linear-gradient(180deg, rgba(10, 26, 38, 0.88), rgba(9, 18, 29, 0.84));
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
    }

    .admin-ribbon-item i {
        width: 32px;
        height: 32px;
        display: grid;
        place-items: center;
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 10px;
        background: rgba(148, 163, 184, 0.06);
        color: #b7c7d8;
        font-size: 0.95rem;
    }

    .admin-ribbon-item span {
        display: block;
        color: rgba(162, 191, 210, 0.75);
        font-size: 0.62rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .admin-ribbon-item strong {
        display: block;
        margin-top: 4px;
        color: #eefbff;
        font-size: 0.82rem;
    }

    .admin-stat-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 25px;
    }

    .admin-stat-card {
        position: relative;
        min-height: 118px;
        overflow: hidden;
        border: 1px solid rgba(148, 180, 212, 0.2);
        border-radius: 16px;
        background: linear-gradient(180deg, rgba(14, 32, 50, 0.94), rgba(10, 21, 32, 0.96));
        box-shadow: 0 10px 24px rgba(2, 8, 18, 0.26);
        transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .admin-stat-card:hover {
        transform: translateY(-4px);
        border-color: rgba(148, 163, 184, 0.3);
        box-shadow: 0 12px 28px rgba(2, 8, 18, 0.24);
    }

    .admin-stat-card::before {
        content: "";
        position: absolute;
        inset: 0 0 auto;
        height: 2px;
        background: rgba(183, 199, 216, 0.7);
    }

    .admin-stat-card::after {
        content: "";
        position: absolute;
        right: -25px;
        bottom: -30px;
        width: 92px;
        height: 92px;
        border: 1px solid rgba(148, 163, 184, 0.08);
        border-radius: 50%;
        box-shadow: none;
    }

    .admin-stat-card:nth-child(2)::before { background: rgba(148, 163, 184, 0.7); }
    .admin-stat-card:nth-child(3)::before { background: rgba(148, 163, 184, 0.7); }
    .admin-stat-card:nth-child(4)::before { background: rgba(148, 163, 184, 0.7); }
    .admin-stat-card:nth-child(5)::before { background: rgba(148, 163, 184, 0.7); }
    .admin-stat-card:nth-child(6)::before { background: rgba(148, 163, 184, 0.7); }

    .admin-stat-card .card-body {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 118px;
        padding: 0.9rem 0.7rem;
        text-align: center;
    }

    .admin-stat-card .stat-icon {
        width: 30px;
        height: 30px;
        display: grid;
        place-items: center;
        margin: 0 auto 8px;
        border: 1px solid rgba(94, 233, 255, 0.22);
        border-radius: 9px;
        background: rgba(94, 233, 255, 0.08);
        color: #67dffc;
        font-size: 0.76rem;
    }

    .admin-stat-card .stat-label {
        margin-bottom: 7px;
        color: rgba(185, 206, 223, 0.8);
        font-size: 0.72rem;
        font-weight: 600;
    }

    .admin-stat-card .stat-value {
        color: #f4fbff;
        font-size: clamp(1.65rem, 1.8vw, 2.35rem);
        font-weight: 800;
        letter-spacing: -0.05em;
        line-height: 1;
    }

    .admin-dashboard-shell .card {
        overflow: hidden;
        border: 1px solid rgba(148, 180, 212, 0.18);
        border-radius: 18px;
        background: linear-gradient(180deg, rgba(12, 28, 43, 0.96), rgba(9, 20, 31, 0.96));
        box-shadow: 0 14px 28px rgba(2, 9, 20, 0.18);
    }

    .admin-dashboard-shell .card:hover {
        border-color: rgba(108, 94, 255, 0.28);
    }

    .admin-dashboard-shell .card-header {
        padding: 0.8rem 1rem;
        border-bottom: 1px solid rgba(148, 180, 212, 0.12);
        background: linear-gradient(90deg, rgba(18, 47, 66, 0.9), rgba(12, 23, 34, 0.9)) !important;
        color: #edf7ff;
    }

    .admin-dashboard-shell .card-header h5 {
        margin: 0;
        color: #edf7ff;
        font-size: 0.88rem;
        font-weight: 700;
    }

    .admin-dashboard-shell .card-header h5 i {
        margin-right: 7px;
        color: #b7c7d8;
    }

    .admin-dashboard-shell .card-body {
        padding: 0.9rem 1rem;
    }

    .admin-dashboard-shell .table {
        --bs-table-bg: transparent;
        --bs-table-color: rgba(205, 225, 239, 0.9);
        --bs-table-border-color: rgba(148, 180, 212, 0.12);
    }

    .admin-dashboard-shell .table thead th {
        background: rgba(11, 24, 35, 0.95) !important;
        color: rgba(205, 225, 239, 0.82) !important;
        border-bottom: 1px solid rgba(148, 180, 212, 0.12);
        font-size: 0.7rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .admin-dashboard-shell .table td,
    .admin-dashboard-shell .list-group-item {
        color: rgba(202, 220, 236, 0.85) !important;
        border-color: rgba(148, 180, 212, 0.08);
    }

    .admin-dashboard-shell .list-group-item {
        background: transparent;
        padding: 0.9rem 1rem;
        transition: background 0.2s ease, padding-left 0.2s ease;
    }

    .admin-dashboard-shell .list-group-item:hover {
        background: rgba(94, 233, 255, 0.04);
        padding-left: 1.1rem;
    }

    .admin-dashboard-shell .table tbody tr {
        transition: background 0.18s ease;
    }

    .admin-dashboard-shell .table tbody tr:hover {
        background: rgba(94, 233, 255, 0.04);
    }

    .admin-dashboard-shell code {
        color: #8be8f7;
        font-size: 0.72rem;
    }

    .admin-dashboard-shell .activity-user {
        color: #edf8ff;
        font-weight: 700;
    }

    .admin-utility-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
        gap: 16px;
        margin-bottom: 25px;
    }

    .admin-quick-actions {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 9px;
    }

    .admin-quick-action {
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 56px;
        padding: 9px 10px;
        border: 1px solid rgba(148, 180, 212, 0.14);
        border-radius: 12px;
        background: linear-gradient(180deg, rgba(13, 31, 46, 0.88), rgba(10, 22, 34, 0.84));
        color: #bfd7eb;
        transition: transform 0.18s ease, border-color 0.18s ease, background 0.18s ease;
    }

    .admin-quick-action:hover {
        transform: translateY(-2px);
        border-color: rgba(148, 163, 184, 0.22);
        background: linear-gradient(180deg, rgba(17, 34, 46, 0.92), rgba(12, 27, 39, 0.88));
        color: #f2fbff;
        text-decoration: none;
    }

    .admin-quick-action::after {
        content: "";
        margin-left: auto;
        width: 6px;
        height: 6px;
        border-top: 1px solid rgba(147, 234, 255, 0.7);
        border-right: 1px solid rgba(147, 234, 255, 0.7);
        transform: rotate(45deg);
        opacity: 0.7;
    }

    .admin-quick-action i {
        width: 30px;
        height: 30px;
        display: grid;
        place-items: center;
        border-radius: 9px;
        background: rgba(148, 163, 184, 0.08);
        color: #c7d4df;
        font-size: 0.8rem;
    }

    .admin-quick-action span {
        font-size: 0.68rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .admin-recent-users {
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 56px;
        padding: 9px 12px;
        border: 1px solid rgba(148, 180, 212, 0.14);
        border-radius: 12px;
        background: linear-gradient(180deg, rgba(13, 31, 46, 0.82), rgba(10, 22, 34, 0.82));
        transition: border-color 0.18s ease, background 0.18s ease;
    }

    .admin-recent-users .user-stack {
        display: flex;
        padding-left: 5px;
    }

    .admin-recent-users .user-avatar {
        width: 28px;
        height: 28px;
        display: grid;
        place-items: center;
        margin-left: -5px;
        border: 2px solid rgba(8, 21, 31, 0.95);
        border-radius: 50%;
        background: linear-gradient(135deg, #0f99bf, #56d1ff);
        color: #062033;
        font-size: 0.62rem;
        font-weight: 800;
    }

    .admin-recent-users strong {
        display: block;
        color: #edf8ff;
        font-size: 0.7rem;
    }

    .admin-recent-users small {
        color: rgba(171, 201, 218, 0.72);
        font-size: 0.61rem;
    }

    .admin-recent-users:hover {
        border-color: rgba(148, 163, 184, 0.22);
        background: linear-gradient(180deg, rgba(17, 34, 46, 0.92), rgba(12, 27, 39, 0.88));
    }

    .admin-recent-users .user-avatar:nth-child(2n) { background: linear-gradient(135deg, #2e7ae6, #8ec5ff); }
    .admin-recent-users .user-avatar:nth-child(3n) { background: linear-gradient(135deg, #34d399, #a7f3d0); }
    .admin-recent-users .user-avatar:nth-child(4n) { background: linear-gradient(135deg, #a78bfa, #ddd6fe); }

    .admin-dashboard-shell .btn-outline-primary {
        border-color: rgba(94, 233, 255, 0.6);
        border-radius: 10px;
        color: #8ae8ff;
        font-size: 0.72rem;
        font-weight: 700;
    }

    .admin-dashboard-shell .btn-outline-primary:hover {
        background: rgba(94, 233, 255, 0.12);
        color: #dffbff;
    }

    .admin-dashboard-shell .badge {
        border-radius: 999px;
        padding: 0.45rem 0.75rem;
    }

    .admin-insights-label {
        display: flex;
        align-items: center;
        gap: 9px;
        margin: 0 0 12px;
        color: rgba(211, 230, 243, 0.88);
        font-size: 0.74rem;
        font-weight: 800;
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }

    .admin-insights-label::before {
        content: "";
        width: 22px;
        height: 2px;
        border-radius: 999px;
        background: rgba(183, 199, 216, 0.85);
    }

    .admin-chart-card .card-body {
        min-height: 175px;
        position: relative;
    }

    .admin-chart-card canvas {
        width: 100% !important;
        height: 135px !important;
    }

    .admin-chart-card .card-body::after {
        content: "Live data";
        position: absolute;
        right: 12px;
        bottom: 8px;
        color: rgba(143, 177, 199, 0.65);
        font-size: 0.58rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .admin-section-description {
        margin: -6px 0 12px;
        color: rgba(157, 188, 208, 0.72);
        font-size: 0.68rem;
    }

    @media (max-width:1200px) {
        .admin-stat-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }

    @media (max-width:900px) {
        .admin-utility-grid { grid-template-columns: 1fr; }
    }

    @media (max-width:767.98px) {
        .admin-dashboard-shell {
            margin: -20px -15px;
            padding: 24px 15px 32px;
        }

        .admin-dashboard-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .admin-stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .admin-dashboard-ribbon { grid-template-columns: 1fr; }
        .admin-quick-actions { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width:480px) {
        .admin-stat-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="admin-dashboard-shell">
<div class="admin-dashboard-header">
    <div>
    <div class="admin-dashboard-eyebrow">Command center</div>
    <h1 class="h3 mb-1">System Dashboard</h1>
    <p class="text-muted mb-0">Platform analytics and administration</p>
    <span class="admin-dashboard-help">Review the overview first, then use quick actions and platform insights below.</span>
    <div class="admin-dashboard-status">All systems operational</div>
</div>
    <div class="admin-dashboard-date"><i class="bi bi-calendar3"></i> {{ now()->format('M d, Y') }}</div>
</div>

<div class="admin-dashboard-ribbon">
    <div class="admin-ribbon-item"><i class="bi bi-activity"></i><div><span>Platform activity</span><strong>{{ $stats['applications'] }} applications tracked</strong></div></div>
    <div class="admin-ribbon-item"><i class="bi bi-briefcase"></i><div><span>Internship pipeline</span><strong>{{ $stats['internships'] }} active opportunities</strong></div></div>
    <div class="admin-ribbon-item"><i class="bi bi-check2-circle"></i><div><span>Successful placements</span><strong>{{ $stats['placements'] }} completed matches</strong></div></div>
</div>

<div class="admin-stat-grid">
    @foreach(['users' => 'Users', 'students' => 'Students', 'employers' => 'Employers', 'internships' => 'Internships', 'applications' => 'Applications', 'placements' => 'Placements'] as $key => $label)
        <div class="admin-stat-card">
                <div class="card-body text-center">
                    <div>
                        <div class="stat-icon"><i class="bi bi-{{ ['users' => 'people', 'students' => 'mortarboard', 'employers' => 'building', 'internships' => 'briefcase', 'applications' => 'file-earmark-text', 'placements' => 'pin-map'][$key] }}"></i></div>
                        <p class="stat-label mb-0">{{ $label }}</p>
                        <h3 class="stat-value mb-0">{{ $stats[$key] }}</h3>
                    </div>
                </div>
        </div>
    @endforeach
</div>

<div class="admin-utility-grid">
    <div>
        <div class="admin-insights-label">Quick actions</div>
        <div class="admin-quick-actions">
            <a href="{{ route('admin.users.index') }}" class="admin-quick-action"><i class="bi bi-person-plus"></i><span>Add user</span></a>
            <a href="{{ route('admin.announcements.index') }}" class="admin-quick-action"><i class="bi bi-megaphone"></i><span>Announcement</span></a>
            <a href="{{ route('admin.logs') }}" class="admin-quick-action"><i class="bi bi-journal-text"></i><span>System logs</span></a>
            <a href="{{ route('admin.reports') }}" class="admin-quick-action"><i class="bi bi-file-earmark-bar-graph"></i><span>Reports</span></a>
        </div>
    </div>
    <div>
        <div class="admin-insights-label">Recent users</div>
        <div class="admin-recent-users">
            <div class="user-stack">
                @forelse($recentUsers->take(5) as $recentUser)
                    <div class="user-avatar" title="{{ $recentUser->name }}">{{ strtoupper(substr($recentUser->name, 0, 1)) }}</div>
                @empty
                    <div class="user-avatar">-</div>
                @endforelse
            </div>
            <div><strong>{{ $recentUsers->count() }} recent accounts</strong><small>Latest platform registrations</small></div>
        </div>
    </div>
</div>

<div class="admin-insights-label">Platform insights</div>
<p class="admin-section-description">Track applications, competencies, and the skills employers need most.</p>
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card admin-chart-card h-100">
            <div class="card-header"><h5 class="mb-0"><i class="bi bi-graph-up"></i>Applications per Month</h5></div>
            <div class="card-body"><canvas id="applicationsChart" height="200"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card admin-chart-card h-100">
            <div class="card-header"><h5 class="mb-0"><i class="bi bi-pie-chart"></i>Application Status</h5></div>
            <div class="card-body"><canvas id="statusChart" height="200"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card admin-chart-card h-100">
            <div class="card-header"><h5 class="mb-0"><i class="bi bi-bar-chart"></i>Competency Levels</h5></div>
            <div class="card-body"><canvas id="competencyChart" height="200"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card admin-chart-card h-100">
            <div class="card-header"><h5 class="mb-0"><i class="bi bi-stars"></i>Top Required Skills</h5></div>
            <div class="card-body"><canvas id="skillsChart" height="200"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between">
                <h5 class="mb-0"><i class="bi bi-activity"></i>Recent System Logs</h5>
                <a href="{{ route('admin.logs') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>User</th><th>Action</th><th>Time</th></tr></thead>
                    <tbody>
                        @forelse($recentLogs as $log)
                            <tr>
                                <td class="activity-user">{{ $log->user?->name ?? 'System' }}</td>
                                <td><code>{{ $log->action }}</code></td>
                                <td>{{ $log->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">No logs.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between">
                <h5 class="mb-0"><i class="bi bi-megaphone"></i>Recent Announcements</h5>
                <a href="{{ route('admin.announcements.index') }}" class="btn btn-sm btn-outline-primary">Manage</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse($announcements as $announcement)
                    <div class="list-group-item">
                        <strong>{{ $announcement->title }}</strong>
                        <p class="mb-0 small text-muted">{{ Str::limit($announcement->content, 80) }}</p>
                        <small class="text-muted">{{ $announcement->published_at?->format('M d, Y') }}</small>
                    </div>
                @empty
                    <div class="list-group-item text-muted text-center">No announcements.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
    const analyticsUrl = "{{ route('analytics.charts') }}";
    Chart.defaults.color = '#9fb6c9';
    Chart.defaults.font.family = 'Inter, sans-serif';
    Chart.defaults.font.size = 10;

    fetch(analyticsUrl)
        .then(r => r.json())
        .then(data => {
            new Chart(document.getElementById('applicationsChart'), {
                type: 'line',
                data: {
                    labels: data.applications_per_month.map(i => i.month),
                    datasets: [{ label: 'Applications', data: data.applications_per_month.map(i => i.total), borderColor: '#2563EB', backgroundColor: 'rgba(37,99,235,.1)', fill: true }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
            });
            new Chart(document.getElementById('statusChart'), {
                type: 'doughnut',
                data: {
                    labels: data.placements.map(i => i.status),
                    datasets: [{ data: data.placements.map(i => i.total), backgroundColor: ['#2563EB','#10B981','#f59e0b','#ef4444','#6366f1','#64748b'] }]
                },
                options: { responsive: true, maintainAspectRatio: false, cutout: '62%' }
            });
            new Chart(document.getElementById('competencyChart'), {
                type: 'bar',
                data: {
                    labels: data.competency_levels.map(i => i.proficiency_level),
                    datasets: [{ label: 'Count', data: data.competency_levels.map(i => i.total), backgroundColor: '#10B981' }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
            });
            new Chart(document.getElementById('skillsChart'), {
                type: 'bar',
                data: {
                    labels: data.top_skills.map(i => i.requirement_name),
                    datasets: [{ label: 'Demand', data: data.top_skills.map(i => i.total), backgroundColor: '#2563EB' }]
                },
                options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
            });
        });
</script>
@endpush
