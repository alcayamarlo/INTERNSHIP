@extends('layouts.app')

@section('title', 'Admin Dashboard')

@push('styles')
<style>
    .admin-dashboard-shell { position: relative; isolation: isolate; min-height: calc(100vh - 72px); margin: -30px; padding: 30px; color: #eff8ff; background: radial-gradient(ellipse at 88% 5%, rgba(8,217,245,.08), transparent 31%), linear-gradient(145deg, rgba(5,18,32,.98), rgba(7,24,41,.98) 55%, rgba(5,16,29,.98)); }
    .admin-dashboard-shell::before { content: ""; position: absolute; inset: 0; z-index: -1; opacity: .32; background-image: linear-gradient(rgba(109,178,215,.06) 1px, transparent 1px), linear-gradient(90deg, rgba(109,178,215,.06) 1px, transparent 1px); background-size: 34px 34px; mask-image: linear-gradient(to bottom, black, transparent 80%); pointer-events: none; }
    .admin-dashboard-shell::after { content: ""; position: absolute; top: 0; left: 0; right: 0; height: 1px; background: linear-gradient(90deg, transparent, rgba(49,217,244,.6), transparent); pointer-events: none; }
    .admin-dashboard-header { position: relative; display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 28px; }
    .admin-dashboard-eyebrow { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; color: #31d9f4; font-size: .7rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
    .admin-dashboard-eyebrow::before { content: ""; width: 28px; height: 2px; border-radius: 999px; background: #31d9f4; box-shadow: 0 0 12px rgba(49,217,244,.7); }
    .admin-dashboard-header h1 { margin: 0; color: #f4f9ff; font-size: clamp(2.1rem, 2.4vw, 3.2rem); font-weight: 800; letter-spacing: -.05em; }
    .admin-dashboard-header p { max-width: 620px; margin: 7px 0 0; color: rgba(188,208,228,.8); font-size: 1rem; line-height: 1.55; }
    .admin-dashboard-date { display: inline-flex; align-items: center; gap: 8px; padding: .65rem .85rem; border: 1px solid rgba(118,180,222,.18); border-radius: 10px; background: rgba(12,32,54,.58); color: rgba(202,220,236,.82); font-size: .75rem; font-weight: 700; }
    .admin-dashboard-date i { color: #31d9f4; }
    .admin-dashboard-status { display: inline-flex; align-items: center; gap: 7px; margin-top: 10px; color: rgba(182,210,228,.78); font-size: .68rem; font-weight: 700; }
    .admin-dashboard-status::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: #45df9a; box-shadow: 0 0 10px rgba(69,223,154,.7); }
    .admin-dashboard-help { display: block; margin-top: 5px; color: rgba(151,184,207,.7); font-size: .66rem; }
    .admin-dashboard-ribbon { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 24px; }
    .admin-ribbon-item { display: flex; align-items: center; gap: 10px; padding: 10px 13px; border: 1px solid rgba(118,180,222,.14); border-radius: 11px; background: rgba(12,32,54,.58); }
    .admin-ribbon-item i { color: #31d9f4; font-size: .95rem; }
    .admin-ribbon-item span { display: block; color: rgba(160,188,208,.72); font-size: .62rem; }
    .admin-ribbon-item strong { display: block; margin-top: 2px; color: #edf8ff; font-size: .76rem; }
    .admin-stat-grid { display: grid; grid-template-columns: repeat(6,minmax(0,1fr)); gap: 16px; margin-bottom: 25px; }
    .admin-stat-card { position: relative; min-height: 116px; overflow: hidden; border: 1px solid rgba(118,180,222,.18); border-radius: 14px; background: linear-gradient(180deg,rgba(19,35,52,.92),rgba(12,23,33,.9)); box-shadow: 0 8px 22px rgba(3,8,18,.2); transition: transform .2s ease,border-color .2s ease,box-shadow .2s ease; }
    .admin-stat-card:hover { transform: translateY(-4px); border-color: rgba(49,217,244,.5); box-shadow: 0 16px 34px rgba(3,8,18,.34); }
    .admin-stat-card::before { content: ""; position: absolute; inset: 0 0 auto; height: 2px; background: linear-gradient(90deg,rgba(8,217,245,.15),rgba(8,217,245,.95),rgba(8,217,245,.15)); }
    .admin-stat-card::after { content: ""; position: absolute; right: -28px; bottom: -34px; width: 92px; height: 92px; border: 1px solid rgba(49,217,244,.12); border-radius: 50%; box-shadow: 0 0 0 12px rgba(49,217,244,.025), 0 0 0 24px rgba(49,217,244,.018); }
    .admin-stat-card:nth-child(2)::before { background: linear-gradient(90deg,rgba(96,165,250,.15),rgba(96,165,250,.95),rgba(96,165,250,.15)); }
    .admin-stat-card:nth-child(3)::before { background: linear-gradient(90deg,rgba(52,211,153,.15),rgba(52,211,153,.95),rgba(52,211,153,.15)); }
    .admin-stat-card:nth-child(4)::before { background: linear-gradient(90deg,rgba(251,191,36,.15),rgba(251,191,36,.95),rgba(251,191,36,.15)); }
    .admin-stat-card:nth-child(5)::before { background: linear-gradient(90deg,rgba(167,139,250,.15),rgba(167,139,250,.95),rgba(167,139,250,.15)); }
    .admin-stat-card .card-body { display: flex; align-items: center; justify-content: center; min-height: 116px; padding: .85rem .7rem; text-align: center; }
    .admin-stat-card .stat-icon { width: 30px; height: 30px; display: grid; place-items: center; margin: 0 auto 7px; border: 1px solid rgba(49,217,244,.22); border-radius: 9px; background: rgba(8,217,245,.1); color: #37d9f3; font-size: .78rem; }
    .admin-stat-card .stat-label { margin-bottom: 6px; color: rgba(188,208,228,.75); font-size: .73rem; font-weight: 600; }
    .admin-stat-card .stat-value { color: #f3f9ff; font-size: clamp(1.65rem,1.8vw,2.35rem); font-weight: 800; letter-spacing: -.05em; line-height: 1; }
    .admin-dashboard-shell .card { overflow: hidden; border: 1px solid rgba(118,180,222,.18); border-radius: 18px; background: linear-gradient(180deg,rgba(15,31,48,.96),rgba(9,23,35,.96)); box-shadow: 0 10px 28px rgba(2,9,20,.15); }
    .admin-dashboard-shell .card:hover { border-color: rgba(49,217,244,.3); }
    .admin-dashboard-shell .card-header { padding: .8rem 1rem; border-bottom: 1px solid rgba(118,180,222,.12); background: linear-gradient(90deg,rgba(16,48,67,.82),rgba(10,24,36,.88)) !important; color: #edf7ff; }
    .admin-dashboard-shell .card-header h5 { margin: 0; color: #edf7ff; font-size: .88rem; font-weight: 700; }
    .admin-dashboard-shell .card-header h5 i { margin-right: 7px; color: #31d9f4; }
    .admin-dashboard-shell .card-body { padding: .9rem 1rem; }
    .admin-dashboard-shell .table { --bs-table-bg: transparent; --bs-table-color: rgba(202,220,236,.85); --bs-table-border-color: rgba(118,180,222,.12); }
    .admin-dashboard-shell .table thead th { background: rgba(12,29,41,.9) !important; color: rgba(202,220,236,.85) !important; border-bottom: 1px solid rgba(118,180,222,.12); font-size: .7rem; letter-spacing: .06em; text-transform: uppercase; }
    .admin-dashboard-shell .table td,.admin-dashboard-shell .list-group-item { color: rgba(202,220,236,.85) !important; border-color: rgba(118,180,222,.08); }
    .admin-dashboard-shell .list-group-item { background: transparent; padding: .9rem 1rem; transition: background .2s ease,padding-left .2s ease; }
    .admin-dashboard-shell .list-group-item:hover { background: rgba(49,217,244,.05); padding-left: 1.2rem; }
    .admin-dashboard-shell .table tbody tr { transition: background .18s ease; }
    .admin-dashboard-shell .table tbody tr:hover { background: rgba(49,217,244,.045); }
    .admin-dashboard-shell code { color: #8be8f7; font-size: .72rem; }
    .admin-dashboard-shell .activity-user { color: #edf8ff; font-weight: 700; }
    .admin-utility-grid { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(0, .9fr); gap: 16px; margin-bottom: 25px; }
    .admin-quick-actions { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 9px; }
    .admin-quick-action { display: flex; align-items: center; gap: 9px; min-height: 54px; padding: 9px 10px; border: 1px solid rgba(118,180,222,.14); border-radius: 11px; background: rgba(12,32,54,.58); color: #b8d1e2; transition: transform .18s ease, border-color .18s ease, background .18s ease; }
    .admin-quick-action:hover { transform: translateY(-2px); border-color: rgba(49,217,244,.42); background: rgba(20,54,76,.72); color: #effaff; }
    .admin-quick-action::after { content: ""; margin-left: auto; width: 6px; height: 6px; border-top: 1px solid rgba(127,231,248,.7); border-right: 1px solid rgba(127,231,248,.7); transform: rotate(45deg); opacity: .55; }
    .admin-quick-action i { width: 28px; height: 28px; display: grid; place-items: center; border-radius: 8px; background: rgba(8,217,245,.1); color: #31d9f4; font-size: .8rem; }
    .admin-quick-action span { font-size: .68rem; font-weight: 700; line-height: 1.2; }
    .admin-recent-users { display: flex; align-items: center; gap: 9px; min-height: 54px; padding: 9px 12px; border: 1px solid rgba(118,180,222,.14); border-radius: 11px; background: rgba(12,32,54,.58); }
    .admin-recent-users .user-stack { display: flex; padding-left: 5px; }
    .admin-recent-users .user-avatar { width: 27px; height: 27px; display: grid; place-items: center; margin-left: -5px; border: 2px solid #0b2036; border-radius: 50%; background: #164762; color: #7fe7f8; font-size: .62rem; font-weight: 800; }
    .admin-recent-users strong { display: block; color: #edf8ff; font-size: .7rem; }
    .admin-recent-users small { color: rgba(171,201,218,.72); font-size: .61rem; }
    .admin-recent-users:hover { border-color: rgba(49,217,244,.38); background: rgba(20,54,76,.72); }
    .admin-recent-users .user-avatar:nth-child(2n) { background: #23435d; color: #9deaf5; }
    .admin-recent-users .user-avatar:nth-child(3n) { background: #28435d; color: #b9c8ff; }
    .admin-dashboard-shell .btn-outline-primary { border-color: rgba(82,200,255,.6); border-radius: 10px; color: #88e0ff; font-size: .72rem; font-weight: 700; }
    .admin-dashboard-shell .btn-outline-primary:hover { background: rgba(82,200,255,.12); color: #dff9ff; }
    .admin-dashboard-shell .badge { border-radius: 999px; padding: .45rem .75rem; }
    .admin-insights-label { display: flex; align-items: center; gap: 9px; margin: 0 0 12px; color: rgba(211,230,243,.88); font-size: .74rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
    .admin-insights-label::before { content: ""; width: 22px; height: 2px; border-radius: 999px; background: #31d9f4; }
    .admin-chart-card .card-body { min-height: 175px; position: relative; }
    .admin-chart-card canvas { width: 100% !important; height: 135px !important; }
    .admin-chart-card .card-body::after { content: "Live data"; position: absolute; right: 12px; bottom: 8px; color: rgba(143,177,199,.55); font-size: .58rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
    .admin-section-description { margin: -6px 0 12px; color: rgba(157,188,208,.7); font-size: .68rem; }
    @media (max-width:1200px) { .admin-stat-grid { grid-template-columns: repeat(3,minmax(0,1fr)); } }
    @media (max-width:900px) { .admin-utility-grid { grid-template-columns: 1fr; } }
    @media (max-width:767.98px) { .admin-dashboard-shell { margin: -20px -15px; padding: 24px 15px 32px; } .admin-dashboard-header { align-items: flex-start; flex-direction: column; } .admin-stat-grid { grid-template-columns: repeat(2,minmax(0,1fr)); } .admin-dashboard-ribbon { grid-template-columns: 1fr; } .admin-quick-actions { grid-template-columns: repeat(2,minmax(0,1fr)); } }
    @media (max-width:480px) { .admin-stat-grid { grid-template-columns: 1fr; } }
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
