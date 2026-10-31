@extends('layouts.guest')

@section('title', 'Set a New Password')
@section('subtitle', 'Choose a password only you know')
@section('body_class', 'auth-page')

@section('content')

<div class="auth-pane">

    @include('auth.partials.top', ['secure' => 'Secure Account'])

    <section class="auth-card" aria-labelledby="auth-title">

        @include('auth.partials.mobile-brand')

        <header class="auth-heading">
            <h1 id="auth-title">
                Set a new
                <span>password.</span>
            </h1>
            <p>Your account was created with a temporary password. Choose a new one to continue.</p>
        </header>

        @if ($errors->any())
            <div class="auth-alert auth-alert--danger" role="alert">
                <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('password.force-change.update') }}"
            id="force-password-change-form"
            data-auth-form
        >
            @csrf
            @method('PUT')

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

            <button type="submit" class="auth-btn" data-loading-text="Updating password...">
                <i class="bi bi-check2-circle" aria-hidden="true"></i>
                <span class="auth-btn-label">Update Password</span>
            </button>
        </form>

    </section>

</div>

@endsection
