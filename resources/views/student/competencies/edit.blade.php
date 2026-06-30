@extends('layouts.app')

@section('title', 'Edit Competency')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-1">Edit Competency</h1>
    <p class="text-muted mb-0">Update your competency information</p>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('student.competencies.update', $competency) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Competency Name *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $competency->name) }}" placeholder="e.g., JavaScript, Leadership, Project Management" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Category *</label>
                            <select class="form-select @error('category') is-invalid @enderror" name="category" required>
                                <option value="">Select a category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->value }}" {{ old('category', $competency->category->value) === $category->value ? 'selected' : '' }}>
                                        {{ $category->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Proficiency Level *</label>
                            <select class="form-select @error('proficiency_level') is-invalid @enderror" name="proficiency_level" required>
                                <option value="">Select a level</option>
                                @foreach($levels as $level)
                                    <option value="{{ $level->value }}" {{ old('proficiency_level', $competency->proficiency_level->value) === $level->value ? 'selected' : '' }}>
                                        {{ $level->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('proficiency_level')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="4" placeholder="Describe your experience with this competency...">{{ old('description', $competency->description) }}</textarea>
                        <small class="text-muted">Optional. Max 1000 characters.</small>
                        @error('description')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Date Obtained</label>
                            <input type="date" class="form-control @error('obtained_at') is-invalid @enderror" name="obtained_at" value="{{ old('obtained_at', $competency->obtained_at?->format('Y-m-d')) }}">
                            <small class="text-muted">Optional. When did you acquire this competency?</small>
                            @error('obtained_at')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Issuing Organization</label>
                            <input type="text" class="form-control @error('issuing_organization') is-invalid @enderror" name="issuing_organization" value="{{ old('issuing_organization') }}" placeholder="e.g., Coursera, Google, Your Company">
                            <small class="text-muted">Optional. Organization that issued certification.</small>
                            @error('issuing_organization')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Save Changes
                        </button>
                        <a href="{{ route('student.competencies.index') }}" class="btn btn-outline-secondary">
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
                <p class="text-muted mb-3">Deleting this competency cannot be undone.</p>
                <form method="POST" action="{{ route('student.competencies.destroy', $competency) }}" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this competency? This action cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Delete Competency
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
