{{-- Popup for an unavailable invitation. Needs $invitation and $reason. --}}
@php
    $coordinator = $invitation?->invitedBy;
    [$heading, $body] = match ($reason) {
        'accepted' => ['This invitation was already used', 'An account for this company has already been created. Sign in with the email and password set during registration.'],
        'expired' => ['This invitation has expired', 'The link is too old and no longer works. Ask your coordinator to send a new invitation.'],
        default => ['This invitation link is not valid', 'The link may be incomplete or replaced by a newer invitation. Check the most recent email, or ask your coordinator.'],
    };
    $icon = match ($reason) {
        'accepted' => 'bi-patch-check',
        'expired' => 'bi-hourglass-split',
        default => 'bi-link-45deg',
    };
@endphp
{{-- The landing page only ships inline SVGs, so load the icon font these popups use. --}}
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/sb-invite.css') }}?v={{ @filemtime(public_path('css/sb-invite.css')) }}">
<div class="inv-overlay" data-inv-overlay>
<div class="inv-backdrop" aria-hidden="true"></div>
<section class="inv-page" role="dialog" aria-modal="true" aria-labelledby="invTitle">
    <div class="inv-card inv-card--state">
        <span class="inv-state-icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
        <h1 class="inv-title" id="invTitle">{{ $heading }}</h1>
        <p class="inv-lead">{{ $body }}</p>
        @if($coordinator)
            <p class="inv-contact">
                Coordinator: <strong>{{ $coordinator->name }}</strong><br>
                <a href="mailto:{{ $coordinator->email }}">{{ $coordinator->email }}</a>
            </p>
        @endif
        <div class="inv-actions inv-actions--center">
            @if($reason === 'accepted')
                <a href="{{ route('login') }}" class="inv-btn inv-btn--primary">Sign in</a>
            @else
                <a href="{{ route('home') }}" class="inv-btn inv-btn--ghost"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to home</a>
            @endif
        </div>
    </div>
</section>
</div>
