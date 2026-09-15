@extends('layouts.app')

@section('title', 'My Portfolio')

@push('styles')
<style>
    .student-page-shell { padding: 8px 0; color: #edf8ff; }
    .student-page-shell .card, .student-page-shell .form-control, .student-page-shell .form-select, .student-page-shell .btn { border-color: rgba(117,176,215,.18) !important; background: rgba(12,24,36,.9) !important; color: #edf8ff !important; }
    .student-page-shell .card { border-radius: 18px; box-shadow: 0 8px 28px rgba(2,9,20,.2); }
    .student-page-shell .card:hover { border-color: rgba(49,217,244,.36) !important; }
    .student-page-shell .card-header { background: rgba(13,30,44,.92) !important; border-bottom: 1px solid rgba(117,176,215,.18); }
    .student-page-title { margin: 0 0 8px; color: #f4f9ff; font-size: clamp(2.2rem,2.3vw,3.2rem); font-weight: 800; letter-spacing: -.05em; }
    .student-page-subtitle { margin: 0; color: rgba(186,209,228,.8); font-size: 1.05rem; }
    .student-page-shell .btn-primary { border: 0; background: linear-gradient(135deg,#29d4ff,#25c7ff) !important; color: #062338 !important; font-weight: 700; box-shadow: 0 8px 20px rgba(37,194,255,.18); }
    .student-page-shell .card .h3 { color: #eef8ff; font-weight: 800; letter-spacing: -.04em; }
    .student-page-shell .table thead th { background: rgba(12,28,40,.9); color: rgba(220,235,246,.9); border-color: rgba(117,176,215,.16); }
    .student-page-shell .table td { color: rgba(216,232,243,.9); border-color: rgba(117,176,215,.08); }
    .student-page-shell .badge { border-radius: 999px; padding: .4rem .7rem; }
</style>
@endpush

@section('content')
<div class="student-page-shell">
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="student-page-title">My Portfolio</h1>
            <p class="student-page-subtitle">Upload and manage your certificates, projects, and supporting documents</p>
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
</div>
@endsection
