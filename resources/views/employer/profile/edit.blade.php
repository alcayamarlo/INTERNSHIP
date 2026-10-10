@extends('layouts.app')

@section('title', 'Edit Company Profile')

@push('styles')
<style>
    .employer-page-shell {
        padding: 8px 0 0;
        color: #edf8ff;
    }

    .employer-page-header {
        margin-bottom: 1.75rem;
    }

    .employer-page-title {
        margin: 0 0 8px;
        color: #f4f9ff;
        font-size: clamp(3rem, 3vw + 1rem, 5rem);
        line-height: 1.02;
        font-weight: 900;
        letter-spacing: -0.055em;
    }

    .employer-page-subtitle {
        margin: 0;
        color: rgba(186, 211, 228, 0.82);
        font-size: 1.05rem;
        font-weight: 500;
    }

    .employer-profile-layout {
        display: grid;
        grid-template-columns: minmax(300px, 0.9fr) minmax(0, 1.9fr);
        gap: 1.5rem;
        align-items: start;
    }

    .employer-profile-upload,
    .employer-profile-panel {
        background: rgba(14, 31, 45, 0.84);
        border: 1px solid rgba(140, 167, 192, 0.18);
        border-radius: 18px;
        box-shadow: 0 10px 28px rgba(2, 9, 20, 0.18);
    }

    .employer-profile-upload {
        padding: 1rem;
        min-height: 380px;
    }

    .upload-box {
        height: 100%;
        min-height: 320px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 1.1rem;
        padding: 1rem;
        border-radius: 12px;
        background: rgba(16, 36, 52, 0.72);
        border: 1px solid rgba(140, 167, 192, 0.1);
    }

    .upload-logo {
        width: 160px;
        height: 160px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        background: rgba(18, 41, 58, 0.9);
        border: 1px solid rgba(140, 167, 192, 0.15);
    }

    .upload-logo i {
        font-size: 3.2rem;
        color: rgba(228, 239, 247, 0.85);
    }

    .upload-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        min-width: 160px;
        min-height: 38px;
        padding: 0.65rem 1rem;
        border-radius: 10px;
        border: 1px solid rgba(126, 204, 239, 0.38);
        background: rgba(17, 41, 56, 0.7);
        color: #dfeaf6;
        font-size: 0.88rem;
        font-weight: 700;
        cursor: pointer;
    }

    .employer-profile-panel {
        overflow: hidden;
    }

    .profile-panel-header {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        padding: 1rem 1.2rem;
        background: rgba(12, 30, 43, 0.76);
        border-bottom: 1px solid rgba(140, 167, 192, 0.12);
        color: #edf8ff;
        font-size: 1.15rem;
        font-weight: 800;
    }

    .profile-panel-header i {
        color: #dfeaf6;
    }

    .profile-panel-body {
        padding: 1.2rem 1.2rem 1rem;
    }

    .employer-form-group {
        margin-bottom: 1.2rem;
    }

    .employer-form-label {
        display: block;
        margin-bottom: 0.45rem;
        color: #dfeaf6;
        font-size: 0.86rem;
        font-weight: 700;
    }

    .employer-page-shell .form-control,
    .employer-page-shell .form-select {
        min-height: 48px;
        border: 1px solid rgba(140, 167, 192, 0.2);
        border-radius: 10px;
        background: rgba(10, 23, 35, 0.82);
        color: #f8fafc !important;
        font-size: 0.98rem;
        box-shadow: none;
    }

    .employer-page-shell textarea.form-control {
        min-height: 120px;
        resize: vertical;
    }

    .employer-page-shell .form-control::placeholder,
    .employer-page-shell .form-select option {
        color: rgba(180, 208, 228, 0.7);
    }

    .employer-page-shell .form-control:focus,
    .employer-page-shell .form-select:focus {
        border-color: rgba(105, 200, 234, 0.85);
        background: rgba(10, 23, 35, 0.95);
        box-shadow: 0 0 0 4px rgba(75, 202, 246, 0.08);
    }

    .employer-form-row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .employer-form-actions {
        display: flex;
        gap: 0.75rem;
        margin-top: 0.5rem;
    }

    .employer-page-shell .btn-primary {
        min-height: 42px;
        padding: 0.7rem 1.2rem;
        border: 0;
        border-radius: 10px;
        background: linear-gradient(135deg, #5ec9f5, #3c9bdf) !important;
        color: #ffffff !important;
        font-size: 0.9rem;
        font-weight: 700;
        box-shadow: 0 8px 18px rgba(44, 164, 222, 0.22);
    }

    .employer-page-shell .btn-outline-secondary {
        min-height: 42px;
        padding: 0.7rem 1.2rem;
        border-radius: 10px;
        border: 1px solid rgba(140, 167, 192, 0.22);
        color: rgba(224, 236, 246, 0.9);
        background: rgba(17, 30, 42, 0.65);
        font-weight: 700;
    }

    @media (max-width: 991.98px) {
        .employer-profile-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .employer-form-row {
            grid-template-columns: 1fr;
        }

        .employer-page-title {
            font-size: 2.5rem;
        }
    }
</style>
@endpush

@section('content')
<div class="employer-page-shell">
    <div class="employer-page-header">
        <h1 class="employer-page-title">Company Profile</h1>
        <p class="employer-page-subtitle">Update your company information and contact details</p>
    </div>

    <div class="employer-profile-layout">
        <div class="employer-profile-upload">
            <div class="upload-box">
                <div class="upload-logo">
                    @if($employer->logo)
                        <img src="{{ Storage::url($employer->logo) }}" alt="{{ $employer->company_name }}" style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 12px;">
                    @else
                        <i class="bi bi-building"></i>
                    @endif
                </div>

                <form method="POST" action="{{ route('employer.profile.update') }}" enctype="multipart/form-data" id="logoForm" class="d-none">
                    @csrf
                    @method('PUT')
                    <input type="file" name="company_logo" id="logoInput" accept="image/jpeg,image/jpg,image/png" onchange="document.getElementById('logoForm').submit();">
                </form>

                <button type="button" class="upload-btn" onclick="document.getElementById('logoInput').click();">
                    <i class="bi bi-cloud-upload"></i> {{ $employer->logo ? 'Change Logo' : 'Upload Logo' }}
                </button>
            </div>
        </div>

        <div class="employer-profile-panel">
            <div class="profile-panel-header">
                <i class="bi bi-building"></i>
                <span>Company Information</span>
            </div>

            <div class="profile-panel-body">
                <form method="POST" action="{{ route('employer.profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="employer-form-group">
                        <label class="employer-form-label">Company Name</label>
                        <input type="text" class="form-control @error('company_name') is-invalid @enderror" name="company_name" value="{{ old('company_name', $employer->company_name) }}" required>
                        @error('company_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="employer-form-row">
                        <div class="employer-form-group">
                            <label class="employer-form-label">Industry</label>
                            <input type="text" class="form-control @error('industry') is-invalid @enderror" name="industry" value="{{ old('industry', $employer->industry) }}" required>
                            @error('industry')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="employer-form-group">
                            <label class="employer-form-label">Company Size</label>
                            <select class="form-select @error('company_size') is-invalid @enderror" name="company_size">
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

                    <div class="employer-form-group">
                        <label class="employer-form-label">Website</label>
                        <input type="url" class="form-control @error('website') is-invalid @enderror" name="website" placeholder="https://www.example.com" value="{{ old('website', $employer->website) }}">
                        @error('website')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="employer-form-group">
                        <label class="employer-form-label">Company Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3" placeholder="Describe your company, mission, and culture">{{ old('description', $employer->description) }}</textarea>
                        @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="employer-form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Save Changes
                        </button>
                        <a href="{{ route('employer.dashboard') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
