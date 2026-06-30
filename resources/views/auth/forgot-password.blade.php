@extends('layouts.guest')

@section('title', 'Forgot Password')
@section('subtitle', 'Reset your password')

@section('content')
<p class="text-muted small mb-4">Enter your email address and we will send you a password reset link.</p>
<form method="POST" action="{{ route('password.email') }}">
    @csrf
    <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autofocus>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <button type="submit" class="btn btn-primary w-100 mb-3">Send Reset Link</button>
    <div class="text-center">
        <a href="{{ route('login') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Back to login</a>
    </div>
</form>
@endsection
