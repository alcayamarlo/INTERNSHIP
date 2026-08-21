@extends('layouts.app')

@section('title', 'Resume')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Resume Builder</h1>
        <p class="text-muted mb-0">Generate PDF resumes from your profile data</p>
    </div>
    <form action="{{ route('student.resume.generate') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-primary"><i class="bi bi-file-earmark-pdf"></i> Generate New Resume</button>
    </form>
</div>

@if(session('download'))
    <div class="alert alert-success">
        Resume generated! <a href="{{ route('student.resume.download', session('download')) }}" class="alert-link">Download now</a>
    </div>
@endif

<div class="card">
    <div class="card-header bg-white"><h5 class="mb-0">Generated Resumes</h5></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Generated</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($resumes as $resume)
                    <tr>
                        <td>{{ $resume->generated_at?->format('M d, Y h:i A') ?? $resume->created_at->format('M d, Y h:i A') }}</td>
                        <td>
                            <a href="{{ route('student.resume.view', $resume) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> View PDF
                            </a>
                            <a href="{{ route('student.resume.download', $resume) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-download"></i> Download PDF
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-center text-muted py-4">No resumes generated yet. Click "Generate New Resume" to create one.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($resumes->hasPages())<div class="card-footer">{{ $resumes->links() }}</div>@endif
</div>
@endsection
