@extends('layouts.app')

@section('title', 'Applicants')

@push('styles')
<style>
    .employer-page-shell {
        padding: 8px 0 0;
        color: var(--sb-text);
    }

    .employer-page-header {
        margin-bottom: 1.8rem;
    }

    .employer-page-title {
        margin: 0 0 8px;
        color: var(--sb-text);
        font-size: clamp(2rem, 2.6vw, 3rem);
        line-height: 1.1;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .employer-page-subtitle {
        margin: 0;
        color: var(--sb-muted);
        font-size: 1rem;
        font-weight: 500;
    }

    .employer-page-shell .card {
        overflow: hidden;
        border: 1px solid var(--sb-border);
        border-radius: 16px;
        background: var(--sb-surface);
        box-shadow: 0 6px 20px rgba(120, 20, 30, 0.06);
    }

    .employer-page-shell .table-responsive {
        border-radius: 16px;
        overflow: hidden;
    }

    .employer-page-shell .table {
        --bs-table-bg: transparent;
        --bs-table-color: var(--sb-text);
        --bs-table-border-color: var(--sb-border);
        margin: 0;
        table-layout: fixed;
    }

    .employer-page-shell .table thead th {
        background: var(--sb-surface-raised) !important;
        color: var(--sb-muted) !important;
        border-color: var(--sb-border) !important;
        padding: 0.9rem 1rem;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .employer-page-shell .table td {
        border-color: var(--sb-border) !important;
        padding: 1.1rem 1rem;
        color: var(--sb-text) !important;
        vertical-align: middle;
        font-size: 0.95rem;
    }

    .employer-page-shell .table tbody tr:hover {
        background: var(--sb-brand-soft);
    }

    .employer-page-shell .student-name {
        color: var(--sb-text);
        font-weight: 700;
        line-height: 1.3;
    }

    .employer-page-shell .student-program {
        display: block;
        margin-top: 0.25rem;
        color: var(--sb-muted);
        font-size: 0.82rem;
        line-height: 1.4;
    }

    .employer-page-shell .table-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 70px;
        padding: 0.4rem 0.8rem;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .employer-page-shell .table-badge.match-blue {
        background: rgba(37, 99, 235, 0.10);
        color: #1d4ed8;
        border: 1px solid rgba(37, 99, 235, 0.28);
    }

    .employer-page-shell .table-badge.match-green,
    .employer-page-shell .table-badge.status-reviewed {
        background: rgba(22, 163, 74, 0.10);
        color: #15803d;
        border: 1px solid rgba(22, 163, 74, 0.32);
    }

    .employer-page-shell .table-badge.status-submitted {
        background: var(--sb-surface-raised);
        color: var(--sb-muted);
        border: 1px solid var(--sb-border);
    }

    .employer-page-shell .review-btn {
        min-height: 36px;
        padding: 0.5rem 1.1rem;
        border: 1px solid var(--sb-brand);
        border-radius: 10px;
        background: var(--sb-brand) !important;
        color: #fff !important;
        font-size: 0.86rem;
        font-weight: 700;
    }

    .employer-page-shell .review-btn:hover {
        border-color: var(--sb-brand-deep);
        background: var(--sb-brand-deep) !important;
    }

    .employer-page-shell .card-footer {
        border-top: 1px solid var(--sb-border);
        background: var(--sb-surface-raised);
        padding: 0.9rem 1rem;
    }

    @media (max-width: 767.98px) {
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
