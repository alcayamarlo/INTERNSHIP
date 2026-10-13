@extends('layouts.app')

@section('title', 'Internships')

@push('styles')
<style>
    .student-page-shell {
        padding: 6px 0 0;
        color: var(--sb-ink);
    }

    .student-page-title {
        margin: 0 0 8px;
        color: var(--sb-ink);
        font-size: clamp(1.8rem, 1.2vw + 1.1rem, 2.5rem);
        font-weight: 800;
        letter-spacing: -0.05em;
    }

    .student-page-subtitle {
        margin: 0;
        color: var(--sb-muted);
        font-size: 0.95rem;
    }

    .student-page-shell .card,
    .student-page-shell .form-control,
    .student-page-shell .form-select,
    .student-page-shell .btn {
        border-color: var(--sb-border);
        color: var(--sb-ink);
    }

    .student-page-shell .card {
        background: var(--sb-surface);
        border-radius: 14px;
    }

    .student-page-shell .card:hover {
        border-color: var(--sb-border);
    }

    .student-page-shell .card-header {
        background: var(--sb-surface-soft);
        border-bottom: 1px solid var(--sb-border);
        padding: 1rem 1.1rem;
    }

    .student-page-shell .btn-primary {
        background: var(--sb-brand);
        color: #fff;
    }

    .student-page-shell .btn-outline-secondary {
        border-color: var(--sb-border);
        color: var(--sb-ink);
    }

    .student-page-shell .border {
        border-color: var(--sb-border) !important;
    }

    .student-page-shell .bg-light {
        background: var(--sb-surface-soft) !important;
        color: var(--sb-ink) !important;
    }

    .student-page-shell .text-primary {
        color: var(--sb-brand-content) !important;
    }

    .student-page-shell .badge {
        border-radius: 999px;
        padding: 0.4rem 0.7rem;
        font-weight: 600;
    }

    .student-page-shell .card-footer {
        background: var(--sb-surface);
        border-top-color: var(--sb-border);
    }

    .student-page-shell .form-control,
    .student-page-shell .form-select {
        background-color: var(--sb-surface) !important;
    }
</style>
@endpush

@section('content')
<div class="student-page-shell">
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="student-page-title">Browse Internships</h1>
        <p class="student-page-subtitle">Find opportunities matched to your competencies</p>
    </div>
</div>

@if(count($recommendations) > 0)
<div class="card mb-4 border-success">
    <div class="card-header bg-white"><h5 class="mb-0 text-accent"><i class="bi bi-stars"></i> Top Recommendations</h5></div>
    <div class="card-body">
        <div class="row g-3">
            @foreach($recommendations as $internship)
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <h6><a href="{{ route('student.internships.show', $internship) }}" class="text-decoration-none">{{ $internship->title }}</a></h6>
                        <small class="text-muted">{{ $internship->employer->company_name }}</small>
                        <div class="mt-2"><span class="badge bg-success">{{ $internship->match_percentage }}% match</span></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <input type="text" class="form-control" name="search" placeholder="Search title..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <input type="text" class="form-control" name="location" placeholder="Location" value="{{ request('location') }}">
            </div>
            <div class="col-md-2">
                <input type="text" class="form-control" name="company" placeholder="Company" value="{{ request('company') }}">
            </div>
            <div class="col-md-2">
                <input type="text" class="form-control" name="skill" placeholder="Skill" value="{{ request('skill') }}">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
                <a href="{{ route('student.internships.index') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-4">
    @forelse($internships as $internship)
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="badge bg-{{ $internship->status === 'open' ? 'success' : 'secondary' }}">{{ ucfirst($internship->status) }}</span>
                        <span class="badge bg-light text-dark">{{ $internship->work_setup->label() }}</span>
                    </div>
                    <h5 class="card-title">{{ $internship->title }}</h5>
                    <p class="text-muted small mb-2"><i class="bi bi-building"></i> {{ $internship->employer->company_name }}</p>
                    @if($internship->location)<p class="text-muted small mb-2"><i class="bi bi-geo-alt"></i> {{ $internship->location }}</p>@endif
                    <p class="card-text small">{{ Str::limit($internship->description, 120) }}</p>
                    @if($internship->requirementsList->count())
                        <div class="mt-2">
                            @foreach($internship->requirementsList->take(3) as $req)
                                <span class="badge bg-primary bg-opacity-10 text-primary me-1">{{ $req->requirement_name }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    <a href="{{ route('student.internships.show', $internship) }}" class="btn btn-outline-primary btn-sm w-100 mb-2">View Details</a>
                    @if($appliedInternshipIds->contains($internship->id))
                        <a href="{{ route('student.applications.index') }}" class="btn btn-success btn-sm w-100" aria-label="Already applied; view My Applications">Applied · View application</a>
                    @elseif($resumes->isNotEmpty())
                        <form method="POST" action="{{ route('student.internships.apply', $internship) }}" data-ajax-application>
                            @csrf
                            <label class="visually-hidden" for="resume-{{ $internship->id }}">Resume for {{ $internship->title }}</label>
                            <select id="resume-{{ $internship->id }}" name="resume_id" class="form-select form-select-sm mb-2" required>
                                <option value="">Choose a resume</option>
                                @foreach($resumes as $resume)
                                    <option value="{{ $resume->id }}">{{ $resume->category?->label() ?? 'Resume' }}{{ $resume->generated_at ? ' · '.$resume->generated_at->format('M Y') : '' }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm w-100">Apply</button>
                            <div data-application-feedback class="small mt-2" role="status" aria-live="polite"></div>
                        </form>
                    @else
                        <a href="{{ route('student.resume.index') }}" class="btn btn-outline-secondary btn-sm w-100">Create a resume to apply</a>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-light text-center">No internships found matching your criteria.</div></div>
    @endforelse
</div>

@if($internships->hasPages())<div class="mt-4">{{ $internships->links() }}</div>@endif
</div>
@endsection
