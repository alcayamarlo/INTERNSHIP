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

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Internship</th><th>Company</th><th>Match</th><th>Status</th><th>Applied</th><th>Interview</th></tr>
            </thead>
            <tbody>
                @forelse($applications as $app)
                    <tr>
                        <td>
                            <a href="{{ route('student.internships.show', $app->internship) }}" class="text-decoration-none">{{ $app->internship->title }}</a>
                        </td>
                        <td>{{ $app->internship->employer->company_name }}</td>
                        <td><span class="badge bg-primary">{{ $app->match_percentage }}%</span></td>
                        <td><span class="badge {{ $app->status->badgeClass() }}">{{ $app->status->label() }}</span></td>
                        <td>{{ $app->applied_at?->format('M d, Y') }}</td>
                        <td>{{ $app->interview_at?->format('M d, Y h:i A') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">You haven't applied to any internships yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($applications->hasPages())<div class="card-footer">{{ $applications->links() }}</div>@endif
</div>
@endsection
