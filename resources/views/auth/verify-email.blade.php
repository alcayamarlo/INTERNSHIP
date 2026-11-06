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
            @if ($needsEmail)
                <h1 id="auth-title">
                    Add your
                    <span>email.</span>
                </h1>
                <p>We'll send a 6-digit code to confirm it's yours. Use an email you check often, like your Gmail. You'll get OJT updates there, and you can use it to reset your password.</p>
            @else
                <h1 id="auth-title">
                    Verify your
                    <span>email.</span>
                </h1>
                <p>Enter the 6-digit code sent to your email address. The code expires in 10 minutes.</p>
            @endif
        </header>

        @if (session('status'))
            <div class="auth-alert auth-alert--success" role="status">
                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($needsEmail)
        <form method="POST" action="{{ route('verification.email') }}" id="addEmailForm" data-auth-form>
            @csrf

            <div class="auth-field">
                <label for="new_email" class="auth-label">
                    Email Address
                    <span class="auth-required" aria-hidden="true">*</span>
                </label>

                <div class="auth-input-wrap">
                    <i class="bi bi-envelope auth-input-icon" aria-hidden="true"></i>
                    <input
                        id="new_email"
                        name="email"
                        type="email"
                        autocomplete="email"
                        maxlength="255"
                        value="{{ old('email', Auth::user()->email) }}"
                        placeholder="juan.delacruz@gmail.com"
                        class="auth-input @error('email') is-invalid @enderror"
                        aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                        @if ($errors->has('email')) aria-describedby="emailError" @endif
                        autofocus
                        required
                    >
                </div>

                @error('email')
                    <div id="emailError" class="auth-error" role="alert">
                        <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <button type="submit" class="auth-btn" data-loading-text="Sending code...">
                <span class="auth-btn-label">Send Code</span>
            </button>
        </form>

        @if (Auth::user()->email !== null)
            <p class="auth-footer-text"><a href="{{ route('verification.notice') }}">Back to entering the code</a></p>
        @endif
        @else

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
                @if ($canChangeEmail)
                    <a href="{{ route('verification.notice', ['change' => 1]) }}" class="auth-link">Wrong email? Use a different one</a>
                @endif
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
        @endif

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
