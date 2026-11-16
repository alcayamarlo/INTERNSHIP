@extends('layouts.guest')

@section('title', 'Invitation unavailable')

@section('content')
@php
    $coordinator = $invitation?->invitedBy;
    [$heading, $body] = match ($reason) {
        'accepted' => ['This invitation was already used', 'An account for this company has already been created. Sign in with the email and password set during registration.'],
        'expired' => ['This invitation has expired', 'The link is too old and no longer works. Ask your coordinator to send a new invitation.'],
        default => ['This invitation link is not valid', 'The link may be incomplete or replaced by a newer invitation. Check the most recent email, or ask your coordinator.'],
    };
@endphp
<div class="sb-guest-wrap">
    <div class="sb-guest-card">
        <h1 class="sb-guest-title">{{ $heading }}</h1>
        <p class="sb-guest-lead">{{ $body }}</p>
        @if($coordinator)
            <p class="sb-guest-note mt-0 mb-3">
                Coordinator: <strong>{{ $coordinator->name }}</strong><br>
                <a href="mailto:{{ $coordinator->email }}">{{ $coordinator->email }}</a>
            </p>
        @endif
        <a href="{{ route('login') }}" class="btn btn-primary">Go to sign in</a>
    </div>
</div>
@endsection
