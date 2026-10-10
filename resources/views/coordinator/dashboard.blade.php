@extends('layouts.app')

@section('title', 'Coordinator Dashboard')

@push('styles')
<style>
    .coordinator-dashboard-shell {
        padding: 8px 0 0;
        color: #edf5fb;
    }

    .coordinator-dashboard-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        gap: 1rem;
    }

    .coordinator-dashboard-header h1 {
        margin: 0;
        color: #f4f9ff;
        font-size: clamp(2.2rem, 1.8vw + 1.2rem, 3.2rem);
        font-weight: 800;
        letter-spacing: -0.06em;
        line-height: 1.1;
    }

    .coordinator-dashboard-header .subtitle {
        margin: 0.5rem 0 0;
        color: rgba(196, 214, 228, 0.74);
        font-size: 0.98rem;
        line-height: 1.5;
        max-width: 700px;
    }

    .coordinator-stat-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 1.4rem;
    }

    .coordinator-stat-card {
        display: flex;
        align-items: center;
        gap: 0.9rem;
        min-height: 110px;
        padding: 1rem 1.1rem;
        border: 1px solid rgba(148, 163, 184, 0.14);
        border-radius: 18px;
        background: rgba(15, 28, 40, 0.9);
        box-shadow: 0 10px 20px rgba(3, 8, 18, 0.12);
        transition: transform 0.18s ease, border-color 0.18s ease;
    }

    .coordinator-stat-card:hover {
        transform: translateY(-1px);
        border-color: rgba(148, 163, 184, 0.2);
    }

    .coordinator-stat-icon {
        width: 46px;
        height: 46px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        background: rgba(123, 163, 205, 0.12);
        border: 1px solid rgba(148, 163, 184, 0.16);
        color: #dfeefc;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .coordinator-stat-content {
        min-width: 0;
    }

    .coordinator-stat-label {
        display: block;
        margin-bottom: 0.2rem;
        color: rgba(196, 214, 228, 0.76);
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .coordinator-stat-value {
        margin: 0;
        color: #f3f9ff;
        font-size: clamp(1.5rem, 2vw, 2.2rem);
        font-weight: 800;
        line-height: 1.05;
        letter-spacing: -0.05em;
    }

    .coordinator-dashboard-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .coordinator-dashboard-card {
        overflow: hidden;
        border: 1px solid rgba(148, 163, 184, 0.15);
        border-radius: 18px;
        background: rgba(15, 30, 43, 0.88);
        box-shadow: 0 12px 24px rgba(3, 8, 18, 0.14);
    }

    .coordinator-dashboard-card .card-header {
        border-bottom: 1px solid rgba(148, 163, 184, 0.12);
        background: rgba(13, 24, 35, 0.9) !important;
        padding: 1rem 1.1rem;
    }

    .coordinator-dashboard-card .card-header h5 {
        margin: 0;
        color: #edf8ff;
        font-size: 1.08rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .coordinator-dashboard-card .card-header h5 i {
        margin-right: 0.5rem;
        color: #cedeef;
    }

    .coordinator-dashboard-card .card-body {
        padding: 1rem 1.1rem;
    }

    .coordinator-dashboard-list {
        display: flex;
        flex-direction: column;
        gap: 0.38rem;
    }

    .coordinator-dashboard-list a {
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 58px;
        border-radius: 12px;
        padding: 0.88rem 0.95rem;
        background: rgba(18, 36, 50, 0.6);
        color: #edf6ff;
        font-size: 1rem;
        font-weight: 600;
        border: 1px solid rgba(148, 163, 184, 0.08);
        transition: all 0.18s ease;
    }

    .coordinator-dashboard-list a:hover {
        background: rgba(26, 44, 59, 0.88);
        border-color: rgba(148, 163, 184, 0.16);
        text-decoration: none;
    }

    .coordinator-dashboard-list a .left {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .coordinator-dashboard-list a .left i {
        width: 20px;
        color: #d6e8f9;
        font-size: 1.1rem;
    }

    .coordinator-dashboard-list a .arrow {
        color: rgba(220, 235, 246, 0.8);
        font-size: 1.1rem;
    }

    .coordinator-info-list {
        display: grid;
        grid-template-columns: 120px 1fr;
        gap: 0.8rem 0.7rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .coordinator-info-list li {
        color: #edf6ff;
        font-size: 0.98rem;
        line-height: 1.5;
        word-break: break-word;
    }

    .coordinator-info-list li:nth-child(odd) {
        color: rgba(214, 227, 240, 0.82);
        font-weight: 700;
    }

    .coordinator-bottom-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.08fr) minmax(0, 0.92fr);
        gap: 1rem;
        margin-top: 0.3rem;
    }

    .coordinator-table-wrap {
        overflow: hidden;
    }

    .coordinator-table {
        width: 100%;
        border-collapse: collapse;
        color: #ebf4ff;
    }

    .coordinator-table thead th {
        background: rgba(16, 32, 46, 0.8);
        color: rgba(214, 227, 240, 0.8);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding: 0.8rem 0.9rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.12);
        text-align: left;
    }

    .coordinator-table tbody td {
        padding: 0.8rem 0.9rem;
        border-top: 1px solid rgba(148, 163, 184, 0.12);
        font-size: 0.92rem;
        color: rgba(239, 247, 255, 0.95);
        vertical-align: middle;
    }

    .coordinator-table tbody tr:hover {
        background: rgba(148, 163, 184, 0.03);
    }

    .coordinator-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 72px;
        padding: 0.38rem 0.72rem;
        border-radius: 999px;
        font-size: 0.68rem;
        font-weight: 800;
        text-transform: capitalize;
        color: #eaf6ff;
    }

    .coordinator-view-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 62px;
        padding: 0.42rem 0.76rem;
        border-radius: 9px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        background: rgba(36, 52, 68, 0.88);
        color: #ebf4ff;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .coordinator-view-btn:hover {
        text-decoration: none;
        color: #fff;
        background: rgba(50, 70, 90, 0.95);
    }

    @media (max-width: 991.98px) {
        .coordinator-stat-grid,
        .coordinator-dashboard-grid,
        .coordinator-bottom-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="coordinator-dashboard-shell">
    <div class="coordinator-dashboard-header">
        <div>
            <h1>Coordinator Dashboard</h1>
            <p class="subtitle">Manage students and track placements at St. Cecilia’s College-Cebu, Inc.</p>
        </div>
    </div>

    <div class="coordinator-stat-grid">
        <div class="coordinator-stat-card">
            <div class="coordinator-stat-icon"><i class="bi bi-people-fill"></i></div>
            <div class="coordinator-stat-content">
                <span class="coordinator-stat-label">Active Students</span>
                <p class="coordinator-stat-value">{{ $stats['total_students'] }}</p>
            </div>
        </div>

        <div class="coordinator-stat-card">
            <div class="coordinator-stat-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
            <div class="coordinator-stat-content">
                <span class="coordinator-stat-label">Internship Applications</span>
                <p class="coordinator-stat-value">{{ $stats['total_applications'] }}</p>
            </div>
        </div>

        <div class="coordinator-stat-card">
            <div class="coordinator-stat-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div class="coordinator-stat-content">
                <span class="coordinator-stat-label">Placements</span>
                <p class="coordinator-stat-value">{{ $stats['accepted'] }}</p>
            </div>
        </div>

        <div class="coordinator-stat-card">
            <div class="coordinator-stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="coordinator-stat-content">
                <span class="coordinator-stat-label">Success Rate</span>
                <p class="coordinator-stat-value">{{ $stats['placement_rate'] }}%</p>
            </div>
        </div>
    </div>

    <div class="coordinator-dashboard-grid">
        <div class="coordinator-dashboard-card">
            <div class="card-header">
                <h5><i class="bi bi-lightning-charge-fill"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="coordinator-dashboard-list">
                    <a href="{{ route('coordinator.students.index') }}">
                        <span class="left"><i class="bi bi-people-fill"></i> Manage Students</span>
                        <span class="arrow"><i class="bi bi-chevron-right"></i></span>
                    </a>
                    <a href="{{ route('coordinator.reports.index') }}">
                        <span class="left"><i class="bi bi-file-earmark-bar-graph-fill"></i> Generate Reports</span>
                        <span class="arrow"><i class="bi bi-chevron-right"></i></span>
                    </a>
                    <a href="{{ route('messages.index') }}">
                        <span class="left"><i class="bi bi-chat-dots-fill"></i> Messages & Announcements</span>
                        <span class="arrow"><i class="bi bi-chevron-right"></i></span>
                    </a>
                    <a href="{{ route('notifications.index') }}">
                        <span class="left"><i class="bi bi-bell-fill"></i> Notifications</span>
                        <span class="arrow"><i class="bi bi-chevron-right"></i></span>
                    </a>
                </div>
            </div>
        </div>

        <div class="coordinator-dashboard-card">
            <div class="card-header">
                <h5><i class="bi bi-info-circle-fill"></i> Institution Info</h5>
            </div>
            <div class="card-body">
                <ul class="coordinator-info-list">
                    <li>Institution:</li>
                    <li>{{ auth()->user()->coordinator->institution->name ?? 'N/A' }}</li>
                    <li>Department:</li>
                    <li>{{ auth()->user()->coordinator->department ?? 'N/A' }}</li>
                    <li>Contact:</li>
                    <li>{{ auth()->user()->email }}</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="coordinator-bottom-grid">
        <div class="coordinator-dashboard-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-people-fill"></i> Recent Students</h5>
                <a href="{{ route('coordinator.students.index') }}" class="coordinator-view-btn">View All</a>
            </div>
            <div class="coordinator-table-wrap">
                <table class="coordinator-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Program</th>
                            <th>Competencies</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentStudents as $student)
                            <tr>
                                <td>{{ $student->user->name }}</td>
                                <td>{{ $student->program ?? 'N/A' }}</td>
                                <td><span class="coordinator-pill" style="background: rgba(110, 168, 255, 0.18);">{{ $student->competencies()->count() }}</span></td>
                                <td>
                                    <a href="{{ route('coordinator.students.show', $student) }}" class="coordinator-view-btn">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-3 text-muted">No students yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="coordinator-dashboard-card">
            <div class="card-header">
                <h5><i class="bi bi-send-fill"></i> Recent Applications</h5>
            </div>
            <div class="coordinator-table-wrap">
                <table class="coordinator-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Position</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentApplications as $application)
                            <tr>
                                <td>{{ $application->student->user->name }}</td>
                                <td>{{ $application->internship->title }}</td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'submitted' => '#3B82F6',
                                            'reviewed' => '#F59E0B',
                                            'interview' => '#8B5CF6',
                                            'accepted' => '#10B981',
                                            'rejected' => '#EF4444',
                                        ];
                                        $statusColor = $statusColors[$application->status->value] ?? '#6B7280';
                                    @endphp
                                    <span class="coordinator-pill" style="background: {{ $statusColor }}; color: white;">{{ $application->status->label() }}</span>
                                </td>
                                <td>{{ $application->applied_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-3 text-muted">No applications yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
