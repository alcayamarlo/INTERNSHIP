@extends('layouts.app')

@section('title', 'Resume')

@section('content')
<x-page-header title="Resume Builder" subtitle="Generate PDF resumes from your profile data">
    <div class="text-md-end">
        <form action="{{ route('student.resume.generate') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Generate New Resume</button>
        </form>
        <small class="text-muted d-block mt-1" data-resume-limit-note>Only your {{ $maxResumes }} latest resumes are kept. Generating a new one removes the oldest.</small>
    </div>
</x-page-header>

<div class="card">
    <div class="card-header"><h2>Generated Resumes</h2></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Generated</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($resumes as $resume)
                    @php
                        $generatedAt = $resume->generated_at ?? $resume->created_at;
                        $applicationCount = (int) ($linkedApplications[$resume->id] ?? 0);
                        $replacement = $resumes->first(fn ($other) => $other->id !== $resume->id);
                    @endphp
                    <tr data-resume-row>
                        <td>
                            {{ $generatedAt->format('M d, Y h:i A') }}
                            @if($loop->first)<span class="badge text-bg-success ms-1">Newest</span>@endif
                            @if($applicationCount > 0)
                                <small class="text-muted d-block">Used in {{ $applicationCount }} {{ Str::plural('application', $applicationCount) }}</small>
                            @endif
                        </td>
                        <td class="sb-row-actions">
                            <a href="{{ route('student.resume.view', $resume, false) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye" aria-hidden="true"></i> View
                            </a>
                            <a href="{{ route('student.resume.download', $resume, false) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-download" aria-hidden="true"></i> Download
                            </a>
                            <form action="{{ route('student.resume.destroy', $resume, false) }}" method="POST"
                                data-confirm="Delete the resume generated {{ $generatedAt->format('M d, Y h:i A') }}?{{ $applicationCount > 0 ? ($replacement ? ' Your applications that used it will switch to your newest resume.' : ' Your applications that used it will have no resume until you generate a new one.') : '' }}"
                                data-confirm-title="Delete resume?" data-confirm-label="Delete">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash" aria-hidden="true"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-center text-muted py-4">No resumes generated yet. Click “Generate New Resume” to create one.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
