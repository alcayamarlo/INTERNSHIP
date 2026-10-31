@extends('layouts.app')

@section('title', 'Upload Portfolio Item')

@section('content')
<x-page-header title="Upload to Portfolio" subtitle="Add projects and supporting documents" />

<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Tab Navigation -->
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="portfolio-tab" data-bs-toggle="tab" data-bs-target="#portfolio" type="button" role="tab" aria-controls="portfolio" aria-selected="true">
                    <i class="bi bi-folder"></i> Portfolio Item
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="certificate-tab" data-bs-toggle="tab" data-bs-target="#certificate" type="button" role="tab" aria-controls="certificate" aria-selected="false">
                    <i class="bi bi-award"></i> Certificate
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Portfolio Upload -->
            <div class="tab-pane fade show active" id="portfolio" role="tabpanel" aria-labelledby="portfolio-tab">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('student.portfolio.store') }}" enctype="multipart/form-data" data-ajax-form data-ajax-reset="true">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Title <span class="text-danger" aria-hidden="true">*</span></label>
                                <input type="text" class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title') }}" placeholder="e.g., E-Commerce Website Project" required>
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">File Type <span class="text-danger" aria-hidden="true">*</span></label>
                                <select class="form-select @error('type') is-invalid @enderror" name="type" required>
                                    <option value="">Select a type</option>
                                    @foreach($types as $type)
                                        <option value="{{ $type->value }}" {{ old('type') === $type->value ? 'selected' : '' }}>
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
                                <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="4" placeholder="Describe this portfolio item...">{{ old('description') }}</textarea>
                                <small class="text-muted">Optional. Max 1000 characters.</small>
                                @error('description')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Upload File <span class="text-danger" aria-hidden="true">*</span></label>
                                <div class="input-group">
                                    <input type="file" class="form-control @error('file') is-invalid @enderror" name="file" id="portfolioFile" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                                    <label class="input-group-text" for="portfolioFile">Choose</label>
                                </div>
                                <small class="text-muted d-block mt-2">Allowed: PDF, DOC, DOCX, JPG, JPEG, PNG (Max 10 MB)</small>
                                @error('file')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-cloud-upload"></i> Upload File
                                </button>
                                <a href="{{ route('student.profile.edit') }}#portfolio" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Certificate Upload -->
            <div class="tab-pane fade" id="certificate" role="tabpanel" aria-labelledby="certificate-tab">
                <div class="card">
                    <div class="card-body">
                        <p class="mb-3">Certificates are now added in My Certificates, together with the skills you learned in them.</p>
                        <a href="{{ route('student.competencies.index', ['add' => 1]) }}" class="btn btn-primary"><i class="bi bi-award" aria-hidden="true"></i> + Add a certificate</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Info Box -->
<div class="row justify-content-center mt-4">
    <div class="col-lg-8">
        <div class="alert alert-info">
            <strong><i class="bi bi-info-circle"></i> Supported File Types:</strong>
            <ul class="mb-0 mt-2">
                <li><strong>PDF:</strong> Certificates, transcripts, documents</li>
                <li><strong>Images (JPG, JPEG, PNG):</strong> Project screenshots, award certificates</li>
                <li><strong>DOC, DOCX:</strong> Documents and reports</li>
            </ul>
        </div>
    </div>
</div>
@endsection
