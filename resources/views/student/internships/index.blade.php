@extends('layouts.app')

@section('title', 'Internships')

@section('content')
<x-breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('student.dashboard')], ['label' => 'Internships']]" />

<x-page-header
    title="Browse Internships"
    subtitle="Find opportunities matched to your competencies"
>
    <a href="{{ route('student.applications.index') }}" class="btn btn-primary"><i class="bi bi-send" aria-hidden="true"></i> My Applications</a>
</x-page-header>

@if(count($recommendations) > 0)
<div class="card mb-4">
    <div class="card-header">
        <h2><i class="bi bi-stars" aria-hidden="true"></i> Top Recommendations</h2>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @foreach($recommendations as $internship)
                <div class="col-md-4">
                    <div class="sb-tile">
                        <h3><a href="{{ route('student.internships.show', $internship) }}">{{ $internship->title }}</a></h3>
                        <p class="text-muted small mb-2">{{ $internship->employer->company_name }}</p>
                        <x-status-badge :status="$internship->match_percentage >= 80 ? 'success' : ($internship->match_percentage >= 60 ? 'warning' : 'danger')" :label="$internship->match_percentage.'% match'" />
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<form method="GET" class="sb-toolbar" role="search" aria-label="Filter internships">
    @if(request()->filled('scope'))<input type="hidden" name="scope" value="{{ $scope }}">@endif
    <input type="text" class="form-control" name="search" placeholder="Search title..." aria-label="Search title" value="{{ request('search') }}">
    <input type="text" class="form-control" name="location" placeholder="Location" aria-label="Location" value="{{ request('location') }}">
    <input type="text" class="form-control" name="company" placeholder="Company" aria-label="Company" value="{{ request('company') }}">
    <input type="text" class="form-control" name="skill" placeholder="Skill" aria-label="Skill" value="{{ request('skill') }}">
    <div class="sb-toolbar-actions">
        <button type="submit" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Filter</button>
        <a href="{{ route('student.internships.index') }}" class="btn btn-outline-secondary">Clear</a>
    </div>
</form>

<p class="small text-muted mb-3" data-internship-scope="{{ $scope }}">
    @if($scope === 'program')
        <i class="bi bi-mortarboard" aria-hidden="true"></i>
        Showing internships for your program ({{ $programName }}), best competency match first. Searching looks across every program.
        <a href="{{ route('student.internships.index', array_merge(request()->except('page'), ['scope' => 'all'])) }}">Show all programs</a>
    @else
        <i class="bi bi-globe" aria-hidden="true"></i>
        Showing internships from all programs, best competency match first. A low match does not stop you from applying.
        @if($programName)
            <a href="{{ route('student.internships.index', array_merge(request()->except('page'), ['scope' => 'program'])) }}">Only my program ({{ $programName }})</a>
        @endif
    @endif
</p>

<div class="row g-4">
    @forelse($internships as $internship)
        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
                        <x-status-badge :status="$internship->match_percentage >= 80 ? 'success' : ($internship->match_percentage >= 60 ? 'warning' : ($internship->match_percentage > 0 ? 'info' : 'neutral'))" :label="$internship->match_percentage.'% match'" />
                        <span class="sb-tag">{{ $internship->work_setup->label() }}</span>
                    </div>
                    <h3 class="h5 mb-2">{{ $internship->title }}</h3>
                    <p class="sb-meta"><i class="bi bi-building" aria-hidden="true"></i> {{ $internship->employer->company_name }}</p>
                    @if($internship->location)<p class="sb-meta"><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $internship->location }}</p>@endif
                    <p class="sb-meta"><i class="bi bi-mortarboard" aria-hidden="true"></i> {{ $internship->programs->isEmpty() ? 'All programs' : $internship->programs->pluck('code')->implode(', ') }}</p>
                    <p class="card-text small">{{ Str::limit($internship->description, 120) }}</p>
                    @if($internship->requirementsList->count())
                        <div class="d-flex flex-wrap gap-1 mt-2">
                            @foreach($internship->requirementsList->take(3) as $req)
                                <span class="sb-tag sb-tag--brand">{{ $req->requirement_name }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="card-footer d-grid gap-2">
                    <a href="{{ route('student.internships.show', $internship) }}" class="btn btn-outline-primary btn-sm">View Details</a>
                    @if($appliedInternshipIds->contains($internship->id))
                        <a href="{{ route('student.applications.index') }}" class="btn btn-outline-secondary btn-sm" aria-label="Already applied; view My Applications"><i class="bi bi-check-circle" aria-hidden="true"></i> Applied · View application</a>
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
                        <a href="{{ route('student.resume.index') }}" class="btn btn-outline-secondary btn-sm">Create a resume to apply</a>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <x-empty-state icon="bi-search" title="No internships found" message="Try different keywords or clear the filters to see everything that is open.">
                    <a href="{{ route('student.internships.index') }}" class="btn btn-outline-primary">Clear filters</a>
                </x-empty-state>
            </div>
        </div>
    @endforelse
</div>

@if($internships->hasPages())<div class="mt-4">{{ $internships->links() }}</div>@endif

@endsection
