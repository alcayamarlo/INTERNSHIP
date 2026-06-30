@extends('layouts.app')

@section('title', 'Add Competency')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-1">Add New Competency</h1>
    <p class="text-muted mb-0">Add a skill, certification, training, or other qualification</p>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('student.competencies.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Competency Name *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" placeholder="e.g., JavaScript, Leadership, Project Management" required>
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
                                    <option value="{{ $category->value }}" {{ old('category') === $category->value ? 'selected' : '' }}>
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
                                    <option value="{{ $level->value }}" {{ old('proficiency_level') === $level->value ? 'selected' : '' }}>
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
                        <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="4" placeholder="Describe your experience with this competency...">{{ old('description') }}</textarea>
                        <small class="text-muted">Optional. Max 1000 characters.</small>
                        @error('description')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Date Obtained</label>
                            <input type="date" class="form-control @error('obtained_at') is-invalid @enderror" name="obtained_at" value="{{ old('obtained_at') }}">
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
                            <i class="bi bi-check-circle"></i> Add Competency
                        </button>
                        <a href="{{ route('student.competencies.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick Tips -->
        <div class="card mt-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-lightbulb"></i> Tips for Adding Competencies</h5>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>Be specific: "JavaScript ES6+" instead of just "JavaScript"</li>
                    <li>Include certifications and formal training</li>
                    <li>Set realistic proficiency levels</li>
                    <li>Include a date if the skill is recent</li>
                    <li>Description helps employers understand your experience</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
