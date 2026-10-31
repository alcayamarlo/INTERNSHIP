@extends('layouts.app')

@section('title', 'Application Details')

@section('content')
<x-breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('student.dashboard')], ['label' => 'Internships', 'url' => route('student.internships.index')], ['label' => 'My Applications', 'url' => route('student.applications.index')], ['label' => $application->internship->title]]" />
<x-page-header title="Application Details" :subtitle="$application->internship->title">
    @php($companyUser = $application->internship->employer?->user)
    @if($companyUser)
        @can('message.send', $companyUser)
            <a href="{{ route('messages.show', $companyUser) }}" class="btn btn-primary"><i class="bi bi-chat-dots" aria-hidden="true"></i> Message company</a>
        @endcan
    @endif
    <a href="{{ route('student.applications.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to applications</a>
</x-page-header>
<div class="card"><div class="card-body">
    <div class="row g-3"><div class="col-md-6"><strong>Company</strong><p>{{ $application->internship->employer->company_name }}</p></div><div class="col-md-6"><strong>Location</strong><p>{{ $application->internship->location ?? '—' }}</p></div><div class="col-md-6"><strong>Status</strong><p><x-status-badge :status="$application->status->value" :label="$application->status->label()" /></p></div><div class="col-md-6"><strong>Applied</strong><p>{{ $application->applied_at?->format('M d, Y h:i A') }}</p></div></div>
    <hr><h2 class="h6">Cover letter</h2><p class="text-muted">{{ $application->cover_letter ?: 'No cover letter provided.' }}</p>
    @if(in_array($application->status, [\App\Enums\ApplicationStatus::Applied, \App\Enums\ApplicationStatus::Submitted], true))<a href="{{ route('student.applications.edit', $application) }}" class="btn btn-primary">Edit application</a>@endif
</div></div>

<div class="card mt-4">
    <div class="card-header"><h2>Application Timeline</h2></div>
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
