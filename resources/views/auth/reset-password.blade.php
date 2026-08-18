@extends('layouts.guest')

@section('title', 'Create New Password')
@section('subtitle', 'Set a new password for your account')

@section('content')
<form method="POST" action="{{ route('password.update') }}" id="resetForm" novalidate>
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <!-- Email Field (Read-only) --
    <div class="form-group">
        <label for="email" class="form-label">
            <i class="bi bi-envelope me-1"></i>Email Address
        </label>
        <input 
            type="email" 
            class="form-control @error('email') is-invalid @enderror" 
            id="email" 
            name="email" 
            value="{{ old('email', $email) }}" 
            placeholder="your.email@example.com"
            required 
            autofocus
        >
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <!-- New Password Field --
    <div class="form-group">
        <label for="password" class="form-label">
            <i class="bi bi-lock me-1"></i>New Password
        </label>
        <div class="password-wrapper">
            <input 
                type="password" 
                class="form-control @error('password') is-invalid @enderror" 
                id="password" 
                name="password" 
                placeholder="At least 8 characters"
                required
            >
            <button type="button" class="password-toggle" title="Toggle password visibility">
                <i class="bi bi-eye"></i>
            </button>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <!-- Confirm Password Field --
    <div class="form-group">
        <label for="password_confirmation" class="form-label">
            <i class="bi bi-lock-check me-1"></i>Confirm Password
        </label>
        <div class="password-wrapper">
            <input 
                type="password" 
                class="form-control" 
                id="password_confirmation" 
                name="password_confirmation" 
                placeholder="Re-enter your password"
                required
            >
            <button type="button" class="password-toggle" title="Toggle password visibility">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <!-- Submit Button --
    <button type="submit" class="btn-login" style="margin-top: 1.5rem;">
        <span>Reset Password</span>
        <span class="btn-login-loader">
            <span class="spinner-border spinner-border-sm text-white" role="status">
                <span class="visually-hidden">Loading...</span>
            </span>
        </span>
    </button>

    <!-- Back to Login Link --
    <div class="auth-footer">
        <a href="{{ route('login') }}">Back to login</a>
    </div>
</form>

@push('scripts')
<script>
    const form = document.getElementById('resetForm');
    const passwordInput = document.getElementById('password');
    const passwordConfirmInput = document.getElementById('password_confirmation');
    const submitBtn = form.querySelector('button[type="submit"]');

    // Real-time validation
    passwordInput.addEventListener('blur', function() {
        if (this.value.length < 8) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
        }
    });

    passwordConfirmInput.addEventListener('blur', function() {
        if (this.value !== passwordInput.value) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
        }
    });

    passwordInput.addEventListener('input', function() {
        if (this.classList.contains('is-invalid')) {
            this.classList.remove('is-invalid');
        }
    });

    passwordConfirmInput.addEventListener('input', function() {
        if (this.classList.contains('is-invalid')) {
            this.classList.remove('is-invalid');
        }
    });

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        let isValid = true;
        
        // Validate password
        if (passwordInput.value.length < 8) {
            passwordInput.classList.add('is-invalid');
            isValid = false;
        }
        
        // Validate confirmation
        if (passwordConfirmInput.value !== passwordInput.value) {
            passwordConfirmInput.classList.add('is-invalid');
            isValid = false;
        }
        
        if (isValid) {
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            form.submit();
        }
    });
</script>
@endpush
