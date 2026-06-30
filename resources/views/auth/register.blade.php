@extends('layouts.guest')

@section('title', 'Register')
@section('subtitle', 'Create your Skill-Bridge account')

@section('content')
<form method="POST" action="{{ route('register') }}" id="registerForm">
    @csrf
    <div class="mb-3">
        <label for="name" class="form-label">Full Name</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label for="phone" class="form-label">Phone <span class="text-muted">(optional)</span></label>
        <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}">
        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label for="role" class="form-label">Register As</label>
        <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
            <option value="">Select role...</option>
            @foreach($roles as $roleOption)
                <option value="{{ $roleOption->value }}" {{ old('role') === $roleOption->value ? 'selected' : '' }}>{{ $roleOption->label() }}</option>
            @endforeach
        </select>
        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3 role-field" data-role="student coordinator" style="display:none;">
        <label for="institution_id" class="form-label">Institution</label>
        <select class="form-select @error('institution_id') is-invalid @enderror" id="institution_id" name="institution_id">
            <option value="">Select institution...</option>
            @foreach($institutions as $institution)
                <option value="{{ $institution->id }}" {{ old('institution_id') == $institution->id ? 'selected' : '' }}>{{ $institution->name }}</option>
            @endforeach
        </select>
        @error('institution_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3 role-field" data-role="student" style="display:none;">
        <label for="program" class="form-label">Program</label>
        <input type="text" class="form-control @error('program') is-invalid @enderror" id="program" name="program" value="{{ old('program') }}">
        @error('program')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3 role-field" data-role="employer" style="display:none;">
        <label for="company_name" class="form-label">Company Name</label>
        <input type="text" class="form-control @error('company_name') is-invalid @enderror" id="company_name" name="company_name" value="{{ old('company_name') }}">
        @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label for="password_confirmation" class="form-label">Confirm Password</label>
        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
    </div>
    <button type="submit" class="btn btn-primary w-100 mb-3">Create Account</button>
    <div class="text-center">
        <a href="{{ route('login') }}" class="small text-decoration-none">Already have an account? Sign in</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
    const roleSelect = document.getElementById('role');
    const roleFields = document.querySelectorAll('.role-field');
    function toggleRoleFields() {
        const role = roleSelect.value;
        roleFields.forEach(el => {
            const roles = el.dataset.role.split(' ');
            el.style.display = roles.includes(role) ? 'block' : 'none';
        });
    }
    roleSelect.addEventListener('change', toggleRoleFields);
    toggleRoleFields();
</script>
@endpush
