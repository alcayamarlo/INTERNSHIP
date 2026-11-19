@extends('layouts.guest')

@section('title', 'Sign-in code')
@section('subtitle', 'One more step to protect student data')

@section('content')
<div class="sb-guest-wrap">
    <div class="sb-guest-card">
        <h1 class="sb-guest-title">Enter your sign-in code</h1>

        @if($state === 'sent')
            <p class="sb-guest-lead" data-two-factor-state="sent">We sent a 6-digit code to <strong>{{ $email }}</strong>. It expires in {{ config('security.two_factor.code_minutes') }} minutes.</p>
        @elseif($state === 'failed')
            <div class="alert alert-warning" role="alert" data-two-factor-state="failed">We could not send the email right now.@if($hasRecoveryCodes) Use one of your recovery codes below, or try again.@else Try again in a moment, or ask the administrator to run <code>user:reset-2fa</code> for your account.@endif</div>
        @else
            <div class="alert alert-info" role="alert" data-two-factor-state="unavailable">Sign-in codes are not emailed to this account.@if($hasRecoveryCodes) Use one of your recovery codes below.@else Ask the administrator to run <code>user:reset-2fa</code> for your account.@endif</div>
        @endif

        <form method="POST" action="{{ route('two-factor.verify') }}" class="mt-3" novalidate>
            @csrf
            <label class="form-label" for="twoFactorCode">Code or recovery code</label>
            <input id="twoFactorCode" name="code" type="text" inputmode="text" autocomplete="one-time-code" autocapitalize="off" spellcheck="false"
                   class="form-control form-control-lg @error('code') is-invalid @enderror" maxlength="30" autofocus required>
            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror

            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" name="trust" value="1" id="twoFactorTrust">
                <label class="form-check-label" for="twoFactorTrust">Trust this browser for {{ $trustDays }} days (do not use on a shared computer)</label>
            </div>

            <button type="submit" class="btn btn-primary w-100 mt-3">Continue</button>
        </form>

        <div class="d-flex flex-wrap gap-3 mt-3 small">
            @if($state !== 'unavailable')
                <form method="POST" action="{{ route('two-factor.resend') }}">
                    @csrf
                    <button type="submit" class="btn btn-link p-0 small">Send a new code</button>
                </form>
            @endif
            <a href="{{ route('login') }}">Start over</a>
        </div>
    </div>
</div>
@endsection
