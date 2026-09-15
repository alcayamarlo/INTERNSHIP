@extends('layouts.app')

@section('title', 'Edit Company Profile')

@push('styles')
<style>
    .employer-page-shell { padding: 8px 0; color: #edf8ff; }
    .employer-page-title { margin: 0 0 8px; color: #f4f9ff; font-size: clamp(2.2rem,2.5vw,3.3rem); font-weight: 800; letter-spacing: -.05em; }
    .employer-page-subtitle { margin: 0; color: rgba(186,211,228,.82); font-size: 1.06rem; }
    .employer-page-shell .card { overflow: hidden; border: 1px solid rgba(117,176,215,.18); border-radius: 18px; background: linear-gradient(145deg,rgba(13,35,54,.96),rgba(8,22,37,.96)); box-shadow: 0 10px 28px rgba(2,9,20,.18); }
    .employer-page-shell .card:hover { border-color: rgba(49,217,244,.36); }
    .employer-page-shell .card-header { padding: 1rem 1.1rem; border-bottom: 1px solid rgba(117,176,215,.14); background: rgba(14,35,51,.9) !important; color: #edf8ff; }
    .employer-page-shell .card-header h5 { margin: 0; color: #edf8ff; font-size: .92rem; font-weight: 750; }
    .employer-page-shell .card-header h5 i { margin-right: 7px; color: #31d9f4; }
    .employer-page-shell .card-body { padding: 1.25rem; }
    .employer-page-shell .form-control, .employer-page-shell .form-select { min-height: 43px; border: 1px solid rgba(117,176,215,.2); border-radius: 10px; background: rgba(3,17,31,.68); color: #f8fafc !important; }
    .employer-page-shell textarea.form-control { min-height: 110px; }
    .employer-page-shell .form-control:focus, .employer-page-shell .form-select:focus { border-color: #08d9f5; background: rgba(3,17,31,.88); box-shadow: 0 0 0 4px rgba(8,217,245,.08); }
    .employer-page-shell .form-label { color: #dcebf5; font-size: .72rem; font-weight: 750; letter-spacing: .04em; }
    .employer-page-shell .bg-light { background: #102a40 !important; color: #edf8ff !important; }
    .employer-page-shell .btn-primary { border: 0; background: linear-gradient(135deg,#29d4ff,#25c7ff) !important; color: #062338 !important; font-weight: 700; }
    .employer-page-shell .btn-outline-primary { border-color: rgba(77,210,255,.55); color: #7fe0ff !important; }
    .employer-page-shell .btn-outline-secondary { border-color: rgba(117,176,215,.25); color: #c2d8e8; }
</style>
@endpush

@section('content')
<div class="employer-page-shell">
<div class="mb-4">
    <h1 class="employer-page-title">Company Profile</h1>
    <p class="employer-page-subtitle">Update your company information and contact details</p>
</div>

<div class="row g-4">
    <!-- Company Logo Card -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="mb-3">
                    @if($employer->logo)
                        <img src="{{ Storage::url($employer->logo) }}" alt="{{ $employer->company_name }}" class="rounded" style="max-width: 150px; max-height: 150px; object-fit: contain;">
                    @else
                        <div class="bg-light rounded d-inline-flex align-items-center justify-content-center" style="width: 150px; height: 150px;">
                            <i class="bi bi-building fs-1 text-muted"></i>
                        </div>
                    @endif
                </div>

                <form method="POST" action="{{ route('employer.profile.update') }}" enctype="multipart/form-data" id="logoForm" class="d-none">
                    @csrf
                    @method('PUT')
                    <input type="file" name="company_logo" id="logoInput" accept="image/jpeg,image/jpg,image/png" onchange="document.getElementById('logoForm').submit();">
                </form>

                <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('logoInput').click();">
                    <i class="bi bi-cloud-upload"></i> {{ $employer->logo ? 'Change Logo' : 'Upload Logo' }}
                </button>

                @if($employer->logo)
                    <form method="POST" action="{{ route('employer.profile.delete-logo') }}" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove company logo?');">
                            <i class="bi bi-trash"></i> Remove
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <!-- Profile Form -->
    <div class="col-lg-8">
        <form method="POST" action="{{ route('employer.profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- Company Information -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-building"></i> Company Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Company Name</label>
                        <input type="text" class="form-control @error('company_name') is-invalid @enderror" name="company_name" value="{{ old('company_name', $employer->company_name) }}" required>
                        @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Industry</label>
                            <input type="text" class="form-control @error('industry') is-invalid @enderror" name="industry" value="{{ old('industry', $employer->industry) }}" required>
                            @error('industry')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company Size</label>
                            <select class="form-select @error('company_size') is-invalid @enderror" name="company_size">
                                <option value="">Select...</option>
                                <option value="1-10" {{ old('company_size', $employerInfo['company_size'] ?? '') === '1-10' ? 'selected' : '' }}>1-10 employees</option>
                                <option value="11-50" {{ old('company_size', $employerInfo['company_size'] ?? '') === '11-50' ? 'selected' : '' }}>11-50 employees</option>
                                <option value="51-200" {{ old('company_size', $employerInfo['company_size'] ?? '') === '51-200' ? 'selected' : '' }}>51-200 employees</option>
                                <option value="201-500" {{ old('company_size', $employerInfo['company_size'] ?? '') === '201-500' ? 'selected' : '' }}>201-500 employees</option>
                                <option value="500+" {{ old('company_size', $employerInfo['company_size'] ?? '') === '500+' ? 'selected' : '' }}>500+ employees</option>
                            </select>
                            @error('company_size')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3 mt-0">
                        <div class="col-md-6">
                            <label class="form-label">Website</label>
                            <input type="url" class="form-control @error('website') is-invalid @enderror" name="website" placeholder="https://www.example.com" value="{{ old('website', $employer->website) }}">
                            @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Company Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3" placeholder="Describe your company, mission, and culture">{{ old('description', $employer->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-telephone"></i> Contact Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Contact Person Name</label>
                            <input type="text" class="form-control @error('contact_person') is-invalid @enderror" name="contact_person" value="{{ old('contact_person', $employer->contact_person) }}" required>
                            @error('contact_person')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Position</label>
                            <input type="text" class="form-control @error('position') is-invalid @enderror" name="position" placeholder="HR Manager, Recruiter, etc." value="{{ old('position', $employerInfo['position'] ?? '') }}" required>
                            @error('position')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3 mt-0">
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile Number</label>
                            <input type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" placeholder="+63 900 000 0000" value="{{ old('phone', $user->phone) }}" required>
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Business Address -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-geo-alt"></i> Business Address</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Street Address</label>
                        <input type="text" class="form-control @error('street') is-invalid @enderror" name="street" placeholder="Street address" value="{{ old('street', $address['street'] ?? '') }}" required>
                        @error('street')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control @error('city') is-invalid @enderror" name="city" value="{{ old('city', $address['city'] ?? '') }}" required>
                            @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Province</label>
                            <input type="text" class="form-control @error('province') is-invalid @enderror" name="province" value="{{ old('province', $address['province'] ?? '') }}" required>
                            @error('province')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">ZIP Code</label>
                            <input type="text" class="form-control @error('zip_code') is-invalid @enderror" name="zip_code" value="{{ old('zip_code', $address['zip_code'] ?? '') }}" required>
                            @error('zip_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="d-flex gap-2">
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
@endsection
