@extends('layouts.app')

@section('title', 'Upload Portfolio Item')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-1">Upload to Portfolio</h1>
    <p class="text-muted mb-0">Add certificates, projects, and supporting documents</p>
</div>

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
                        <form method="POST" action="{{ route('student.portfolio.store') }}" enctype="multipart/form-data">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Title *</label>
                                <input type="text" class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title') }}" placeholder="e.g., E-Commerce Website Project" required>
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">File Type *</label>
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
                                <label class="form-label">Upload File *</label>
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
                                <a href="{{ route('student.portfolio.index') }}" class="btn btn-outline-secondary">
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
                        <form method="POST" action="{{ route('student.certificates.store') }}" enctype="multipart/form-data">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Certificate Title *</label>
                                <input type="text" class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title') }}" placeholder="e.g., AWS Solutions Architect Associate" required>
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Issuing Organization</label>
                                    <input type="text" class="form-control @error('issuer') is-invalid @enderror" name="issuer" value="{{ old('issuer') }}" placeholder="e.g., Amazon Web Services">
                                    @error('issuer')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Expiration Date</label>
                                    <input type="date" class="form-control @error('expiration_date') is-invalid @enderror" name="expiration_date" value="{{ old('expiration_date') }}">
                                    @error('expiration_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Issue Date</label>
                                    <input type="date" class="form-control @error('issue_date') is-invalid @enderror" name="issue_date" value="{{ old('issue_date') }}">
                                    @error('issue_date')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3 mt-3">
                                <label class="form-label">Upload Certificate File *</label>
                                <div class="input-group">
                                    <input type="file" class="form-control @error('file') is-invalid @enderror" name="file" id="certificateFile" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <label class="input-group-text" for="certificateFile">Choose</label>
                                </div>
                                <small class="text-muted d-block mt-2">Allowed: PDF, JPG, JPEG, PNG (Max 10 MB)</small>
                                @error('file')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="alert alert-warning small">Uploaded evidence is marked <strong>Pending Verification</strong> until an authorized evaluator reviews it.</div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-cloud-upload"></i> Upload Certificate
                                </button>
                                <a href="{{ route('student.portfolio.index') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </a>
                            </div>
                        </form>
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
