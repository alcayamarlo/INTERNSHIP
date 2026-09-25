@extends('layouts.app')

@section('title', $student->user->name)

@push('styles')
<style>
    .student-detail-shell {
        padding-top: 8px;
        color: #edf8ff;
    }

    .student-detail-back {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1.3rem;
        color: #77d3ff;
        font-size: 1rem;
        font-weight: 700;
        text-decoration: none;
    }

    .student-detail-layout {
        display: grid;
        grid-template-columns: minmax(300px, 1.02fr) minmax(0, 1.55fr);
        gap: 1.5rem;
        align-items: start;
    }

    .student-detail-profile,
    .student-detail-panel,
    .student-detail-section {
        border: 1px solid rgba(140, 167, 192, 0.18);
        border-radius: 18px;
        background: rgba(14, 31, 45, 0.84);
        box-shadow: 0 10px 28px rgba(2, 9, 20, 0.18);
    }

    .student-detail-profile {
        padding: 1.5rem 1.3rem 1.2rem;
    }

    .student-profile-avatar {
        width: 110px;
        height: 110px;
        margin: 0 auto 1.2rem;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.95);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #0d1e2d;
        font-size: 2.7rem;
        overflow: hidden;
    }

    .student-profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .student-profile-name {
        margin: 0;
        color: #f4f9ff;
        font-size: clamp(2.1rem, 1.8vw + 1rem, 3.4rem);
        line-height: 1.08;
        font-weight: 900;
        letter-spacing: -0.06em;
        text-align: center;
    }

    .student-profile-email {
        margin: 0.5rem 0 0;
        color: rgba(196, 214, 228, 0.8);
        text-align: center;
        font-size: 1rem;
    }

    .student-profile-institution {
        margin: 0.5rem 0 0;
        color: rgba(196, 214, 228, 0.75);
        text-align: center;
        font-size: 0.95rem;
    }

    .student-profile-progress-wrap {
        margin-top: 1.4rem;
    }

    .student-profile-progress {
        height: 7px;
        background: rgba(148, 163, 184, 0.12);
        border-radius: 999px;
        overflow: hidden;
    }

    .student-profile-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #57d59d, #71e7b7);
        border-radius: 999px;
    }

    .student-profile-progress-label {
        display: block;
        margin-top: 0.7rem;
        color: rgba(196, 214, 228, 0.82);
        font-size: 0.97rem;
        font-weight: 500;
        text-align: center;
    }

    .student-detail-panel {
        overflow: hidden;
    }

    .student-section-header {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 1rem 1.2rem;
        border-bottom: 1px solid rgba(140, 167, 192, 0.12);
        color: #edf8ff;
        background: rgba(12, 30, 43, 0.8);
        font-size: 1.2rem;
        font-weight: 800;
    }

    .student-section-body {
        padding: 1.1rem 1.2rem 1rem;
    }

    .student-info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1.2rem 1.3rem;
    }

    .student-info-item {
        min-width: 0;
    }

    .student-info-label {
        display: block;
        margin-bottom: 0.28rem;
        color: rgba(196, 214, 228, 0.8);
        font-size: 0.95rem;
        font-weight: 700;
    }

    .student-info-value {
        display: block;
        color: #f0f8ff;
        font-size: 1.08rem;
        font-weight: 600;
        line-height: 1.4;
    }

    .student-objectives {
        margin-top: 1rem;
        color: #eff8ff;
        font-size: 1.08rem;
        line-height: 1.5;
    }

    .student-detail-section {
        margin-top: 1.4rem;
        overflow: hidden;
    }

    .student-badge-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.7rem;
        padding: 1rem 1.2rem;
    }

    .student-competency-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 36px;
        padding: 0.55rem 0.9rem;
        border-radius: 8px;
        background: rgba(96, 165, 228, 0.15);
        border: 1px solid rgba(96, 165, 228, 0.22);
        color: #dfeaf6;
        font-size: 0.92rem;
        font-weight: 700;
    }

    .student-competency-badge strong {
        color: #f0f9ff;
        font-weight: 800;
    }

    .student-table-wrap {
        overflow: hidden;
    }

    .student-detail-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .student-detail-table thead th {
        padding: 0.9rem 1rem;
        background: rgba(9, 22, 35, 0.9);
        color: rgba(180, 207, 225, 0.78);
        border-bottom: 1px solid rgba(140, 167, 192, 0.12);
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .student-detail-table tbody td {
        padding: 0.95rem 1rem;
        color: rgba(216, 232, 243, 0.92);
        border-bottom: 1px solid rgba(140, 167, 192, 0.08);
        font-size: 0.98rem;
        vertical-align: middle;
    }

    .student-detail-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .student-detail-table .badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 74px;
        padding: 0.4rem 0.65rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .student-detail-table .bg-success {
        background: rgba(32, 201, 151, 0.18) !important;
        color: #8fe6bf !important;
    }

    .student-detail-table .bg-primary {
        background: rgba(73, 175, 232, 0.18) !important;
        color: #afd8f5 !important;
    }

    .student-detail-table .bg-warning {
        background: rgba(244, 202, 90, 0.18) !important;
        color: #f7d98d !important;
    }

    @media (max-width: 991.98px) {
        .student-detail-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .student-info-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="student-detail-shell">
    <a href="{{ route('coordinator.students.index') }}" class="student-detail-back">
        <i class="bi bi-arrow-left"></i> Back to Students
    </a>

    <div class="student-detail-layout">
        <div class="student-detail-profile">
            <div class="student-profile-avatar">
                @if($student->profile_picture)
                    <img src="{{ Storage::url($student->profile_picture) }}" alt="{{ $student->user->name }}">
                @else
                    <i class="bi bi-person"></i>
                @endif
            </div>

            <h2 class="student-profile-name">{{ $student->user->name }}</h2>
            <p class="student-profile-email">{{ $student->user->email }}</p>
            <p class="student-profile-institution">{{ $student->institution?->name ?? 'No institution' }}</p>

            <div class="student-profile-progress-wrap">
                <div class="student-profile-progress">
                    <div class="student-profile-progress-bar" style="width: {{ $student->profile_completion ?? 0 }}%"></div>
                </div>
                <span class="student-profile-progress-label">Profile {{ $student->profile_completion ?? 0 }}% complete</span>
            </div>
        </div>

        <div class="student-detail-panel">
            <div class="student-section-header">
                <span>Profile Details</span>
            </div>

            <div class="student-section-body">
                <div class="student-info-grid">
                    <div class="student-info-item">
                        <span class="student-info-label">Program:</span>
                        <span class="student-info-value">{{ $student->program ?? '—' }}</span>
                    </div>
                    <div class="student-info-item">
                        <span class="student-info-label">Year Level:</span>
                        <span class="student-info-value">{{ $student->year_level ?? '—' }}</span>
                    </div>
                    <div class="student-info-item">
                        <span class="student-info-label">Student ID:</span>
                        <span class="student-info-value">{{ $student->student_id_number ?? '—' }}</span>
                    </div>
                    <div class="student-info-item">
                        <span class="student-info-label">Phone:</span>
                        <span class="student-info-value">{{ $student->user->phone ?? '—' }}</span>
                    </div>
                </div>

                @if($student->career_objectives)
                    <div class="student-objectives">
                        <div class="student-info-label" style="margin-bottom: 0.35rem;">Career Objectives:</div>
                        {{ $student->career_objectives }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="student-detail-section" style="grid-column: 2 / 3;">
        <div class="student-section-header">Competencies ({{ $student->competencies->count() }})</div>

        <div class="student-badge-list">
            @forelse($student->competencies as $comp)
                <span class="student-competency-badge">
                    <strong>{{ $comp->name }}</strong>
                    &nbsp;— {{ $comp->proficiency_level->label() }}
                </span>
            @empty
                <span class="student-info-value" style="padding: 0.5rem 0; color: rgba(196,214,228,0.8);">No competencies recorded.</span>
            @endforelse
        </div>
    </div>

    <div class="student-detail-section" style="grid-column: 2 / 3;">
        <div class="student-section-header">Applications</div>

        <div class="student-table-wrap">
            <table class="student-detail-table">
                <thead>
                    <tr>
                        <th>Internship</th>
                        <th>Company</th>
                        <th>Match</th>
                        <th>Status</th>
                        <th>Applied</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($student->applications as $app)
                        <tr>
                            <td>{{ $app->internship->title }}</td>
                            <td>{{ $app->internship->employer->company_name }}</td>
                            <td>{{ $app->match_percentage }}%</td>
                            <td><span class="badge {{ $app->status->badgeClass() }}">{{ $app->status->label() }}</span></td>
                            <td>{{ $app->applied_at?->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">No applications.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
