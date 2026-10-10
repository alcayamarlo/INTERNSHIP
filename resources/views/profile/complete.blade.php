@extends('layouts.guest')

@section('title', 'Complete Your Profile')
@section('subtitle', 'Your account needs a profile before you can continue')

@section('content')
<main style="max-width: 560px; margin: 0 auto; padding: 48px 24px; color: #f8fafc;">
    <h1>Complete your {{ $role->label() }} profile</h1>
    <p style="margin: 16px 0 24px; color: #8da4bd;">
        Your account is missing its {{ strtolower($role->label()) }} profile. Open your profile page to add the required information.
    </p>
    <a href="{{ route($profileRoute) }}" class="btn btn-primary">Continue to profile</a>
</main>
@endsection
