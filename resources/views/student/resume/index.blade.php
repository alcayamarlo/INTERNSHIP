@extends('layouts.app')

@section('title', 'Resume')

@section('content')
<x-page-header title="Resume Builder" subtitle="Generate PDF resumes from your profile data">
    @if($view === 'active')
        <form action="{{ route('student.resume.generate') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Generate New Resume</button>
        </form>
    @endif
</x-page-header>

<div class="d-flex gap-2 mb-3" role="group" aria-label="Resume views">
    <a href="{{ route('student.resume.index', ['view' => 'active']) }}" class="btn btn-sm {{ $view === 'active' ? 'btn-primary' : 'btn-outline-primary' }}">Active</a>
    <a href="{{ route('student.resume.index', ['view' => 'archived']) }}" class="btn btn-sm {{ $view === 'archived' ? 'btn-primary' : 'btn-outline-primary' }}">Archived</a>
</div>

<div id="resumeActionFeedback" class="alert d-none" role="status" aria-live="polite"></div>

<div class="card">
    <div class="card-header"><h2>{{ $view === 'archived' ? 'Archived Resumes' : 'Generated Resumes' }}</h2></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Generated</th><th>Actions</th></tr></thead>
            <tbody data-resume-list data-empty-message="{{ $view === 'archived' ? 'No archived resumes.' : 'No resumes generated yet. Click “Generate New Resume” to create one.' }}">
                @forelse($resumes as $resume)
                    <tr data-resume-row>
                        <td>
                            {{ $resume->generated_at?->format('M d, Y h:i A') ?? $resume->created_at->format('M d, Y h:i A') }}
                            @if($resume->archived_at)<small class="text-muted d-block">Archived {{ $resume->archived_at->diffForHumans() }}</small>@endif
                        </td>
                        <td class="sb-row-actions">
                            @if(!$resume->archived_at)
                                <a href="{{ route('student.resume.view', $resume, false) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View PDF
                                </a>
                                <a href="{{ route('student.resume.download', $resume, false) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-download"></i> Download PDF
                                </a>
                                <form action="{{ route('student.resume.archive', $resume, false) }}" method="POST"
                                    data-ajax-resume="archive" data-active-applications="{{ $resume->active_application_count }}">
                                    @csrf
                                    <input type="hidden" name="confirmed_active_applications" value="0">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Archive</button>
                                </form>
                            @else
                                <form action="{{ route('student.resume.restore', $resume->id, false) }}" method="POST" data-ajax-resume="restore">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Restore</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr data-resume-empty><td colspan="2" class="text-center text-muted py-4">{{ $view === 'archived' ? 'No archived resumes.' : 'No resumes generated yet. Click “Generate New Resume” to create one.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($resumes->hasPages())<div class="card-footer">{{ $resumes->links() }}</div>@endif
</div>
@endsection
