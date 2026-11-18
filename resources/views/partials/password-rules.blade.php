{{-- Live password checklist: one row per rule, each turning green with a check as it is met. Mirrors
     App\Support\PasswordPolicy (the server checks again). Props: $password (id of the password input),
     $confirm (id of the confirm input), optional $id (for aria-describedby). --}}
<ul class="pw-rules" @isset($id) id="{{ $id }}" @endisset data-pw-rules data-password="{{ $password }}" data-confirm="{{ $confirm }}"
    data-min="{{ \App\Support\PasswordPolicy::MIN_LENGTH }}" data-max="{{ \App\Support\PasswordPolicy::MAX_LENGTH }}"
    aria-label="Password requirements" aria-live="polite">
    <li data-rule="length">{{ \App\Support\PasswordPolicy::MIN_LENGTH }} to {{ \App\Support\PasswordPolicy::MAX_LENGTH }} characters</li>
    <li data-rule="uppercase">An uppercase letter (A-Z)</li>
    <li data-rule="lowercase">A lowercase letter (a-z)</li>
    <li data-rule="number">A number (0-9)</li>
    <li data-rule="special">A special character (! @ # $ %)</li>
    <li data-rule="match">Both passwords match</li>
</ul>
@once
    <link rel="stylesheet" href="{{ asset('css/sb-password-rules.css') }}?v={{ @filemtime(public_path('css/sb-password-rules.css')) }}">
    <script defer src="{{ asset('js/sb-password-rules.js') }}?v={{ @filemtime(public_path('js/sb-password-rules.js')) }}"></script>
@endonce
