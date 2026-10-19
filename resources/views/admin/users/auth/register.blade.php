@extends('layouts.guest')

@section('title', 'Create Account')
@section('subtitle', 'Join Skill Bridge today')
@section('body_class', 'auth-page')

@section('content')

@php
    // Errors for fields that have no input of their own on this form.
    $fieldKeys = ['name', 'phone', 'email', 'password', 'institution_id', 'program_id', 'program'];
    $otherErrorKey = collect($errors->keys())->diff($fieldKeys)->first();
@endphp

<div class="auth-pane auth-pane--wide">

    @include('admin.users.auth.partials.top', ['secure' => 'Secure Registration'])

    <section class="auth-card" aria-labelledby="auth-title">

        @include('admin.users.auth.partials.mobile-brand')

        <header class="auth-heading">
            <h1 id="auth-title">
                Create an account.
                <span>Start your journey.</span>
            </h1>
            <p>Create your SkillBridge student account and start building your future.</p>
        </header>

        @if ($otherErrorKey)
            <div class="auth-alert auth-alert--danger" role="alert">
                <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                <span>{{ $errors->first($otherErrorKey) }}</span>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('register') }}"
            id="registerForm"
            novalidate
            data-auth-form
        >
            @csrf

            {{-- ACCOUNT TYPE + INSTITUTION --}}
            <div class="auth-chip" aria-label="Selected account type">
                <i class="bi bi-mortarboard-fill" aria-hidden="true"></i>
                <span>Student account</span>
                <span class="auth-chip-note">For {{ $institution->name }} students</span>
            </div>

            <input
                type="hidden"
                id="institution_id"
                name="institution_id"
                value="{{ $institution->id }}"
            >

            @error('institution_id')
                <div class="auth-error" role="alert">
                    <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                    <span>{{ $message }}</span>
                </div>
            @enderror

            <div id="institutionError" class="auth-error js-error" role="alert"></div>

            <div class="auth-grid">

                {{-- NAME --}}
                <div class="auth-field auth-field--full">
                    <label for="name" class="auth-label">
                        Full Name
                        <span class="auth-required" aria-hidden="true">*</span>
                    </label>

                    <div class="auth-input-wrap">
                        <i class="bi bi-person auth-input-icon" aria-hidden="true"></i>
                        <input
                            type="text"
                            class="auth-input @error('name') is-invalid @enderror"
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="Enter your full name"
                            maxlength="100"
                            autocomplete="name"
                            aria-describedby="nameError"
                            required
                            autofocus
                        >
                    </div>

                    @error('name')
                        <div class="auth-error server-error" role="alert">
                            <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror

                    <div id="nameError" class="auth-error js-error"></div>
                </div>

                {{-- EMAIL --}}
                <div class="auth-field">
                    <label for="email" class="auth-label">
                        Email Address
                        <span class="auth-required" aria-hidden="true">*</span>
                    </label>

                    <div class="auth-input-wrap">
                        <i class="bi bi-envelope auth-input-icon" aria-hidden="true"></i>
                        <input
                            type="email"
                            class="auth-input @error('email') is-invalid @enderror"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="you@example.com"
                            maxlength="255"
                            autocomplete="email"
                            aria-describedby="emailError"
                            required
                        >
                    </div>

                    @error('email')
                        <div class="auth-error server-error" role="alert">
                            <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror

                    <div id="emailError" class="auth-error js-error"></div>
                </div>

                {{-- PHONE --}}
                <div class="auth-field">
                    <label for="phone" class="auth-label">
                        Phone Number
                        <span class="auth-optional">Optional</span>
                    </label>

                    <div class="auth-input-wrap">
                        <i class="bi bi-telephone auth-input-icon" aria-hidden="true"></i>
                        <input
                            type="tel"
                            class="auth-input @error('phone') is-invalid @enderror"
                            id="phone"
                            name="phone"
                            value="{{ old('phone') }}"
                            placeholder="+63 912 345 6789"
                            maxlength="20"
                            autocomplete="tel"
                            aria-describedby="phoneError"
                        >
                    </div>

                    @error('phone')
                        <div class="auth-error server-error" role="alert">
                            <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror

                    <div id="phoneError" class="auth-error js-error"></div>
                </div>

                {{-- PROFILE DETAILS (shown per role by the script below) --}}
                <div id="roleDetails" class="auth-field--full">

                    {{-- PROGRAM --}}
                    <div class="auth-field role-field show" data-role="student">
                        <label for="program" class="auth-label">
                            Program
                            <span class="auth-required" aria-hidden="true">*</span>
                        </label>

                        <div class="auth-input-wrap">
                            <i class="bi bi-book auth-input-icon" aria-hidden="true"></i>
                            <select
                                class="auth-input @if ($errors->has('program_id') || $errors->has('program')) is-invalid @endif"
                                id="program"
                                name="program_id"
                                aria-describedby="programError"
                                required
                            >
                                <option value="">Select your program</option>
                                @foreach($programs as $program)
                                    <option value="{{ $program->id }}" {{ (string) old('program_id') === (string) $program->id ? 'selected' : '' }}>{{ $program->display_name }}</option>
                                @endforeach
                            </select>
                            <i class="bi bi-chevron-down auth-select-caret" aria-hidden="true"></i>
                        </div>

                        @error('program_id')
                            <div class="auth-error server-error" role="alert">
                                <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror

                        @error('program')
                            <div class="auth-error server-error" role="alert">
                                <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror

                        <div id="programError" class="auth-error js-error"></div>
                    </div>

                    {{-- COMPANY --}}
                    <div class="auth-field role-field" data-role="employer">
                        <label for="company_name" class="auth-label">
                            Company Name
                            <span class="auth-required" aria-hidden="true">*</span>
                        </label>

                        <div class="auth-input-wrap">
                            <i class="bi bi-buildings auth-input-icon" aria-hidden="true"></i>
                            <input
                                type="text"
                                class="auth-input @error('company_name') is-invalid @enderror"
                                id="company_name"
                                name="company_name"
                                value="{{ old('company_name') }}"
                                placeholder="Enter company name"
                                maxlength="150"
                                aria-describedby="companyError"
                            >
                        </div>

                        @error('company_name')
                            <div class="auth-error server-error" role="alert">
                                <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror

                        <div id="companyError" class="auth-error js-error"></div>
                    </div>

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
                            class="auth-input @error('password') is-invalid @enderror"
                            id="password"
                            name="password"
                            placeholder="Create a password"
                            maxlength="128"
                            autocomplete="new-password"
                            aria-describedby="passwordError passwordRules"
                            required
                        >
                        <button
                            type="button"
                            class="auth-toggle"
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
                        <div class="auth-error server-error" role="alert">
                            <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror

                    <div id="passwordError" class="auth-error js-error"></div>
                </div>

                {{-- CONFIRM PASSWORD --}}
                <div class="auth-field">
                    <label for="password_confirmation" class="auth-label">
                        Confirm Password
                        <span class="auth-required" aria-hidden="true">*</span>
                    </label>

                    <div class="auth-input-wrap auth-input-wrap--password">
                        <i class="bi bi-lock auth-input-icon" aria-hidden="true"></i>
                        <input
                            type="password"
                            class="auth-input"
                            id="password_confirmation"
                            name="password_confirmation"
                            placeholder="Repeat password"
                            maxlength="128"
                            autocomplete="new-password"
                            aria-describedby="passwordConfirmationError"
                            required
                        >
                        <button
                            type="button"
                            class="auth-toggle"
                            data-password-toggle="password_confirmation"
                            aria-controls="password_confirmation"
                            aria-label="Show password"
                            aria-pressed="false"
                            title="Show password"
                        >
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div id="passwordConfirmationError" class="auth-error js-error"></div>
                </div>

                <div class="auth-pw-meta" id="passwordRules">
                    <div class="password-rules">
                        <span id="ruleLength" class="password-rule">
                            <i class="bi bi-circle-fill" aria-hidden="true"></i>
                            8+ characters
                        </span>

                        <span id="ruleUpper" class="password-rule">
                            <i class="bi bi-circle-fill" aria-hidden="true"></i>
                            One uppercase letter
                        </span>
                    </div>

                    <div class="password-strength">
                        <div id="passwordStrengthBar" class="password-strength-bar"></div>
                    </div>

                    <div id="passwordStrengthText" class="password-strength-text" aria-live="polite"></div>
                </div>

            </div>

            {{-- TERMS --}}
            <div class="auth-terms" id="termsWrapper">
                <label class="auth-terms-label" for="terms">
                    <input
                        type="checkbox"
                        id="terms"
                        name="terms"
                        value="1"
                        aria-describedby="termsError"
                        {{ old('terms') ? 'checked' : '' }}
                    >

                    <span>
                        I agree to the
                        <a href="#">Terms and Conditions</a>
                        and
                        <a href="#">Privacy Policy</a>.
                        I understand that my information will be used for
                        Skill Bridge internship and career services.
                    </span>
                </label>

                <div id="termsError" class="auth-error auth-terms-error" role="alert">
                    Please agree to the Terms and Conditions and Privacy Policy.
                </div>
            </div>

            <button
                type="submit"
                class="auth-btn auth-btn--spaced"
                id="submitBtn"
                data-loading-text="Creating account..."
            >
                <i class="bi bi-person-check-fill" aria-hidden="true"></i>
                <span class="auth-btn-label" id="submitText">Create Account</span>
            </button>
        </form>

        <p class="auth-footer-text">
            Already have a SkillBridge account?
            <a href="{{ route('login') }}">Sign in here</a>
        </p>

    </section>

</div>

@endsection


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const form = document.getElementById('registerForm');
    const roleCards = document.querySelectorAll('input[name="role"]');
    const roleDetails = document.getElementById('roleDetails');
    const roleFields = document.querySelectorAll('.role-field');
    const institution = document.getElementById('institution_id');
    const program = document.getElementById('program');
    const company = document.getElementById('company_name');
    const password = document.getElementById('password');
    const passwordConfirmation = document.getElementById('password_confirmation');
    const terms = document.getElementById('terms');
    const termsWrapper = document.getElementById('termsWrapper');
    const termsError = document.getElementById('termsError');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------- Role fields ---------- */

    function getSelectedRole() {
        const checked = document.querySelector('input[name="role"]:checked');

        if (checked) {
            return checked.value;
        }

        return roleCards.length === 0 ? 'student' : '';
    }

    function updateRoleFields() {
        const selectedRole = getSelectedRole();
        let visible = false;

        roleFields.forEach(function (field) {
            const roles = field.dataset.role.trim().split(/\s+/);
            const show = roles.includes(selectedRole);

            field.classList.toggle('show', show);
            visible = visible || show;
        });

        roleDetails.style.display = visible ? 'block' : 'none';

        if (institution) {
            institution.required = selectedRole === 'student' || selectedRole === 'coordinator';
        }

        if (program) {
            program.required = selectedRole === 'student';
        }

        if (company) {
            company.required = selectedRole === 'employer';
        }
    }

    roleCards.forEach(function (radio) {
        radio.addEventListener('change', function () {
            updateRoleFields();
            clearError('roleError');
        });
    });

    updateRoleFields();

    /* ---------- Password strength ---------- */

    function setRule(id, valid) {
        const element = document.getElementById(id);

        if (!element) {
            return;
        }

        const icon = element.querySelector('i');

        element.classList.toggle('valid', valid);

        if (icon) {
            icon.className = valid ? 'bi bi-check-circle-fill' : 'bi bi-circle-fill';
        }
    }

    function updatePasswordStrength() {
        const value = password.value;
        const hasLength = value.length >= 8;
        const hasUpper = /[A-Z]/.test(value);
        const strength = document.querySelector('.password-strength');
        const bar = document.getElementById('passwordStrengthBar');
        const text = document.getElementById('passwordStrengthText');

        setRule('ruleLength', hasLength);
        setRule('ruleUpper', hasUpper);

        if (value.length === 0) {
            strength.classList.remove('show');
            bar.className = 'password-strength-bar';
            text.textContent = '';
            return;
        }

        strength.classList.add('show');
        bar.className = 'password-strength-bar';

        if (hasLength && hasUpper) {
            bar.classList.add('strong');
            text.textContent = 'Password meets the requirements';
        } else {
            bar.classList.add('weak');
            text.textContent = 'Weak password';
        }
    }

    password.addEventListener('input', function () {
        updatePasswordStrength();
        clearFieldError(password, 'passwordError');
    });

    updatePasswordStrength();

    /* ---------- Errors ---------- */

    function showError(input, errorId, message) {
        const error = document.getElementById(errorId);

        if (input) {
            input.classList.add('is-invalid');
            input.setAttribute('aria-invalid', 'true');
        }

        if (error) {
            error.textContent = message;
            error.classList.add('show');
        }
    }

    function clearError(errorId) {
        const error = document.getElementById(errorId);

        if (error) {
            error.textContent = '';
            error.classList.remove('show');
        }
    }

    function clearFieldError(input, errorId) {
        if (input) {
            input.classList.remove('is-invalid');
            input.removeAttribute('aria-invalid');
        }

        clearError(errorId);
    }

    function clearAllErrors() {
        form.querySelectorAll('.js-error').forEach(function (error) {
            error.textContent = '';
            error.classList.remove('show');
        });

        form.querySelectorAll('.is-invalid').forEach(function (input) {
            input.classList.remove('is-invalid');
            input.removeAttribute('aria-invalid');
        });

        form.querySelectorAll('.server-error').forEach(function (error) {
            error.remove();
        });

        termsWrapper.classList.remove('invalid');
        termsError.classList.remove('show');
    }

    /* ---------- Validation ---------- */

    function validateForm() {
        let valid = true;

        const name = document.getElementById('name');
        const nameValue = name.value.trim();

        if (nameValue === '') {
            showError(name, 'nameError', 'Full name is required.');
            valid = false;
        } else if (nameValue.length < 2) {
            showError(name, 'nameError', 'Full name must contain at least 2 characters.');
            valid = false;
        } else if (!/^[A-Za-zÀ-ÿ\s.'-]+$/.test(nameValue)) {
            showError(name, 'nameError', 'Please enter a valid full name.');
            valid = false;
        }

        const phone = document.getElementById('phone');
        const phoneValue = phone.value.trim();

        if (phoneValue !== '' && !/^\d{10,15}$/.test(phoneValue.replace(/[\s()+-]/g, ''))) {
            showError(phone, 'phoneError', 'Please enter a valid phone number.');
            valid = false;
        }

        const email = document.getElementById('email');
        const emailValue = email.value.trim();

        if (emailValue === '') {
            showError(email, 'emailError', 'Email address is required.');
            valid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(emailValue)) {
            showError(email, 'emailError', 'Please enter a valid email address.');
            valid = false;
        }

        const selectedRole = getSelectedRole();

        if (selectedRole === '') {
            const roleError = document.getElementById('roleError');

            if (roleError) {
                roleError.textContent = 'Please select your account type.';
                roleError.classList.add('show');
            }

            valid = false;
        }

        if ((selectedRole === 'student' || selectedRole === 'coordinator') && institution.value === '') {
            showError(institution, 'institutionError', 'Please select your institution.');
            valid = false;
        }

        if (selectedRole === 'student' && program.value.trim() === '') {
            showError(program, 'programError', 'Please select your program.');
            valid = false;
        }

        if (selectedRole === 'employer') {
            const companyValue = company.value.trim();

            if (companyValue === '') {
                showError(company, 'companyError', 'Please enter your company name.');
                valid = false;
            } else if (companyValue.length < 2) {
                showError(company, 'companyError', 'Please enter a valid company name.');
                valid = false;
            }
        }

        if (password.value.length < 8 || !/[A-Z]/.test(password.value)) {
            showError(password, 'passwordError', 'Password must be at least 8 characters and contain one uppercase letter.');
            valid = false;
        }

        if (passwordConfirmation.value === '') {
            showError(passwordConfirmation, 'passwordConfirmationError', 'Please confirm your password.');
            valid = false;
        } else if (passwordConfirmation.value !== password.value) {
            showError(passwordConfirmation, 'passwordConfirmationError', 'Passwords do not match.');
            valid = false;
        }

        if (!terms.checked) {
            termsWrapper.classList.add('invalid');
            termsError.classList.add('show');
            valid = false;
        }

        return valid;
    }

    /* ---------- Submit ---------- */

    // Invalid input stops here; a valid form posts normally and the shared
    // auth-form handler (skillbridge-ui.js) shows the loading state.
    form.addEventListener('submit', function (event) {
        clearAllErrors();

        if (validateForm()) {
            return;
        }

        event.preventDefault();

        const firstError = form.querySelector('.auth-input.is-invalid');
        const target = firstError || (termsWrapper.classList.contains('invalid') ? terms : null);

        if (target) {
            target.scrollIntoView({
                behavior: reduceMotion ? 'auto' : 'smooth',
                block: 'center'
            });
            target.focus({ preventScroll: true });
        }
    });

    /* ---------- Live clearing ---------- */

    function clearFieldOnEdit() {
        this.classList.remove('is-invalid');
        this.removeAttribute('aria-invalid');

        const error = document.getElementById(this.id + 'Error');

        if (error && error.classList.contains('js-error')) {
            clearError(error.id);
        }

        const group = this.closest('.auth-field');

        if (group) {
            group.querySelectorAll('.server-error').forEach(function (error) {
                error.remove();
            });
        }
    }

    form.querySelectorAll('input, select').forEach(function (field) {
        field.addEventListener('input', clearFieldOnEdit);
        field.addEventListener('change', clearFieldOnEdit);
    });

    passwordConfirmation.addEventListener('input', function () {
        if (this.value !== '' && this.value === password.value) {
            clearFieldError(this, 'passwordConfirmationError');
        }
    });

    terms.addEventListener('change', function () {
        if (this.checked) {
            termsWrapper.classList.remove('invalid');
            termsError.classList.remove('show');
        }
    });
});
</script>
@endpush
