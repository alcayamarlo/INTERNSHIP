@extends('layouts.app')

@section('title', 'Create User')

@section('content')
<x-page-header title="Create User" subtitle="Create an employer, coordinator, or student account">
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Users</a>
</x-page-header>

<div class="card" style="max-width: 900px;">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.users.store') }}" class="row g-3">
            @csrf

            <div class="col-md-6">
                <label for="name" class="form-label">Full Name <span class="text-danger" aria-hidden="true">*</span></label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="email" class="form-label">Email Address <span class="text-danger" aria-hidden="true">*</span></label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="role" class="form-label">Account Type <span class="text-danger" aria-hidden="true">*</span></label>
                <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" required>
                    <option value="">Select account type</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->value }}" {{ old('role') === $role->value ? 'selected' : '' }}>{{ $role->label() }}</option>
                    @endforeach
                </select>
                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="phone" class="form-label">Phone Number <span class="text-muted fw-normal">(optional)</span></label>
                <input id="phone" name="phone" type="text" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror">
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6" id="company-field">
                <label for="company_name" class="form-label">Company Name</label>
                <input id="company_name" name="company_name" type="text" value="{{ old('company_name') }}" class="form-control @error('company_name') is-invalid @enderror">
                @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6" id="institution-field">
                <label for="institution_id" class="form-label">Institution</label>
                <select id="institution_id" name="institution_id" class="form-select @error('institution_id') is-invalid @enderror">
                    <option value="">Select institution</option>
                    @foreach($institutions as $institution)
                        <option value="{{ $institution->id }}" {{ old('institution_id') == $institution->id ? 'selected' : '' }}>{{ $institution->name }}</option>
                    @endforeach
                </select>
                @error('institution_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <hr>
            </div>

            <div class="col-md-6">
                <label for="password" class="form-label">Temporary Password <span class="text-danger" aria-hidden="true">*</span></label>
                <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" required>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger" aria-hidden="true">*</span></label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary"><i class="bi bi-person-plus" aria-hidden="true"></i> Create Account</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const role = document.getElementById('role');
    const companyField = document.getElementById('company-field');
    const institutionField = document.getElementById('institution-field');
    const company = document.getElementById('company_name');
    const institution = document.getElementById('institution_id');

    function updateFields() {
        const isEmployer = role.value === 'employer';
        const needsInstitution = role.value === 'coordinator' || role.value === 'student';
        companyField.hidden = !isEmployer;
        institutionField.hidden = isEmployer;
        company.required = isEmployer;
        institution.required = needsInstitution;
    }

    role.addEventListener('change', updateFields);
    updateFields();
});
</script>
@endpush
