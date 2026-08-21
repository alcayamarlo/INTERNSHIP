@extends('layouts.app')

@section('title', 'My Applications')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">My Applications</h1>
        <p class="text-muted mb-0">Track your internship application status</p>
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
@endsection
