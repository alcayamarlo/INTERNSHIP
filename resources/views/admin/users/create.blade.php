@extends('layouts.app')

@section('title', 'Create User')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Create User</h1>
        <p class="text-muted mb-0">Create an employer or coordinator account</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Back to Users</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.users.store') }}" class="row g-3">
            @csrf

            <div class="col-md-6">
                <label for="name" class="form-label">Full Name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="email" class="form-label">Email Address</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="role" class="form-label">Account Type</label>
                <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" required>
                    <option value="">Select account type</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->value }}" {{ old('role') === $role->value ? 'selected' : '' }}>{{ $role->label() }}</option>
                    @endforeach
                </select>
                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="phone" class="form-label">Phone Number <span class="text-muted">(optional)</span></label>
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
                <label for="password" class="form-label">Temporary Password</label>
                <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" required>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="password_confirmation" class="form-label">Confirm Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Create Account</button>
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
        companyField.hidden = !isEmployer;
        institutionField.hidden = isEmployer;
        company.required = isEmployer;
        institution.required = !isEmployer;
    }

    role.addEventListener('change', updateFields);
    updateFields();
});
</script>
@endpush
