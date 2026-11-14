@extends('layouts.guest')

@section('title', 'Invitation unavailable')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/company-invitation.css') }}?v={{ @filemtime(public_path('css/company-invitation.css')) }}">
@endpush

@section('content')
@php
    $coordinator = $invitation?->invitedBy;
    [$heading, $body] = match ($reason) {
        'accepted' => ['This invitation was already used', 'An account for this company has already been created. Sign in with the email and password set during registration.'],
        'expired' => ['This invitation has expired', 'The link is too old and no longer works. Ask your coordinator to send a new invitation.'],
        default => ['This invitation link is not valid', 'The link may be incomplete or replaced by a newer invitation. Check the most recent email, or ask your coordinator.'],
    };
@endphp
<div class="ci-wrap">
    <div class="ci-card">
        <h1 class="ci-title">{{ $heading }}</h1>
        <p class="ci-lead">{{ $body }}</p>
        @if($coordinator)
            <p class="ci-contact">
                Coordinator: <strong>{{ $coordinator->name }}</strong><br>
                <a href="mailto:{{ $coordinator->email }}">{{ $coordinator->email }}</a>
            </p>
        @endif
        <a href="{{ route('login') }}" class="btn btn-primary">Go to sign in</a>
    </div>
</div>
@endsection
