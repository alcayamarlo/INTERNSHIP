@extends('layouts.app')

@section('title', 'My Applications')

@push('styles')
<style>
    .student-page-shell {
        padding: 6px 0 0;
        color: var(--sb-ink);
    }

    .student-page-title {
        margin: 0 0 8px;
        color: var(--sb-ink);
        font-size: clamp(1.8rem, 1.2vw + 1.1rem, 2.5rem);
        font-weight: 800;
        letter-spacing: -0.05em;
    }

    .student-page-subtitle {
        margin: 0;
        color: var(--sb-muted);
        font-size: 0.95rem;
    }

    .student-page-shell .card {
        color: var(--sb-ink);
        background: var(--sb-surface);
        border-color: var(--sb-border);
        border-radius: 14px;
    }

    .student-page-shell .card-header {
        background: var(--sb-surface-soft);
        border-color: var(--sb-border);
    }

    .student-page-shell .form-control,
    .student-page-shell .form-select {
        color: var(--sb-ink);
        background-color: var(--sb-surface);
        border-color: var(--sb-border);
    }

    .student-page-shell .btn-outline-primary {
        color: var(--sb-brand-content);
        border-color: var(--sb-brand);
    }

    .student-page-shell .table thead th {
        background: var(--sb-surface-soft);
        color: var(--sb-ink);
        border-color: var(--sb-border);
        font-size: 0.72rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        font-weight: 800;
        padding: 0.8rem 0.9rem;
    }

    .student-page-shell .table td {
        color: var(--sb-ink);
        border-color: var(--sb-border);
        padding: 0.8rem 0.9rem;
        font-size: 0.92rem;
    }

    .student-page-shell .table tbody tr {
        transition: background-color 120ms ease;
    }

    .student-page-shell .table tbody tr:hover {
        background: var(--sb-hover-neutral);
    }

    .student-page-shell .table a {
        color: var(--sb-brand-content);
    }

    .student-page-shell .card-footer {
        background: var(--sb-surface);
        border-color: var(--sb-border);
    }
</style>
@endpush

@section('content')
<div class="student-page-shell">
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="student-page-title">My Applications</h1>
        <p class="student-page-subtitle">Track your internship application status</p>
    </div>
    <a href="{{ route('student.internships.index') }}" class="btn btn-primary"><i class="bi bi-search"></i> Browse Internships</a>
</div>

@if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
@endif

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-2"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">All</option>@foreach(\App\Enums\ApplicationStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Job title</label><input name="job_title" class="form-control" value="{{ request('job_title') }}"></div>
            <div class="col-md-2"><label class="form-label">Company</label><input name="company" class="form-control" value="{{ request('company') }}"></div>
            <div class="col-md-2"><label class="form-label">Location</label><input name="location" class="form-control" value="{{ request('location') }}"></div>
            <div class="col-md-2"><label class="form-label">Applied from</label><input type="date" name="applied_from" class="form-control" value="{{ request('applied_from') }}"></div>
            <div class="col-md-2"><label class="form-label">Applied to</label><input type="date" name="applied_to" class="form-control" value="{{ request('applied_to') }}"></div>
            <div class="col-12 d-flex gap-2"><button class="btn btn-outline-primary"><i class="bi bi-funnel"></i> Apply filters</button><a href="{{ route('student.applications.index') }}" class="btn btn-outline-secondary">Clear</a></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Internship</th><th>Company</th><th>Location</th><th>Match</th><th>Status</th><th>Applied</th><th>Interview</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($applications as $app)
                    <tr>
                        <td>
                            <a href="{{ route('student.internships.show', $app->internship) }}" class="text-decoration-none">{{ $app->internship->title }}</a>
                        </td>
                        <td>{{ $app->internship->employer->company_name }}</td>
                        <td>{{ $app->internship->location ?? '—' }}</td>
                        <td><span class="badge bg-primary">{{ $app->match_percentage }}%</span></td>
                        <td><span class="badge {{ $app->status->badgeClass() }}">{{ $app->status->label() }}</span></td>
                        <td>{{ $app->applied_at?->format('M d, Y') }}</td>
                        <td>{{ $app->interview_at?->format('M d, Y h:i A') ?? '—' }}</td>
                        <td class="text-nowrap">
                            <a href="{{ route('student.applications.show', $app) }}" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                            @if(in_array($app->status, [\App\Enums\ApplicationStatus::Applied, \App\Enums\ApplicationStatus::Submitted], true))<a href="{{ route('student.applications.edit', $app) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a><form method="POST" action="{{ route('student.applications.destroy', $app) }}" class="d-inline" onsubmit="return confirm('Delete this application?');">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button></form>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No applications match your filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($applications->hasPages())<div class="card-footer">{{ $applications->links() }}</div>@endif
</div>
</div>
@endsection
