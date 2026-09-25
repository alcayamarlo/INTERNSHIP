@extends('layouts.app')

@section('title', 'My Applications')

@push('styles')
<style>
    .student-page-shell {
        padding: 6px 0 0;
        color: #edf8ff;
    }

    .student-page-title {
        margin: 0 0 8px;
        color: #f4f9ff;
        font-size: clamp(1.8rem, 1.2vw + 1.1rem, 2.5rem);
        font-weight: 800;
        letter-spacing: -0.05em;
    }

    .student-page-subtitle {
        margin: 0;
        color: rgba(186,209,228,.8);
        font-size: 0.95rem;
    }

    .student-page-shell .card,
    .student-page-shell .form-control,
    .student-page-shell .form-select,
    .student-page-shell .btn {
        border-color: rgba(117,176,215,.18) !important;
        background: rgba(16,31,45,.86) !important;
        color: #edf8ff !important;
    }

    .student-page-shell .card {
        border-radius: 18px;
        border: 1px solid rgba(148,163,184,.14);
        box-shadow: 0 10px 22px rgba(2,9,20,.12);
        overflow: hidden;
    }

    .student-page-shell .card:hover {
        border-color: rgba(49,217,244,.36) !important;
    }

    .student-page-shell .card-header {
        background: rgba(13,30,44,.92) !important;
        border-bottom: 1px solid rgba(117,176,215,.18);
        padding: 1rem 1.1rem;
    }

    .student-page-shell .btn-primary {
        border: 0;
        background: linear-gradient(135deg, #6ab6d7, #4f9ed1) !important;
        color: #eef9ff !important;
        font-weight: 700;
        box-shadow: 0 8px 20px rgba(37,194,255,.15);
    }

    .student-page-shell .btn-outline-primary {
        border-color: rgba(77,210,255,.5);
        color: #7fe0ff !important;
    }

    .student-page-shell .table thead th {
        background: rgba(12,28,40,.9);
        color: rgba(220,235,246,.9);
        border-color: rgba(117,176,215,.18);
        font-size: 0.72rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        font-weight: 800;
        padding: 0.8rem 0.9rem;
    }

    .student-page-shell .table td {
        color: rgba(216,232,243,.9);
        border-color: rgba(117,176,215,.08);
        padding: 0.8rem 0.9rem;
        font-size: 0.92rem;
    }

    .student-page-shell .table tbody tr {
        transition: background .18s ease;
    }

    .student-page-shell .table tbody tr:hover {
        background: rgba(31,191,255,.05);
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
                            @if($app->status === \App\Enums\ApplicationStatus::Submitted)<a href="{{ route('student.applications.edit', $app) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a><form method="POST" action="{{ route('student.applications.destroy', $app) }}" class="d-inline" onsubmit="return confirm('Delete this application?');">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button></form>@endif
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
