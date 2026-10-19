@extends('layouts.guest')

@section('title', 'Reset Password')
@section('subtitle', 'Request a code and choose a new password')

@push('styles')
<style>
body.skillbridge-guest:has(.forgot-password-page) {
    height: 100vh;
    height: 100dvh;
    min-height: 0;
    overflow: hidden;
}

body.skillbridge-guest:has(.forgot-password-page) .auth-shell {
    display: flex;
    flex-wrap: nowrap;
    width: 100%;
    height: 100vh;
    height: 100dvh;
    min-height: 0;
    overflow: hidden;
}

body.skillbridge-guest:has(.forgot-password-page) .auth-left {
    position: relative;
    flex: 0 0 58.3333%;
    width: 58.3333%;
    min-width: 0;
    height: 100%;
    min-height: 0;
    background: #301016;
}

body.skillbridge-guest:has(.forgot-password-page) .auth-left::before {
    inset: 0;
    width: auto;
    height: auto;
    opacity: 1;
    border-radius: 0;
    background: url('{{ asset("images/login-campus.png") }}') center 48% / cover no-repeat;
}

body.skillbridge-guest:has(.forgot-password-page) .auth-left::after {
    inset: 0;
    width: auto;
    height: auto;
    opacity: 1;
    border-radius: 0;
    background: linear-gradient(145deg, rgba(35, 8, 13, .78), rgba(116, 12, 29, .58) 55%, rgba(29, 13, 18, .78));
}

body.skillbridge-guest:has(.forgot-password-page) .auth-left h1 span {
    color: #ffb8bf;
}

body.skillbridge-guest:has(.forgot-password-page) .auth-right {
    flex: 0 0 41.6667%;
    width: 41.6667%;
    height: 100%;
    min-width: 0;
    min-height: 0;
    overflow: hidden;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-password-page {
    position: relative;
    isolation: isolate;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    min-height: 0;
    overflow: hidden;
    padding: clamp(18px, 3vh, 32px) clamp(20px, 3vw, 48px);
    background: linear-gradient(135deg, #f8fafc 0%, #fff 52%, rgba(254, 242, 242, .7) 100%);
    color: #0f172a;
    font-family: "Figtree", "Segoe UI", system-ui, sans-serif;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-password-page::before,
body.skillbridge-guest:has(.forgot-password-page) .forgot-password-page::after {
    position: absolute;
    z-index: 0;
    width: 20rem;
    height: 20rem;
    border-radius: 9999px;
    filter: blur(64px);
    pointer-events: none;
    content: "";
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-password-page::before {
    top: -5rem;
    right: -5rem;
    background: rgba(239, 68, 68, .1);
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-password-page::after {
    bottom: -5rem;
    left: -5rem;
    background: rgba(251, 113, 133, .1);
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-password-card {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 500px;
    padding: clamp(20px, 2.2vw, 30px);
    border: 1px solid rgba(226, 232, 240, .8);
    border-radius: 24px;
    background: rgba(255, 255, 255, .95);
    box-shadow: 0 20px 25px -5px rgba(15, 23, 42, .1), 0 8px 10px -6px rgba(15, 23, 42, .1);
    backdrop-filter: blur(12px);
    font-family: "Figtree", "Segoe UI", system-ui, sans-serif;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-password-page :is(button, input, a) {
    font-family: "Figtree", "Segoe UI", system-ui, sans-serif;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-password-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 22px;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-password-top .auth-back-link {
    display: inline-flex;
    flex: 0 1 auto;
    align-items: center;
    gap: 8px;
    min-height: 44px;
    margin: 0;
    padding: 8px 14px;
    border: 1px solid #fee2e2;
    border-radius: 999px;
    background: #fff7f7;
    color: #991b1b;
    font: 700 14px/1.2 "Figtree", "Segoe UI", system-ui, sans-serif;
    text-decoration: none;
    white-space: nowrap;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-password-top .auth-back-link:hover {
    border-color: #b91c1c;
    background: #b91c1c;
    color: #fff;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-password-secure {
    display: inline-flex;
    flex: 0 0 auto;
    align-items: center;
    gap: 6px;
    padding: 8px 10px;
    border: 1px solid #e2e8f0;
    border-radius: 9999px;
    background: #f8fafc;
    color: #475569;
    font-size: 12px;
    white-space: nowrap;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-password-secure i {
    color: #16a34a;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-password-heading {
    margin-bottom: 20px;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-password-heading h1 {
    margin: 0;
    color: #0f172a;
    font: 700 clamp(29px, 3vw, 38px)/1.08 "Bricolage Grotesque", "Figtree", "Segoe UI", sans-serif;
    letter-spacing: -.04em;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-password-heading h1 span {
    display: block;
    margin-top: 3px;
    color: #dc2626;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-password-heading p {
    margin: 10px 0 0;
    color: #6b7280;
    font-size: 14px;
    line-height: 1.5;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-status,
body.skillbridge-guest:has(.forgot-password-page) .forgot-errors {
    padding: 10px 12px;
    margin: 0 0 14px;
    border-radius: 10px;
    font-size: 13px;
    line-height: 1.45;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-status {
    border: 1px solid #bbf7d0;
    background: #f0fdf4;
    color: #166534;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-errors {
    border: 1px solid #fecaca;
    background: #fef2f2;
    color: #991b1b;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-errors div + div {
    margin-top: 3px;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-form {
    display: grid;
    gap: 12px;
    margin: 0;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-field {
    min-width: 0;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-field label {
    display: flex;
    align-items: center;
    gap: 7px;
    margin: 0 0 7px;
    color: #334155;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .04em;
    line-height: 1.25;
    text-transform: uppercase;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-field label i {
    color: #b91c1c;
    font-size: 12px;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-input-wrap {
    position: relative;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-input-wrap > i {
    position: absolute;
    z-index: 1;
    top: 50%;
    left: 14px;
    transform: translateY(-50%);
    color: #64748b;
    pointer-events: none;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-input {
    display: block;
    width: 100%;
    height: 46px;
    padding: 0 14px 0 42px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    outline: 0;
    background: #f1f5f9;
    color: #0f172a;
    font: 400 14px/1.4 "Figtree", "Segoe UI", system-ui, sans-serif;
    transition: border-color 150ms ease, background-color 150ms ease, box-shadow 150ms ease;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-input::placeholder {
    color: #64748b;
    opacity: 1;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-input:hover {
    border-color: #94a3b8;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-input:focus {
    border-color: #dc2626;
    background: #fff;
    box-shadow: 0 0 0 2px rgba(239, 68, 68, .2);
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-input.is-invalid {
    border-color: #b91c1c;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-field-error {
    margin-top: 4px;
    color: #b91c1c;
    font-size: 12px;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-password-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 4px;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-password-actions button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-width: 0;
    min-height: 46px;
    padding: 10px 12px;
    border: 1px solid #b91c1c;
    border-radius: 10px;
    background: #b91c1c;
    color: #fff;
    font: 600 14px/1.25 "Figtree", "Segoe UI", system-ui, sans-serif;
    cursor: pointer;
    transition: transform 200ms ease, background-color 200ms ease, box-shadow 200ms ease;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-password-actions button:hover {
    border-color: #991b1b;
    background: #991b1b;
    box-shadow: 0 8px 18px rgba(185, 28, 28, .18);
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-password-actions button:active {
    transform: scale(.97);
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-password-back {
    display: block;
    width: fit-content;
    margin: 18px auto 0;
    color: #b91c1c;
    font-size: 14px;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    transition: color 150ms ease, opacity 150ms ease;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-password-back:hover {
    color: #991b1b;
    opacity: .85;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-field label {
    color: #334155;
    font-size: 12px;
    letter-spacing: .02em;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-field label i {
    color: #b91c1c;
}

body.skillbridge-guest:has(.forgot-password-page) .forgot-password-page :is(a, button, input):focus-visible {
    outline: 3px solid rgba(220, 38, 38, .35);
    outline-offset: 3px;
}

@media (max-height: 760px) and (min-width: 851px) {
    body.skillbridge-guest:has(.forgot-password-page) .forgot-password-card {
        padding: 22px 28px;
    }

    body.skillbridge-guest:has(.forgot-password-page) .forgot-password-top {
        margin-bottom: 14px;
    }

    body.skillbridge-guest:has(.forgot-password-page) .forgot-password-heading {
        margin-bottom: 14px;
    }

    body.skillbridge-guest:has(.forgot-password-page) .forgot-form {
        gap: 8px;
    }
}

@media (max-width: 850px) {
    html:has(body.skillbridge-guest .forgot-password-page) {
        height: auto;
        overflow: auto;
    }

    body.skillbridge-guest:has(.forgot-password-page) {
        height: auto;
        min-height: 100vh;
        min-height: 100dvh;
        overflow: auto;
    }

    body.skillbridge-guest:has(.forgot-password-page) .auth-shell {
        display: block;
        height: auto;
        min-height: 100vh;
        min-height: 100dvh;
        overflow-x: clip;
        overflow-y: visible;
    }

    body.skillbridge-guest:has(.forgot-password-page) .auth-left {
        display: none;
    }

    body.skillbridge-guest:has(.forgot-password-page) .auth-right {
        width: 100%;
        height: auto;
        min-height: 100vh;
        min-height: 100dvh;
        overflow: visible;
    }

    body.skillbridge-guest:has(.forgot-password-page) .forgot-password-page {
        min-height: 100vh;
        min-height: 100dvh;
        height: auto;
        overflow-x: clip;
        overflow-y: visible;
        padding: 24px 16px;
    }
}

@media (max-width: 480px) {
    body.skillbridge-guest:has(.forgot-password-page) .forgot-password-card {
        padding: 22px 18px;
        border-radius: 20px;
    }

    body.skillbridge-guest:has(.forgot-password-page) .forgot-password-actions {
        grid-template-columns: 1fr;
    }
}

@media (prefers-reduced-motion: reduce) {
    body.skillbridge-guest:has(.forgot-password-page) *,
    body.skillbridge-guest:has(.forgot-password-page) *::before,
    body.skillbridge-guest:has(.forgot-password-page) *::after {
        scroll-behavior: auto !important;
        transition-duration: .01ms !important;
    }
}
</style>
@endpush

@section('content')
<main class="forgot-password-page">
    <section class="forgot-password-card" aria-labelledby="forgot-password-title">
        <header class="forgot-password-top">
            <a class="auth-back-link" href="{{ route('home') }}">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Back to Home
            </a>
            <span class="forgot-password-secure">
                <i class="bi bi-shield-check" aria-hidden="true"></i>
                Secure Reset
            </span>
        </header>

        <div class="forgot-password-heading">
            <h1 id="forgot-password-title">
                Reset your
                <span>password.</span>
            </h1>
            <p>Request a six-digit code, then enter it with your new password. The code expires after 15 minutes.</p>
        </div>

        @if (session('status'))
            <div class="forgot-status" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="forgot-errors" role="alert">
                @foreach ($errors->all() as $message)
                    <div>{{ $message }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" id="password-reset-form" class="forgot-form">
            @csrf

            <div class="forgot-field">
                <label for="email"><i class="bi bi-envelope-fill" aria-hidden="true"></i>Email address <span aria-hidden="true">*</span></label>
                <div class="forgot-input-wrap">
                    <i class="bi bi-envelope" aria-hidden="true"></i>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        placeholder="Enter your email address"
                        class="forgot-input @error('email') is-invalid @enderror"
                        aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                        required
                    >
                </div>
                @error('email')
                    <div class="forgot-field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="forgot-field">
                <label for="code"><i class="bi bi-shield-lock-fill" aria-hidden="true"></i>Six-digit code <span aria-hidden="true">*</span></label>
                <div class="forgot-input-wrap">
                    <i class="bi bi-key" aria-hidden="true"></i>
                    <input
                        id="code"
                        name="code"
                        type="text"
                        inputmode="numeric"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        autocomplete="one-time-code"
                        value="{{ old('code') }}"
                        placeholder="Enter the code sent to your email"
                        class="forgot-input @error('code') is-invalid @enderror"
                        aria-invalid="{{ $errors->has('code') ? 'true' : 'false' }}"
                        required
                    >
                </div>
                @error('code')
                    <div class="forgot-field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="forgot-field">
                <label for="password"><i class="bi bi-lock-fill" aria-hidden="true"></i>New password <span aria-hidden="true">*</span></label>
                <div class="forgot-input-wrap">
                    <i class="bi bi-lock" aria-hidden="true"></i>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        placeholder="Enter a new password"
                        class="forgot-input @error('password') is-invalid @enderror"
                        aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                        required
                    >
                </div>
                @error('password')
                    <div class="forgot-field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="forgot-field">
                <label for="password_confirmation"><i class="bi bi-lock-fill" aria-hidden="true"></i>Confirm new password <span aria-hidden="true">*</span></label>
                <div class="forgot-input-wrap">
                    <i class="bi bi-lock" aria-hidden="true"></i>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        placeholder="Confirm your new password"
                        class="forgot-input"
                        required
                    >
                </div>
            </div>

            <div class="forgot-password-actions">
                <button type="submit" formaction="{{ route('password.email') }}" formnovalidate>
                    <i class="bi bi-envelope-arrow-up" aria-hidden="true"></i>
                    Send reset code
                </button>
                <button type="submit" formaction="{{ route('password.update') }}">
                    <i class="bi bi-check2-circle" aria-hidden="true"></i>
                    Reset password
                </button>
            </div>
        </form>

        <a class="forgot-password-back" href="{{ route('login') }}">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            Back to Login
        </a>
    </section>
</main>
@endsection
