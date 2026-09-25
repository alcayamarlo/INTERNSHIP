@extends('layouts.app')

@section('title', 'Applicants')

@push('styles')
<style>
    .employer-page-shell {
        padding: 8px 0 0;
        color: #edf8ff;
    }

    .employer-page-header {
        margin-bottom: 1.8rem;
    }

    .employer-page-title {
        margin: 0 0 8px;
        color: #f4f9ff;
        font-size: clamp(2.8rem, 3.2vw, 5rem);
        line-height: 1.02;
        font-weight: 900;
        letter-spacing: -0.055em;
    }

    .employer-page-subtitle {
        margin: 0;
        color: rgba(186, 211, 228, 0.82);
        font-size: 1.05rem;
        font-weight: 500;
    }

    .employer-page-shell .card {
        overflow: hidden;
        border: 1px solid rgba(140, 167, 192, 0.18);
        border-radius: 18px;
        background: rgba(14, 31, 45, 0.84);
        box-shadow: 0 10px 28px rgba(2, 9, 20, 0.18);
    }

    .employer-page-shell .table-responsive {
        border-radius: 18px;
        overflow: hidden;
    }

    .employer-page-shell .table {
        --bs-table-bg: transparent;
        --bs-table-color: rgba(216, 232, 243, 0.96);
        --bs-table-border-color: rgba(140, 167, 192, 0.08);
        margin: 0;
        table-layout: fixed;
    }

    .employer-page-shell .table thead th {
        background: rgba(9, 22, 35, 0.9) !important;
        color: rgba(180, 207, 225, 0.78) !important;
        border-color: rgba(140, 167, 192, 0.12) !important;
        padding: 1rem 1rem;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .employer-page-shell .table td {
        border-color: rgba(140, 167, 192, 0.08) !important;
        padding: 1.2rem 1rem;
        color: rgba(216, 232, 243, 0.92) !important;
        vertical-align: middle;
        font-size: 0.98rem;
    }

    .employer-page-shell .table tbody tr:hover {
        background: rgba(148, 163, 184, 0.04);
    }

    .employer-page-shell .student-name {
        color: #f0f8ff;
        font-weight: 700;
        line-height: 1.3;
    }

    .employer-page-shell .student-program {
        display: block;
        margin-top: 0.25rem;
        color: rgba(180, 208, 228, 0.8);
        font-size: 0.82rem;
        line-height: 1.4;
    }

    .employer-page-shell .table-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 70px;
        padding: 0.5rem 0.8rem;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .employer-page-shell .table-badge.match-blue {
        background: rgba(73, 175, 232, 0.12);
        color: #a7dbff;
        border: 1px solid rgba(110, 179, 230, 0.34);
    }

    .employer-page-shell .table-badge.match-green {
        background: rgba(32, 201, 151, 0.12);
        color: #97efc9;
        border: 1px solid rgba(32, 201, 151, 0.4);
    }

    .employer-page-shell .table-badge.status-submitted {
        background: rgba(148, 163, 184, 0.12);
        color: #e5eef7;
        border: 1px solid rgba(148, 163, 184, 0.24);
    }

    .employer-page-shell .table-badge.status-reviewed {
        background: rgba(32, 201, 151, 0.12);
        color: #97efc9;
        border: 1px solid rgba(32, 201, 151, 0.4);
    }

    .employer-page-shell .review-btn {
        min-height: 36px;
        padding: 0.55rem 1rem;
        border: 0;
        border-radius: 10px;
        background: linear-gradient(135deg, #5ec9f5, #3c9bdf) !important;
        color: #ffffff !important;
        font-size: 0.86rem;
        font-weight: 700;
        box-shadow: 0 8px 18px rgba(44, 164, 222, 0.22);
    }

    .employer-page-shell .card-footer {
        border-top: 1px solid rgba(140, 167, 192, 0.12);
        background: rgba(10, 24, 34, 0.8);
        padding: 0.9rem 1rem;
    }

    @media (max-width: 767.98px) {
        .employer-page-title {
            font-size: 2.5rem;
        }

        .employer-page-shell .table {
            min-width: 760px;
        }
    }
</style>
@endpush

@section('content')
<div class="employer-page-shell">
    <div class="employer-page-header">
        <h1 class="employer-page-title">Applicants</h1>
        <p class="employer-page-subtitle">Review student applications</p>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Internship</th>
                        <th>Match</th>
                        <th>Status</th>
                        <th>Applied</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applications as $app)
                        <tr>
                            <td>
                                <span class="student-name">{{ $app->student->user->name }}</span>
                                <span class="student-program">{{ $app->student->program ?? '—' }}</span>
                            </td>
                            <td>{{ $app->internship->title }}</td>
                            <td>
                                @php
                                    $matchClass = $app->match_percentage >= 70 ? 'match-green' : 'match-blue';
                                @endphp
                                <span class="table-badge {{ $matchClass }}">{{ $app->match_percentage }}%</span>
                            </td>
                            <td>
                                @php
                                    $statusText = strtolower($app->status->label());
                                    $statusClass = $statusText === 'reviewed' ? 'status-reviewed' : 'status-submitted';
                                @endphp
                                <span class="table-badge {{ $statusClass }}">{{ $app->status->label() }}</span>
                            </td>
                            <td>{{ $app->applied_at?->format('M d, Y') }}</td>
                            <td>
                                <a href="{{ route('employer.applicants.show', $app) }}" class="btn review-btn">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No applicants yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($applications->hasPages())
            <div class="card-footer">{{ $applications->links() }}</div>
        @endif
    </div>
</div>
@endsection
