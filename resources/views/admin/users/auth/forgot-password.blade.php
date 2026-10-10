@extends('layouts.guest')

@section('title', 'Reset Password')
@section('subtitle', 'Request a code and choose a new password')

@section('content')
<div class="container py-5" style="max-width: 520px;">
    <div class="card shadow">
        <div class="card-body p-4">
            <h1 class="h3 mb-2">Reset your password</h1>
            <p class="text-muted mb-4">Request a six-digit code, then enter it here with your new password. The code expires after 15 minutes.</p>

            @if(session('status'))
                <div class="alert alert-info" role="status">{{ session('status') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    @foreach($errors->all() as $message)
                        <div>{{ $message }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" id="password-reset-form">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="code" class="form-label">Six-digit code</label>
                    <input id="code" name="code" type="text" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" autocomplete="one-time-code" value="{{ old('code') }}" class="form-control">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">New password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" class="form-control">
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">Confirm new password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="form-control">
                </div>

                <button type="submit" formaction="{{ route('password.email') }}" formnovalidate class="btn btn-outline-primary w-100 mb-2">
                    Send reset code
                </button>
                <button type="submit" formaction="{{ route('password.update') }}" class="btn btn-primary w-100">
                    Reset password
                </button>
            </form>

            <div class="text-center mt-4">
                <a href="{{ route('login') }}">Back to sign in</a>
            </div>
        </div>
    </div>
</div>
@endsection
