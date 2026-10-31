@extends('layouts.guest')

@section('title', 'Welcome Back')

@section(
    'subtitle',
    'Sign in to continue your internship journey'
)

@section('body_class', 'auth-page')

@section('content')

<div class="auth-pane">

    @include('auth.partials.top', ['secure' => 'Secure Login'])

    <section class="auth-card" aria-labelledby="auth-title">

        @include('auth.partials.mobile-brand')

        <header class="auth-heading">
            <h1 id="auth-title">
                Welcome back.
                <span>Continue your journey.</span>
            </h1>
            <p>Sign in to your SkillBridge account to continue your journey.</p>
        </header>

        @if (session('status'))
            <div class="auth-alert auth-alert--success" role="status">
                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if (
            $errors->any()
            &&
            !$errors->has('login')
            &&
            !$errors->has('password')
        )
            <div class="auth-alert auth-alert--danger" role="alert">
                <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('login') }}"
            id="loginForm"
            novalidate
            data-auth-form
        >
            @csrf

            {{-- LOGIN --}}
            <div class="auth-field">
                <label for="login" class="auth-label">
                    Email or School ID
                    <span class="auth-required" aria-hidden="true">*</span>
                </label>

                <div class="auth-input-wrap">
                    <i class="bi bi-person auth-input-icon" aria-hidden="true"></i>
                    <input
                        type="text"
                        id="login"
                        name="login"
                        class="auth-input @error('login') is-invalid @enderror"
                        value="{{ old('login') }}"
                        placeholder="Enter your email address or school ID"
                        autocomplete="username"
                        maxlength="255"
                        aria-invalid="{{ $errors->has('login') ? 'true' : 'false' }}"
                        aria-describedby="loginError @if ($errors->has('login')) loginServerError @endif"
                        required
                    >
                </div>

                @error('login')
                    <div id="loginServerError" class="auth-error server-error" role="alert">
                        <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror

                <div id="loginError" class="auth-error" aria-live="polite"></div>
            </div>

            {{-- PASSWORD --}}
            <div class="auth-field">
                <label for="password" class="auth-label">
                    Password
                    <span class="auth-required" aria-hidden="true">*</span>
                </label>

                <div class="auth-input-wrap auth-input-wrap--password">
                    <i class="bi bi-lock auth-input-icon" aria-hidden="true"></i>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="auth-input @error('password') is-invalid @enderror"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        maxlength="255"
                        aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                        aria-describedby="passwordError @if ($errors->has('password')) passwordServerError @endif"
                        required
                    >
                    <button
                        type="button"
                        class="auth-toggle"
                        id="passwordToggle"
                        data-password-toggle="password"
                        aria-controls="password"
                        aria-label="Show password"
                        aria-pressed="false"
                        title="Show password"
                    >
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                </div>

                @error('password')
                    <div id="passwordServerError" class="auth-error server-error" role="alert">
                        <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror

                <div id="passwordError" class="auth-error" aria-live="polite"></div>
            </div>

            {{-- REMEMBER + FORGOT --}}
            <div class="auth-options">
                <label class="auth-check" for="remember">
                    <input
                        type="checkbox"
                        id="remember"
                        name="remember"
                        value="1"
                        {{ old('remember') ? 'checked' : '' }}
                    >
                    Remember me
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="auth-link">
                        Forgot password?
                    </a>
                @endif
            </div>

            <button
                type="submit"
                class="auth-btn"
                id="loginButton"
                data-loading-text="Signing in..."
            >
                <span class="auth-btn-label">Sign In</span>
            </button>
        </form>

        <p class="auth-footer-text">
            Don't have a SkillBridge account?
            Contact your coordinator or administrator.
        </p>

    </section>

</div>

@endsection


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const form = document.getElementById('loginForm');
    if (!form) {
        return;
    }

    const emailInput = document.getElementById('login');
    const passwordInput = document.getElementById('password');
    const emailError = document.getElementById('loginError');
    const passwordError = document.getElementById('passwordError');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    let hasSubmitted = false;

    function showError(input, errorElement, message) {
        input.classList.add('is-invalid');
        input.setAttribute('aria-invalid', 'true');
        errorElement.textContent = message;
    }

    function clearError(input, errorElement) {
        input.classList.remove('is-invalid');
        input.setAttribute('aria-invalid', 'false');
        errorElement.textContent = '';
    }

    function validateEmail() {
        const login = emailInput.value.trim();
        clearError(emailInput, emailError);

        if (login === '') {
            showError(emailInput, emailError, 'Email address or school ID is required.');
            return false;
        }

        if (login.length > 255) {
            showError(emailInput, emailError, 'This is too long.');
            return false;
        }

        return true;
    }

    function validatePassword() {
        const password = passwordInput.value;
        clearError(passwordInput, passwordError);

        if (password === '') {
            showError(passwordInput, passwordError, 'Password is required.');
            return false;
        }

        if (password.trim() === '') {
            showError(passwordInput, passwordError, 'Password cannot contain only spaces.');
            return false;
        }

        return true;
    }

    function hideServerError(input) {
        const serverError = input.closest('.auth-field').querySelector('.server-error');
        if (serverError) {
            serverError.style.display = 'none';
        }
    }

    emailInput.addEventListener('blur', function () {
        if (hasSubmitted || emailInput.value.trim() !== '') {
            validateEmail();
        }
    });

    emailInput.addEventListener('input', function () {
        clearError(emailInput, emailError);
        hideServerError(emailInput);
    });

    passwordInput.addEventListener('blur', function () {
        if (hasSubmitted || passwordInput.value !== '') {
            validatePassword();
        }
    });

    passwordInput.addEventListener('input', function () {
        clearError(passwordInput, passwordError);
        hideServerError(passwordInput);
    });

    // Invalid input stops here; a valid form posts normally and the shared
    // auth-form handler (skillbridge-ui.js) shows the loading state.
    form.addEventListener('submit', function (event) {
        hasSubmitted = true;

        const emailValid = validateEmail();
        const passwordValid = validatePassword();

        if (emailValid && passwordValid) {
            return;
        }

        event.preventDefault();

        const firstInvalid = emailValid ? passwordInput : emailInput;
        firstInvalid.focus();
        firstInvalid.scrollIntoView({
            behavior: reduceMotion ? 'auto' : 'smooth',
            block: 'center'
        });
    });
});
</script>
@endpush
