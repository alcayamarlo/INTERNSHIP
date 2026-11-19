{{-- Idle sign-out warning: App\Http\Middleware\EndIdleSession ends the session, this warns a minute before. --}}
<link rel="stylesheet" href="{{ asset('css/sb-idle-warning.css') }}?v={{ @filemtime(public_path('css/sb-idle-warning.css')) }}">
<div hidden
     data-idle-timeout
     data-seconds-left="{{ \App\Http\Middleware\EndIdleSession::secondsLeft(request()) }}"
     data-status-url="{{ route('session.idle-status') }}"
     data-keep-alive-url="{{ route('session.keep-alive') }}"
     data-login-url="{{ route('login') }}"></div>
<script src="{{ asset('js/idle-timeout.js') }}?v={{ @filemtime(public_path('js/idle-timeout.js')) }}" defer></script>
