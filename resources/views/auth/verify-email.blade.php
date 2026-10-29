@extends('layouts.guest')

@section('title', 'Verify Email')

@section('subtitle', 'Please verify your email address')

@section('body_class', 'auth-page')

@section('content')

<div class="auth-pane">

    @include('auth.partials.top', ['secure' => 'Secure Verification'])

    <section class="auth-card" aria-labelledby="auth-title">

        @include('auth.partials.mobile-brand')

        <header class="auth-heading">
            <h1 id="auth-title">
                Verify your
                <span>email.</span>
            </h1>
            <p>Enter the 6-digit code sent to your email address. The code expires in 10 minutes.</p>
        </header>

        @if (session('status'))
            <div class="auth-alert auth-alert--success" role="status">
                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('verification.verify') }}"
            id="verifyForm"
            data-auth-form
        >
            @csrf

            {{-- EMAIL (read-only) --}}
            <div class="auth-field">
                <label for="email" class="auth-label">Email Address</label>

                <div class="auth-input-wrap">
                    <i class="bi bi-envelope auth-input-icon" aria-hidden="true"></i>
                    <input
                        type="email"
                        id="email"
                        class="auth-input"
                        value="{{ Auth::user()->email ?? '' }}"
                        readonly
                        aria-readonly="true"
                    >
                </div>
            </div>

            {{-- CODE --}}
            <div class="auth-field">
                <label for="code" class="auth-label">
                    Verification Code
                    <span class="auth-required" aria-hidden="true">*</span>
                </label>

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
                        @if ($errors->has('code')) aria-describedby="codeError" @endif
                        autofocus
                        required
                    >
                </div>

                @error('code')
                    <div id="codeError" class="auth-error" role="alert">
                        <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <button type="submit" class="auth-btn" data-loading-text="Verifying...">
                <span class="auth-btn-label">Verify Email</span>
            </button>
        </form>

        <form method="POST" action="{{ route('verification.resend') }}" data-auth-form>
            @csrf
            <button type="submit" class="auth-btn auth-btn--ghost auth-btn--spaced" data-loading-text="Sending...">
                <span class="auth-btn-label">Resend Code</span>
            </button>
        </form>

        <p class="auth-footer-text">
            Didn't receive the email? Check your spam folder or
            <a href="mailto:support@skillbridge.com">contact support</a>.
        </p>

        <form method="POST" action="{{ route('logout') }}" class="auth-footer-links">
            @csrf
            <button type="submit" class="auth-link">
                <i class="bi bi-box-arrow-left" aria-hidden="true"></i>
                Sign Out
            </button>
        </form>

    </section>

</div>

@endsection


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Digits only, six at most.
    const codeInput = document.getElementById('code');
    codeInput?.addEventListener('input', () => {
        codeInput.value = codeInput.value.replace(/\D/g, '').slice(0, 6);
    });
});
</script>
@endpush
