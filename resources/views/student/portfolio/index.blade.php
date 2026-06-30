@extends('layouts.app')

@section('title', 'My Portfolio')

@section('content')
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 mb-1">My Portfolio</h1>
            <p class="text-muted mb-0">Upload and manage your certificates, projects, and supporting documents</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('student.portfolio.create') }}" class="btn btn-primary">
                <i class="bi bi-cloud-upload"></i> Upload File
            </a>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3 text-muted small">Total Files</h6>
                <div class="h3 mb-0 text-primary">{{ $stats['total'] }}</div>
                <small class="text-muted d-block mt-2">Documents uploaded</small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3 text-muted small">Certificates</h6>
                <div class="h3 mb-0 text-accent">{{ $stats['certificates'] }}</div>
                <small class="text-muted d-block mt-2">Professional certificates</small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3 text-muted small">Projects</h6>
                <div class="h3 mb-0 text-success">{{ $stats['projects'] }}</div>
                <small class="text-muted d-block mt-2">Project files</small>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3 text-muted small">Storage Used</h6>
                <div class="h3 mb-0 text-warning">{{ $stats['storage_usage'] }}</div>
                <small class="text-muted d-block mt-2">Total storage (10 MB limit)</small>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('student.portfolio.index') }}" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" name="search" placeholder="Search portfolio..." value="{{ $filters['search'] }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">File Type</label>
                <select class="form-select" name="type">
                    <option value="">All Types</option>
                    @foreach($types as $type)
                        <option value="{{ $type->value }}" {{ $filters['type'] === $type->value ? 'selected' : '' }}>
                            {{ $type->label() }}
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

<!-- Portfolio Items -->
@if($portfolios->count())
    <div class="row g-4 mb-4">
        @foreach($portfolios as $item)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="card-title mb-1">{{ Str::limit($item->title, 40) }}</h5>
                                <span class="badge bg-light text-dark">{{ $item->type->label() }}</span>
                            </div>
                            <span class="badge bg-success">
                                <i class="bi bi-file"></i>
                                {{ strtoupper(pathinfo($item->file_path, PATHINFO_EXTENSION)) }}
                            </span>
                        </div>

                        @if($item->description)
                            <p class="text-muted small mb-3">{{ Str::limit($item->description, 80) }}</p>
                        @endif

                        <small class="text-muted d-block mb-3">
                            <i class="bi bi-calendar"></i> {{ $item->created_at->format('M d, Y') }}
                        </small>

                        <div class="btn-group btn-group-sm w-100" role="group">
                            <a href="{{ route('student.portfolio.show', $item) }}" class="btn btn-outline-primary" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('student.portfolio.edit', $item) }}" class="btn btn-outline-secondary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="{{ route('student.portfolio.download', $item) }}" class="btn btn-outline-success" title="Download">
                                <i class="bi bi-download"></i>
                            </a>
                            <form method="POST" action="{{ route('student.portfolio.destroyPortfolio', $item) }}" class="d-inline" onsubmit="return confirm('Delete this file?');">
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
        {{ $portfolios->links() }}
    </div>
@else
    <div class="alert alert-info text-center py-5">
        <i class="bi bi-cloud-upload fs-1"></i>
        <p class="mt-3 mb-0">No portfolio items yet. <a href="{{ route('student.portfolio.create') }}">Upload your first file →</a></p>
    </div>
@endif
@endsection
