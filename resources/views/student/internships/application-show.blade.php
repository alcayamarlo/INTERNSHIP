@extends('layouts.app')

@section('title', 'Application Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 mb-1">Application Details</h1><p class="text-muted mb-0">{{ $application->internship->title }}</p></div><a href="{{ route('student.applications.index') }}" class="btn btn-outline-secondary">Back to applications</a></div>
<div class="card"><div class="card-body">
    <div class="row g-3"><div class="col-md-6"><strong>Company</strong><p>{{ $application->internship->employer->company_name }}</p></div><div class="col-md-6"><strong>Location</strong><p>{{ $application->internship->location ?? '—' }}</p></div><div class="col-md-6"><strong>Status</strong><p><span class="badge {{ $application->status->badgeClass() }}">{{ $application->status->label() }}</span></p></div><div class="col-md-6"><strong>Applied</strong><p>{{ $application->applied_at?->format('M d, Y h:i A') }}</p></div></div>
    <hr><h5>Cover letter</h5><p class="text-muted">{{ $application->cover_letter ?: 'No cover letter provided.' }}</p>
    @if($application->status === \App\Enums\ApplicationStatus::Submitted)<a href="{{ route('student.applications.edit', $application) }}" class="btn btn-primary">Edit application</a>@endif
</div></div>

<div class="card mt-4">
    <div class="card-header bg-white"><h5 class="mb-0">Application Timeline</h5></div>
    <div class="card-body">
        @forelse($application->statusHistory->sortByDesc('created_at') as $history)
            <div class="border-start border-3 ps-3 mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <strong>{{ \App\Enums\ApplicationStatus::from($history->status)->label() }}</strong>
                    <small class="text-muted">{{ $history->created_at?->format('M d, Y h:i A') }}</small>
                </div>
                <div class="small text-muted mt-1">Updated by {{ $history->actor?->name ?? 'System' }}</div>
                @if($history->interview_at)
                    <div class="small mt-1">Interview scheduled: {{ $history->interview_at->format('M d, Y h:i A') }}</div>
                @endif
                @if($history->notes)
                    <div class="mt-2 text-muted">{{ $history->notes }}</div>
                @endif
            </div>
        @empty
            <p class="text-muted mb-0">No status updates yet.</p>
        @endforelse
    </div>
</div>
@endsection
