{{-- First-login step 1/2: the landing page with the "Add your Gmail" or "Enter the code" view of the auth modal open. --}}
@include('welcome', ['authOpen' => $needsEmail ? 'email' : 'code'])
