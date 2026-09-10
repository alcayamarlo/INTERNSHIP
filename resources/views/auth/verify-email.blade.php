@extends('layouts.guest')

@section('title', 'Verify Email')

@section('subtitle', 'Please verify your email address')

@push('styles')
<style>
.auth-verify-wrapper {
    width: 100%;
    max-width: 480px;
    min-height: 100%;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 40px 20px;
}
.verify-card {
    padding: 30px;
    border: 1px solid rgba(148, 163, 184, 0.14);
    border-radius: 15px;
    background: linear-gradient(145deg, rgba(12, 32, 54, 0.96), rgba(7, 21, 38, 0.96));
    box-shadow: 0 18px 50px rgba(0, 0, 0, 0.18);
}
.verify-card::before {
    content: "";
    position: absolute;
    left: 0;
    right: 0;
    top: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, #08d9f5, transparent);
}
.verify-icon {
    width: 60px;
    height: 60px;
    margin: 0 auto 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(8, 217, 245, 0.08);
    border: 2px solid rgba(8, 217, 245, 0.2);
    color: #08d9f5;
    font-size: 24px;
}
.verify-title {
    text-align: center;
    margin: 0 0 10px;
    color: #f8fafc;
    font-size: 18px;
    font-weight: 800;
}
.verify-text {
    text-align: center;
    color: #8da4bd;
    font-size: 12px;
    line-height: 1.7;
    margin-bottom: 24px;
}
.verify-alert {
    padding: 12px;
    margin-bottom: 20px;
    border-radius: 8px;
    font-size: 11px;
    text-align: center;
}
.verify-alert-success {
    border: 1px solid rgba(74, 222, 128, 0.2);
    background: rgba(74, 222, 128, 0.07);
    color: #72e89a;
}
.verify-alert-warning {
    border: 1px solid rgba(251, 191, 36, 0.2);
    background: rgba(251, 191, 36, 0.07);
    color: #fbbf24;
}
.verify-email-display {
    text-align: center;
    padding: 10px;
    margin-bottom: 20px;
    border-radius: 8px;
    background: rgba(4, 17, 31, 0.72);
    border: 1px solid rgba(148, 163, 184, 0.1);
    color: #08d9f5;
    font-size: 12px;
    font-weight: 700;
}
.btn-verify {
    width: 100%;
    height: 43px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(135deg, #08d9f5, #05b8d1);
    color: #03212e;
    font-family: inherit;
    font-size: 11px;
    font-weight: 850;
    cursor: pointer;
    transition: 0.2s ease;
    text-decoration: none;
}
.btn-verify:hover {
    transform: translateY(-1px);
    box-shadow: 0 9px 24px rgba(8, 217, 245, 0.16);
    color: #03212e;
    text-decoration: none;
}
.btn-resend {
    width: 100%;
    height: 43px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: 1px solid rgba(148, 163, 184, 0.15);
    border-radius: 8px;
    background: transparent;
    color: #8da4bd;
    font-family: inherit;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    transition: 0.2s ease;
    text-decoration: none;
}
.btn-resend:hover {
    border-color: rgba(8, 217, 245, 0.3);
    color: #08d9f5;
    text-decoration: none;
}
.verify-divider {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 16px 0;
    color: #63778d;
    font-size: 8px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}
.verify-divider::before,
.verify-divider::after {
    content: "";
    flex: 1;
    height: 1px;
    background: rgba(148, 163, 184, 0.10);
}
.verify-footer {
    text-align: center;
    margin-top: 20px;
    font-size: 10px;
    color: #536b82;
}
.verify-footer a {
    color: #08d9f5;
    text-decoration: none;
    font-weight: 700;
}
.verify-footer a:hover {
    text-decoration: underline;
}
</style>
@endpush

@section('content')
<div class="auth-verify-wrapper">
    <div class="verify-card" style="position: relative;">

        <div class="verify-icon">
            <i class="bi bi-envelope-check"></i>
        </div>

        <h2 class="verify-title">Verify Your Email</h2>

        <p class="verify-text">
            We've sent a verification link to your email address.
            Please check your inbox and click the link to verify your account.
        </p>

        <div class="verify-email-display">
            <i class="bi bi-envelope-fill"></i>
            {{ Auth::user()->email ?? 'your email address' }}
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="verify-alert verify-alert-success">
                <i class="bi bi-check-circle-fill"></i>
                A new verification link has been sent to your email address.
            </div>
        @endif

        @if (session('status'))
            @if (session('status') !== 'verification-link-sent')
                <div class="verify-alert verify-alert-success">
                    <i class="bi bi-check-circle-fill"></i>
                    {{ session('status') }}
                </div>
            @endif
        @endif

        <div style="position: relative;">
            <form method="POST" action="{{ route('verification.resend') }}">
                @csrf
                <button type="submit" class="btn-verify" style="margin-bottom: 10px;">
                    <i class="bi bi-send-fill"></i>
                    Resend Verification Email
                </button>
            </form>
        </div>

        <div class="verify-divider">or</div>

        <div style="position: relative;">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-resend">
                    <i class="bi bi-box-arrow-left"></i>
                    Sign Out
                </button>
            </form>
        </div>

        <div class="verify-footer">
            Didn't receive the email? Check your spam folder or
            <a href="mailto:support@skillbridge.com">contact support</a>.
        </div>

    </div>
</div>
@endsection
