@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-1">System Dashboard</h1>
    <p class="text-muted mb-0">Platform analytics and administration</p>
</div>

<div class="row g-4 mb-4">
    @foreach(['users' => 'Users', 'students' => 'Students', 'employers' => 'Employers', 'internships' => 'Internships', 'applications' => 'Applications', 'placements' => 'Placements'] as $key => $label)
        <div class="col-md-4 col-lg-2">
            <div class="card stat-card {{ $loop->even ? 'accent' : '' }} h-100">
                <div class="card-body text-center">
                    <p class="text-muted small mb-1">{{ $label }}</p>
                    <h3 class="mb-0">{{ $stats[$key] }}</h3>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white"><h5 class="mb-0">Applications per Month</h5></div>
            <div class="card-body"><canvas id="applicationsChart" height="200"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white"><h5 class="mb-0">Application Status</h5></div>
            <div class="card-body"><canvas id="statusChart" height="200"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white"><h5 class="mb-0">Competency Levels</h5></div>
            <div class="card-body"><canvas id="competencyChart" height="200"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white"><h5 class="mb-0">Top Required Skills</h5></div>
            <div class="card-body"><canvas id="skillsChart" height="200"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between">
                <h5 class="mb-0">Recent System Logs</h5>
                <a href="{{ route('admin.logs') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>User</th><th>Action</th><th>Time</th></tr></thead>
                    <tbody>
                        @forelse($recentLogs as $log)
                            <tr>
                                <td>{{ $log->user?->name ?? 'System' }}</td>
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
                <h5 class="mb-0">Recent Announcements</h5>
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
@endsection

@push('scripts')
<script>
    const analyticsUrl = "{{ route('analytics.charts') }}";
    fetch(analyticsUrl)
        .then(r => r.json())
        .then(data => {
            new Chart(document.getElementById('applicationsChart'), {
                type: 'line',
                data: {
                    labels: data.applications_per_month.map(i => i.month),
                    datasets: [{ label: 'Applications', data: data.applications_per_month.map(i => i.total), borderColor: '#2563EB', backgroundColor: 'rgba(37,99,235,.1)', fill: true }]
                },
                options: { responsive: true, plugins: { legend: { display: false } } }
            });
            new Chart(document.getElementById('statusChart'), {
                type: 'doughnut',
                data: {
                    labels: data.placements.map(i => i.status),
                    datasets: [{ data: data.placements.map(i => i.total), backgroundColor: ['#2563EB','#10B981','#f59e0b','#ef4444','#6366f1','#64748b'] }]
                },
                options: { responsive: true }
            });
            new Chart(document.getElementById('competencyChart'), {
                type: 'bar',
                data: {
                    labels: data.competency_levels.map(i => i.proficiency_level),
                    datasets: [{ label: 'Count', data: data.competency_levels.map(i => i.total), backgroundColor: '#10B981' }]
                },
                options: { responsive: true, plugins: { legend: { display: false } } }
            });
            new Chart(document.getElementById('skillsChart'), {
                type: 'bar',
                data: {
                    labels: data.top_skills.map(i => i.requirement_name),
                    datasets: [{ label: 'Demand', data: data.top_skills.map(i => i.total), backgroundColor: '#2563EB' }]
                },
                options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } } }
            });
        });
</script>
@endpush
