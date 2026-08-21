@extends('layouts.app')

@section('title', 'Application Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 mb-1">Application Details</h1><p class="text-muted mb-0">{{ $application->internship->title }}</p></div><a href="{{ route('student.applications.index') }}" class="btn btn-outline-secondary">Back to applications</a></div>
<div class="card"><div class="card-body">
    <div class="row g-3"><div class="col-md-6"><strong>Company</strong><p>{{ $application->internship->employer->company_name }}</p></div><div class="col-md-6"><strong>Location</strong><p>{{ $application->internship->location ?? '—' }}</p></div><div class="col-md-6"><strong>Status</strong><p><span class="badge {{ $application->status->badgeClass() }}">{{ $application->status->label() }}</span></p></div><div class="col-md-6"><strong>Applied</strong><p>{{ $application->applied_at?->format('M d, Y h:i A') }}</p></div></div>
    <hr><h5>Cover letter</h5><p class="text-muted">{{ $application->cover_letter ?: 'No cover letter provided.' }}</p>
    @if($application->status === \App\Enums\ApplicationStatus::Submitted)<a href="{{ route('student.applications.edit', $application) }}" class="btn btn-primary">Edit application</a>@endif
</div></div>
@endsection
