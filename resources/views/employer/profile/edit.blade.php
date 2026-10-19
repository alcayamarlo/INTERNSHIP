@extends('layouts.app')

@section('title', 'Edit Company Profile')

@section('content')

<x-page-header title="Company Profile" subtitle="Update your company information and contact details" />

<div class="row g-4">

    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="sb-logo-box mx-auto mb-3">
                    @php
                        $employerLogoUrl = \App\Support\PublicFileUrl::relative($employer->logo);
                        $employerInitials = collect(preg_split('/\s+/', trim($employer->company_name)))
                            ->filter()
                            ->map(fn ($part) => mb_substr($part, 0, 1))
                            ->take(2)
                            ->implode('');
                    @endphp
                    <img data-company-logo src="{{ $employerLogoUrl ?? '' }}" alt="{{ $employer->company_name }} logo" @if(!$employerLogoUrl) hidden @endif onerror="this.hidden=true;this.nextElementSibling.hidden=false;">
                    <span data-company-initials @if($employerLogoUrl) hidden @endif>{{ strtoupper($employerInitials ?: 'C') }}</span>
                </div>

                <form method="POST" action="{{ route('employer.profile.update') }}" enctype="multipart/form-data" id="logoForm" class="d-none">
                    @csrf
                    @method('PUT')
                    <input type="file" name="company_logo" id="logoInput" accept="image/jpeg,image/jpg,image/png">
                </form>

                <button type="button" class="btn btn-outline-primary" id="uploadCompanyLogo">
                    <i class="bi bi-cloud-upload" aria-hidden="true"></i> {{ $employer->logo ? 'Change Logo' : 'Upload Logo' }}
                </button>
                <p class="text-muted small mt-2 mb-0">JPG or PNG</p>
                <div data-employer-feedback class="small mt-2" role="status" aria-live="polite"></div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h2><i class="bi bi-building" aria-hidden="true"></i> Company Information</h2>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('employer.profile.update') }}" enctype="multipart/form-data" data-ajax-form id="employerProfileForm">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label" for="company_name">Company Name <span class="text-danger" aria-hidden="true">*</span></label>
                        <input id="company_name" type="text" class="form-control @error('company_name') is-invalid @enderror" name="company_name" value="{{ old('company_name', $employer->company_name) }}" required>
                        @error('company_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="industry">Industry <span class="text-danger" aria-hidden="true">*</span></label>
                            <input id="industry" type="text" class="form-control @error('industry') is-invalid @enderror" name="industry" value="{{ old('industry', $employer->industry) }}" required>
                            @error('industry')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="company_size">Company Size</label>
                            <select id="company_size" class="form-select @error('company_size') is-invalid @enderror" name="company_size">
                                <option value="">Select...</option>
                                <option value="1-10" {{ old('company_size', $employerInfo['company_size'] ?? '') === '1-10' ? 'selected' : '' }}>1-10 employees</option>
                                <option value="11-50" {{ old('company_size', $employerInfo['company_size'] ?? '') === '11-50' ? 'selected' : '' }}>11-50 employees</option>
                                <option value="51-200" {{ old('company_size', $employerInfo['company_size'] ?? '') === '51-200' ? 'selected' : '' }}>51-200 employees</option>
                                <option value="201-500" {{ old('company_size', $employerInfo['company_size'] ?? '') === '201-500' ? 'selected' : '' }}>201-500 employees</option>
                                <option value="500+" {{ old('company_size', $employerInfo['company_size'] ?? '') === '500+' ? 'selected' : '' }}>500+ employees</option>
                            </select>
                            @error('company_size')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="website">Website</label>
                        <input id="website" type="url" class="form-control @error('website') is-invalid @enderror" name="website" placeholder="https://www.example.com" value="{{ old('website', $employer->website) }}">
                        @error('website')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="description">Company Description</label>
                        <textarea id="description" class="form-control @error('description') is-invalid @enderror" name="description" rows="3" placeholder="Describe your company, mission, and culture">{{ old('description', $employer->description) }}</textarea>
                        @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle" aria-hidden="true"></i> Save Changes
                        </button>
                        <a href="{{ route('employer.dashboard') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle" aria-hidden="true"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('employerProfileForm');
    const uploadForm = document.getElementById('logoForm');
    const input = document.getElementById('logoInput');
    const button = document.getElementById('uploadCompanyLogo');
    const feedback = document.querySelector('[data-employer-feedback]');

    button.addEventListener('click', () => input.click());
    input.addEventListener('change', async () => {
        if (!input.files.length) return;

        const originalLabel = button.textContent;
        const data = new FormData(form);
        data.set('_token', uploadForm.elements.namedItem('_token').value);
        data.set('_method', 'PUT');
        data.set('company_logo', input.files[0]);
        button.disabled = true;
        button.textContent = 'Uploading…';
        feedback.textContent = '';
        feedback.className = 'small mt-2';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: data,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const payload = await response.json();
            if (!response.ok) {
                const firstError = payload.errors ? Object.values(payload.errors).flat()[0] : null;
                throw new Error(firstError || payload.message || 'Unable to upload company logo.');
            }

            const image = document.querySelector('[data-company-logo]');
            image.src = payload.logo_url;
            image.hidden = false;
            document.querySelector('[data-company-initials]').hidden = true;
            feedback.textContent = payload.message;
            feedback.classList.add('text-success');
        } catch (error) {
            feedback.textContent = error.message || 'Unable to upload company logo.';
            feedback.classList.add('text-danger');
        } finally {
            input.value = '';
            button.disabled = false;
            button.textContent = originalLabel;
        }
    });
})();
</script>
@endpush
