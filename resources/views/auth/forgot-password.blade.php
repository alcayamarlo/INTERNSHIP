@extends('layouts.guest')

@section('title', 'Reset Password')
@section('subtitle', 'Request a code and choose a new password')
@section('body_class', 'auth-page')

@section('content')

@php
    $secondsLeft = $showResetFields
        ? max(0, (int) $resetCodeExpiresAt - now()->timestamp)
        : 0;

    // Errors for keys that have no field of their own on this form.
    $fieldKeys = ['email', 'code', 'password', 'password_confirmation'];
    $otherErrorKey = collect($errors->keys())->diff($fieldKeys)->first();

    // The email is locked once a code has been sent to it.
    $emailLocked = $showResetFields && filled(old('email'));
@endphp

<div class="auth-pane">

    @include('auth.partials.top', ['secure' => 'Secure Reset'])

    <section class="auth-card" aria-labelledby="auth-title">

        @include('auth.partials.mobile-brand')

        <header class="auth-heading">
            <span class="auth-step-tag">
                @if ($showResetFields)
                    Step 2 of 2 &middot; Verify and finish
                @else
                    Step 1 of 2 &middot; Request a code
                @endif
            </span>

            <h1 id="auth-title">
                Reset your
                <span>password.</span>
            </h1>

            <p>
                @if ($showResetFields)
                    Enter the six-digit code and choose a new password.
                @else
                    Enter your email to receive a 6-digit verification code.
                @endif
            </p>
        </header>

        @if (session('status'))
            <div class="auth-alert auth-alert--success" role="status">
                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($otherErrorKey)
            <div class="auth-alert auth-alert--danger" role="alert">
                <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                <span>{{ $errors->first($otherErrorKey) }}</span>
            </div>
        @endif

        <form
            method="POST"
            action="{{ $showResetFields ? route('password.update') : route('password.email') }}"
            id="password-reset-form"
            data-auth-form
        >
            @csrf

            {{-- EMAIL --}}
            <div class="auth-field">
                <label for="email" class="auth-label">
                    Email Address
                    <span class="auth-required" aria-hidden="true">*</span>
                </label>

                <div class="auth-input-wrap">
                    <i class="bi bi-envelope auth-input-icon" aria-hidden="true"></i>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        maxlength="255"
                        placeholder="Enter your email address"
                        class="auth-input @error('email') is-invalid @enderror"
                        aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                        @if ($emailLocked) readonly aria-readonly="true" @endif
                        @unless ($showResetFields) autofocus @endunless
                        required
                    >
                </div>

                @error('email')
                    <div class="auth-error" role="alert">
                        <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>

            @if ($showResetFields)
                <div class="auth-step" data-reset-step="2">

                    {{-- CODE --}}
                    <div class="auth-field">
                        <div class="auth-label-row">
                            <label for="code" class="auth-label">
                                Six-Digit Code
                                <span class="auth-required" aria-hidden="true">*</span>
                            </label>

                            <span
                                class="auth-countdown @if ($secondsLeft === 0) is-expired @endif"
                                role="timer"
                                data-reset-countdown
                                data-expires-at="{{ (int) $resetCodeExpiresAt }}"
                            >
                                <i class="bi bi-clock" aria-hidden="true"></i>
                                <span data-countdown-text>
                                    @if ($secondsLeft > 0)
                                        Code expires in {{ sprintf('%02d:%02d', intdiv($secondsLeft, 60), $secondsLeft % 60) }}
                                    @else
                                        Code expired. Request a new code.
                                    @endif
                                </span>
                            </span>
                        </div>

                        <div class="auth-input-wrap">
                            <i class="bi bi-key auth-input-icon" aria-hidden="true"></i>
                            <input
                                id="code"
                                name="code"
                                type="text"
                                inputmode="numeric"
                                maxlength="6"
                                pattern="[0-9]{6}"
                                autocomplete="one-time-code"
                                value="{{ old('code') }}"
                                placeholder="6-digit code"
                                class="auth-input auth-input--code @error('code') is-invalid @enderror"
                                aria-invalid="{{ $errors->has('code') ? 'true' : 'false' }}"
                                aria-describedby="codeHelp"
                                @unless ($errors->has('email')) autofocus @endunless
                                required
                            >
                        </div>

                        @error('code')
                            <div class="auth-error" role="alert">
                                <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror

                        <div class="auth-code-actions">
                            <span id="codeHelp">Check your inbox for the verification code.</span>

                            <button
                                type="submit"
                                class="auth-link"
                                form="resend-code-form"
                                data-loading-text="Sending..."
                            >
                                <span class="auth-btn-label">Resend Code</span>
                            </button>
                        </div>
                    </div>

                    {{-- NEW PASSWORD --}}
                    <div class="auth-field">
                        <label for="password" class="auth-label">
                            New Password
                            <span class="auth-required" aria-hidden="true">*</span>
                        </label>

                        <div class="auth-input-wrap auth-input-wrap--password">
                            <i class="bi bi-lock auth-input-icon" aria-hidden="true"></i>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="new-password"
                                placeholder="Enter a new password"
                                class="auth-input @error('password') is-invalid @enderror"
                                aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                                aria-describedby="passwordHint"
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
                            <div class="auth-error" role="alert">
                                <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror

                        <p id="passwordHint" class="auth-hint">Use at least 8 characters.</p>
                    </div>

                    {{-- CONFIRM NEW PASSWORD --}}
                    <div class="auth-field">
                        <label for="password_confirmation" class="auth-label">
                            Confirm New Password
                            <span class="auth-required" aria-hidden="true">*</span>
                        </label>

                        <div class="auth-input-wrap auth-input-wrap--password">
                            <i class="bi bi-lock auth-input-icon" aria-hidden="true"></i>
                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                placeholder="Confirm your new password"
                                class="auth-input"
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
                    </div>

                    <button type="submit" class="auth-btn" data-loading-text="Resetting password...">
                        <i class="bi bi-check2-circle" aria-hidden="true"></i>
                        <span class="auth-btn-label">Reset Password</span>
                    </button>

                </div>
            @else
                <button type="submit" class="auth-btn" data-loading-text="Sending code...">
                    <span class="auth-btn-label">Send Reset Code</span>
                </button>
            @endif
        </form>

        @if ($showResetFields)
            {{-- Resend lives in its own form so pressing Enter in the main form can never trigger it. --}}
            <form
                method="POST"
                action="{{ route('password.email') }}"
                id="resend-code-form"
                data-auth-form
            >
                @csrf
                <input type="hidden" name="email" id="resend-email" value="{{ old('email') }}">
            </form>
        @endif

        <div class="auth-footer-links">
            @if ($showResetFields)
                <a class="auth-link" href="{{ route('password.request', ['new' => 1]) }}">
                    Use a different email address
                </a>
            @endif

            <a class="auth-link" href="{{ route('login') }}">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Back to Login
            </a>
        </div>

    </section>

</div>

@endsection


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const countdown = document.querySelector('[data-reset-countdown]');

    if (countdown) {
        const label = countdown.querySelector('[data-countdown-text]') || countdown;
        const expiresAt = Number(countdown.dataset.expiresAt);
        let timer;

        const updateCountdown = () => {
            const secondsRemaining = Math.max(0, expiresAt - Math.floor(Date.now() / 1000));

            if (secondsRemaining === 0) {
                label.textContent = 'Code expired. Request a new code.';
                countdown.classList.add('is-expired');
                window.clearInterval(timer);
                return;
            }

            const minutes = String(Math.floor(secondsRemaining / 60)).padStart(2, '0');
            const seconds = String(secondsRemaining % 60).padStart(2, '0');
            label.textContent = `Code expires in ${minutes}:${seconds}`;
        };

        updateCountdown();
        timer = window.setInterval(updateCountdown, 1000);
    }

    // Digits only, six at most.
    const codeInput = document.getElementById('code');
    codeInput?.addEventListener('input', () => {
        codeInput.value = codeInput.value.replace(/\D/g, '').slice(0, 6);
    });

    // Keep the resend form's email in step with the visible field.
    const emailInput = document.getElementById('email');
    const resendEmail = document.getElementById('resend-email');
    emailInput?.addEventListener('input', () => {
        if (resendEmail) {
            resendEmail.value = emailInput.value;
        }
    });

    // Put the cursor on the first field that needs attention.
    document.querySelector('.auth-input.is-invalid')?.focus();
});
</script>
@endpush
