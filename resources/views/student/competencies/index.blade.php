@extends('layouts.app')

@section('title', 'My Competencies')

@section('content')
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 mb-1">My Competencies</h1>
            <p class="text-muted mb-0">Earned skills backed by assessments, certificates, or verified training</p>
        </div>
        <a href="{{ route('student.competencies.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Submit Evidence
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3 text-muted small">Total Competencies</h6>
                <div class="h3 mb-0 text-primary">{{ $stats['total'] }}</div>
                <small class="text-muted d-block mt-2">Skills & certifications</small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3 text-muted small">Technical Skills</h6>
                <div class="h3 mb-0 text-accent">{{ $stats['technical'] }}</div>
                <small class="text-muted d-block mt-2">Programming & tools</small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3 text-muted small">Soft Skills</h6>
                <div class="h3 mb-0 text-success">{{ $stats['soft'] }}</div>
                <small class="text-muted d-block mt-2">Leadership & communication</small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3 text-muted small">Average Level</h6>
                <div class="h3 mb-0 text-warning">{{ $stats['average_level'] }}</div>
                <small class="text-muted d-block mt-2">Overall proficiency</small>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('student.competencies.index') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" name="search" placeholder="Search competencies..." value="{{ $filters['search'] }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Category</label>
                <select class="form-select" name="category">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->value }}" {{ $filters['category'] === $category->value ? 'selected' : '' }}>
                            {{ $category->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Level</label>
                <select class="form-select" name="level">
                    <option value="">All Levels</option>
                    @foreach($levels as $level)
                        <option value="{{ $level->value }}" {{ $filters['level'] === $level->value ? 'selected' : '' }}>
                            {{ $level->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Competencies Grid -->
@if($competencies->count())
    <div class="row g-4 mb-4">
        @foreach($competencies as $competency)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="card-title mb-1">{{ $competency->name }}</h5>
                                <span class="badge bg-light text-dark">{{ $competency->category->label() }}</span>
                            </div>
                            <span class="badge" style="background-color: {{ getProficiencyColor($competency->proficiency_level->value) }}">
                                {{ $competency->proficiency_level->label() }}
                            </span>
                        </div>

                        @if($competency->description)
                            <p class="text-muted small mb-3">{{ Str::limit($competency->description, 100) }}</p>
                        @endif

                        @if($competency->obtained_at)
                            <small class="text-muted d-block mb-3">
                                <i class="bi bi-calendar"></i> {{ $competency->obtained_at->format('M d, Y') }}
                            </small>
                        @endif

                        <div class="small mb-3"><i class="bi bi-shield-check"></i> Evidence: {{ $competency->verification_status === 'verified' ? 'Verified' : 'Pending Verification' }} @if($competency->evidence_name)<span class="text-muted">({{ $competency->evidence_name }})</span>@endif</div>

                        <div class="btn-group btn-group-sm w-100" role="group">
                            <a href="{{ route('student.competencies.show', $competency) }}" class="btn btn-outline-primary" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('student.competencies.edit', $competency) }}" class="btn btn-outline-secondary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('student.competencies.destroy', $competency) }}" class="d-inline" onsubmit="return confirm('Delete this competency?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center">
        {{ $competencies->links() }}
    </div>
@else
    <div class="alert alert-info text-center py-5">
        <i class="bi bi-info-circle fs-1"></i>
        <p class="mt-3 mb-0">No competencies found. <a href="{{ route('student.competencies.create') }}">Add your first competency →</a></p>
    </div>
@endif
@endsection

@php
function getProficiencyColor($level) {
    return match($level) {
        'beginner' => '#6B7280',
        'intermediate' => '#F59E0B',
        'advanced' => '#3B82F6',
        'expert' => '#10B981',
        default => '#6B7280',
    };
}
@endphp
