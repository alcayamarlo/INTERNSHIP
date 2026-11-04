{{-- /forgot-password is the landing page with the Forgot / Reset view of the auth modal open. --}}
@include('welcome', ['authOpen' => $showResetFields ? 'reset' : 'forgot'])
