@extends('layouts.app')

@section('title', 'Internships')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Browse Internships</h1>
        <p class="text-muted mb-0">Find opportunities matched to your competencies</p>
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
                <div class="card-footer bg-white">
                    <a href="{{ route('student.internships.show', $internship) }}" class="btn btn-primary btn-sm w-100">View Details</a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-light text-center">No internships found matching your criteria.</div></div>
    @endforelse
</div>

@if($internships->hasPages())<div class="mt-4">{{ $internships->links() }}</div>@endif
@endsection
