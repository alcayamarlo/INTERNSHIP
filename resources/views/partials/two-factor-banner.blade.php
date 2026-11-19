{{-- Two-factor reminders (App\Services\TwoFactor). Admins who have no recovery codes yet; others who could switch it on. --}}
@php
    $tfUser = auth()->user();
    $tf = app(\App\Services\TwoFactor::class);
    $tfKind = null;
    if ($tfUser && ! request()->routeIs('account.security*') && ! $tfUser->must_change_password && $tfUser->hasVerifiedEmail() && $tf->canEmail($tfUser)) {
        if ($tf->isActiveFor($tfUser) && $tf->recoveryCodesLeft($tfUser) === 0) {
            $tfKind = 'codes';
        } elseif ($tf->canEnable($tfUser) && ! $tfUser->two_factor_enabled) {
            $tfKind = 'enable';
        }
    }
@endphp
@if($tfKind)
    <link rel="stylesheet" href="{{ asset('css/sb-two-factor.css') }}?v={{ @filemtime(public_path('css/sb-two-factor.css')) }}">
    <div class="alert {{ $tfKind === 'codes' ? 'alert-warning' : 'alert-info' }} sb-two-factor-banner d-flex flex-wrap align-items-center gap-2 mb-4" role="status" data-two-factor-banner data-banner-kind="{{ $tfKind }}">
        <i class="bi bi-shield-lock" aria-hidden="true"></i>
        <span class="me-auto">
            @if($tfKind === 'codes')
                <strong>Save your recovery codes.</strong> You need one to sign in if email is down.
            @else
                <strong>Protect your account.</strong> Turn on two-factor sign-in: you can open many students' records.
            @endif
        </span>
        <a class="btn btn-sm {{ $tfKind === 'codes' ? 'btn-warning' : 'btn-primary' }}" href="{{ route('account.security') }}">{{ $tfKind === 'codes' ? 'Make recovery codes' : 'Set up' }}</a>
        @if($tfKind === 'enable')
            <button type="button" class="btn-close" data-banner-close aria-label="Remind me later"></button>
        @endif
    </div>
    <script src="{{ asset('js/two-factor.js') }}?v={{ @filemtime(public_path('js/two-factor.js')) }}" defer></script>
@endif
