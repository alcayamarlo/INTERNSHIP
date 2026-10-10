@extends('layouts.app')

@section('title', 'Manage Internships')

@push('styles')
<style>
    .employer-page-shell {
        padding: 8px 0 0;
        color: #edf8ff;
    }

    .employer-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 2rem;
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

    .employer-page-shell .btn-primary {
        min-height: 44px;
        padding: 0.7rem 1.2rem;
        border: 0;
        border-radius: 10px;
        background: linear-gradient(135deg, #5ec9f5, #3c9bdf) !important;
        color: #ffffff !important;
        font-size: 0.92rem;
        font-weight: 700;
        box-shadow: 0 8px 18px rgba(44, 164, 222, 0.22);
    }

    .employer-page-shell .btn-primary i {
        margin-right: 0.5rem;
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
        font-size: 1rem;
    }

    .employer-page-shell .table tbody tr:hover {
        background: rgba(148, 163, 184, 0.04);
    }

    .employer-page-shell .table tbody td strong {
        color: #f0f8ff;
        font-weight: 700;
    }

    .employer-page-shell .badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 70px;
        padding: 0.45rem 0.8rem;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .employer-page-shell .badge.bg-success {
        background: rgba(32, 201, 151, 0.15) !important;
        color: #8fe6bf !important;
        border: 1px solid rgba(32, 201, 151, 0.45);
    }

    .employer-page-shell .badge.bg-secondary {
        background: rgba(148, 163, 184, 0.12) !important;
        color: #d8e8f2 !important;
        border: 1px solid rgba(148, 163, 184, 0.22);
    }

    .employer-page-shell .table-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .employer-page-shell .action-btn {
        width: 36px;
        height: 36px;
        padding: 0;
        border-radius: 10px;
        border: 1px solid rgba(136, 158, 184, 0.22);
        background: rgba(25, 38, 49, 0.8);
        color: #dfeaf6;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.15s ease, border-color 0.15s ease;
    }

    .employer-page-shell .action-btn:hover {
        transform: translateY(-1px);
        border-color: rgba(147, 186, 223, 0.38);
    }

    .employer-page-shell .action-btn.edit {
        color: #7fd1f8;
    }

    .employer-page-shell .action-btn.close {
        color: #f6c56d;
    }

    .employer-page-shell .action-btn.delete {
        color: #ff9caa;
    }

    .employer-page-shell .action-btn form {
        display: inline-flex;
    }

    .employer-page-shell .card-footer {
        border-top: 1px solid rgba(140, 167, 192, 0.12);
        background: rgba(10, 24, 34, 0.8);
        padding: 0.9rem 1rem;
    }

    @media (max-width: 767.98px) {
        .employer-page-header {
            flex-direction: column;
            align-items: flex-start;
        }

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
        <div>
            <h1 class="employer-page-title">Internship Postings</h1>
            <p class="employer-page-subtitle">Manage your internship listings</p>
        </div>

        <a href="{{ route('employer.internships.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i>New Posting
        </a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Location</th>
                        <th>Setup</th>
                        <th>Status</th>
                        <th>Applicants</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($internships as $internship)
                        <tr>
                            <td><strong>{{ $internship->title }}</strong></td>
                            <td>{{ $internship->location ?? '—' }}</td>
                            <td>{{ $internship->work_setup->label() }}</td>
                            <td>
                                <span class="badge bg-{{ $internship->status === 'open' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($internship->status) }}
                                </span>
                            </td>
                            <td>{{ $internship->applications_count }}</td>
                            <td>
                                <div class="table-actions">
                                    <a href="{{ route('employer.internships.edit', $internship) }}" class="action-btn edit" title="Edit posting">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    @if($internship->status === 'open')
                                        <form action="{{ route('employer.internships.close', $internship) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="action-btn close" title="Close posting">
                                                <i class="bi bi-lock"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <form action="{{ route('employer.internships.destroy', $internship) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this internship?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-btn delete" title="Delete posting">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No internship postings yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($internships->hasPages())
            <div class="card-footer">{{ $internships->links() }}</div>
        @endif
    </div>
</div>
@endsection
