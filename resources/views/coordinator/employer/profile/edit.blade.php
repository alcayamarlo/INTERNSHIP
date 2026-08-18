@extends('layouts.app')

@section('title', 'Edit Company Profile')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-1">Company Profile</h1>
    <p class="text-muted mb-0">Update your company information and contact details</p>
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
@endsection
