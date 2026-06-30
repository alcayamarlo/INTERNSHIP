@extends('layouts.app')

@section('title', 'Applicants')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Applicants</h1>
        <p class="text-muted mb-0">Review student applications</p>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Student</th><th>Internship</th><th>Match</th><th>Status</th><th>Applied</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($applications as $app)
                    <tr>
                        <td>
                            <strong>{{ $app->student->user->name }}</strong><br>
                            <small class="text-muted">{{ $app->student->program ?? '—' }}</small>
                        </td>
                        <td>{{ $app->internship->title }}</td>
                        <td>
                            <span class="badge {{ $app->match_percentage >= 70 ? 'bg-success' : 'bg-primary' }}">{{ $app->match_percentage }}%</span>
                        </td>
                        <td><span class="badge {{ $app->status->badgeClass() }}">{{ $app->status->label() }}</span></td>
                        <td>{{ $app->applied_at?->format('M d, Y') }}</td>
                        <td>
                            <a href="{{ route('employer.applicants.show', $app) }}" class="btn btn-sm btn-primary">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No applicants yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($applications->hasPages())<div class="card-footer">{{ $applications->links() }}</div>@endif
</div>
@endsection
