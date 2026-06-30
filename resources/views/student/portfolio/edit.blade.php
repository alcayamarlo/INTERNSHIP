@extends('layouts.app')

@section('title', 'Edit Portfolio Item')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-1">Edit Portfolio Item</h1>
    <p class="text-muted mb-0">Update your portfolio information</p>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('student.portfolio.update', $portfolio) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title', $portfolio->title) }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">File Type *</label>
                        <select class="form-select @error('type') is-invalid @enderror" name="type" required>
                            <option value="">Select a type</option>
                            @foreach($types as $type)
                                <option value="{{ $type->value }}" {{ old('type', $portfolio->type->value) === $type->value ? 'selected' : '' }}>
                                    {{ $type->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="4">{{ old('description', $portfolio->description) }}</textarea>
                        <small class="text-muted">Optional. Max 1000 characters.</small>
                        @error('description')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Current File Info -->
                    <div class="mb-3">
                        <h6 class="text-muted">Current File</h6>
                        <div class="alert alert-light">
                            <small>
                                <i class="bi bi-file"></i>
                                {{ pathinfo($portfolio->file_path, PATHINFO_BASENAME) }}
                                <span class="badge bg-success ms-2">{{ strtoupper(pathinfo($portfolio->file_path, PATHINFO_EXTENSION)) }}</span>
                            </small>
                        </div>
                    </div>

                    <!-- File Replacement -->
                    <div class="mb-3">
                        <label class="form-label">Replace File (Optional)</label>
                        <div class="input-group">
                            <input type="file" class="form-control @error('file') is-invalid @enderror" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <label class="input-group-text">Choose</label>
                        </div>
                        <small class="text-muted d-block mt-2">Leave empty to keep current file. Allowed: PDF, DOC, DOCX, JPG, JPEG, PNG (Max 10 MB)</small>
                        @error('file')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Save Changes
                        </button>
                        <a href="{{ route('student.portfolio.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="card mt-4 border-danger">
            <div class="card-header bg-danger-light">
                <h5 class="mb-0 text-danger"><i class="bi bi-exclamation-triangle"></i> Danger Zone</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Deleting this portfolio item cannot be undone.</p>
                <form method="POST" action="{{ route('student.portfolio.destroyPortfolio', $portfolio) }}" style="display:inline;" onsubmit="return confirm('Are you sure? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Delete Portfolio Item
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
