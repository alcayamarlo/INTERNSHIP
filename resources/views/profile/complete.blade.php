@extends('layouts.guest')

@section('title', 'Complete Your Profile')
@section('subtitle', 'Your account needs a profile before you can continue')

@section('content')
<div class="sb-guest-wrap">
    <div class="sb-guest-card">
        <h1 class="sb-guest-title">Complete your {{ $role->label() }} profile</h1>
        <p class="sb-guest-lead">
            Your account is missing its {{ strtolower($role->label()) }} profile. Open your profile page to add the required information.
        </p>
        <a href="{{ route($profileRoute) }}" class="btn btn-primary">Continue to profile</a>
    </div>
</div>
@endsection
