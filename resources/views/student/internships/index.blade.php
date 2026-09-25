@extends('layouts.app')

@section('title', 'Internships')

@push('styles')
<style>
    .student-page-shell {
        padding: 6px 0 0;
        color: #edf8ff;
    }

    .student-page-title {
        margin: 0 0 8px;
        color: #f4f9ff;
        font-size: clamp(1.8rem, 1.2vw + 1.1rem, 2.5rem);
        font-weight: 800;
        letter-spacing: -0.05em;
    }

    .student-page-subtitle {
        margin: 0;
        color: rgba(186,209,228,.8);
        font-size: 0.95rem;
    }

    .student-page-shell .card,
    .student-page-shell .form-control,
    .student-page-shell .form-select,
    .student-page-shell .btn {
        border-color: rgba(117,176,215,.18) !important;
        background: rgba(16,31,45,.86) !important;
        color: #edf8ff !important;
    }

    .student-page-shell .card {
        border-radius: 18px;
        border: 1px solid rgba(148,163,184,.14);
        box-shadow: 0 10px 22px rgba(2,9,20,.12);
        overflow: hidden;
    }

    .student-page-shell .card:hover {
        border-color: rgba(49,217,244,.36) !important;
    }

    .student-page-shell .card-header {
        background: rgba(13,30,44,.92) !important;
        border-bottom: 1px solid rgba(117,176,215,.18);
        padding: 1rem 1.1rem;
    }

    .student-page-shell .btn-primary {
        border: 0;
        background: linear-gradient(135deg, #6ab6d7, #4f9ed1) !important;
        color: #eef9ff !important;
        font-weight: 700;
        box-shadow: 0 8px 20px rgba(37,194,255,.15);
    }

    .student-page-shell .btn-outline-secondary {
        border-color: rgba(117,176,215,.28);
        color: #bed5e7 !important;
    }

    .student-page-shell .border {
        border-color: rgba(117,176,215,.28) !important;
    }

    .student-page-shell .bg-light {
        background: rgba(238,244,248,.08) !important;
        color: #edf8ff !important;
    }

    .student-page-shell .text-primary {
        color: #58d9ff !important;
    }

    .student-page-shell .badge {
        border-radius: 999px;
        padding: 0.4rem 0.7rem;
        font-weight: 700;
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
</div>
@endsection
