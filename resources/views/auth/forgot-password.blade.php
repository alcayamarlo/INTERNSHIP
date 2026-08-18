@extends('layouts.guest')

@section('title', 'Reset Password')
@section('subtitle', 'We\'ll help you regain access to your account')

@section('content')
<p style="font-size: 0.95rem; color: var(--text-secondary); margin-bottom: 1.5rem; line-height: 1.6;">
    <i class="bi bi-info-circle" style="color: var(--cyan); margin-right: 0.5rem;"></i>
    Enter your email address and we'll send you a password reset link.
</p>

<form method="POST" action="{{ route('password.email') }}" id="forgotForm" novalidate>
    @csrf

    <!-- Email Field --
    <div class="form-group">
        <label for="email" class="form-label">
            <i class="bi bi-envelope me-1"></i>Email Address
        </label>
        <input 
            type="email" 
            class="form-control @error('email') is-invalid @enderror" 
            id="email" 
            name="email" 
            value="{{ old('email') }}" 
            placeholder="your.email@example.com"
            required 
            autofocus
        >
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <!-- Submit Button --
    <button type="submit" class="btn-login">
        <span>Send Reset Link</span>
        <span class="btn-login-loader">
            <span class="spinner-border spinner-border-sm text-white" role="status">
                <span class="visually-hidden">Loading...</span>
            </span>
        </span>
    </button>

    <!-- Back to Login Link --
    <div class="auth-footer">
        <a href="{{ route('login') }}" style="display: inline-flex; align-items: center; gap: 0.5rem;">
            <i class="bi bi-arrow-left"></i>Back to login
        </a>
    </div>
</form>

@push('scripts')
<script>
    const form = document.getElementById('forgotForm');
    const emailInput = document.getElementById('email');
    const submitBtn = form.querySelector('button[type="submit"]');

    // Email validation
    emailInput.addEventListener('blur', function() {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (this.value.trim() === '') {
            this.classList.add('is-invalid');
        } else if (!emailRegex.test(this.value)) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
        }
    });

    emailInput.addEventListener('input', function() {
        if (this.classList.contains('is-invalid')) {
            this.classList.remove('is-invalid');
        }
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (emailInput.value.trim() === '') {
            emailInput.classList.add('is-invalid');
            return;
        } else if (!emailRegex.test(emailInput.value)) {
            emailInput.classList.add('is-invalid');
            return;
        }
        
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
        form.submit();
    });
</script>
@endpush
