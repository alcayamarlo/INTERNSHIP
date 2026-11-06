@extends('layouts.guest')

@section('title', 'Set a New Password')
@section('subtitle', 'Choose a password only you know')
@section('body_class', 'auth-page')

@push('styles')
<style>
    /* Live password checklist and strength meter (colors from the SkillBridge theme tokens). */
    .pw-meter { display: grid; gap: 6px; margin-top: 10px; }
    .pw-meter-bars { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
    .pw-meter-bars i { height: 6px; border-radius: 6px; background: var(--sb-border, #f3d9db); transition: background-color .2s ease; }
    .pw-meter[data-level="weak"] .pw-meter-bars i:nth-child(1) { background: var(--sb-danger-ink, #b42332); }
    .pw-meter[data-level="medium"] .pw-meter-bars i:nth-child(-n+2) { background: #c98a04; }
    .pw-meter[data-level="strong"] .pw-meter-bars i { background: var(--sb-success-ink, #187443); }
    .pw-meter-text { font-size: 13px; font-weight: 600; color: var(--sb-muted, #7a5f63); }
    .pw-checklist { list-style: none; margin: 14px 0 0; padding: 12px 14px; display: grid; gap: 6px; border: 1px solid var(--sb-border, #f3d9db); border-radius: 12px; font-size: 13.5px; }
    .pw-checklist li { display: flex; gap: 8px; align-items: flex-start; color: var(--sb-muted, #7a5f63); line-height: 1.4; }
    .pw-checklist li::before { content: "\25CB"; flex: none; width: 16px; text-align: center; }
    .pw-checklist li.is-met { color: var(--sb-success-ink, #187443); }
    .pw-checklist li.is-met::before { content: "\2713"; font-weight: 800; }
    .pw-errors { margin: 6px 0 0; padding-left: 18px; }
    @media (prefers-reduced-motion: reduce) { .pw-meter-bars i { transition: none; } }
</style>
@endpush

@section('content')

<div class="auth-pane">

    @include('auth.partials.top', ['secure' => 'Secure Account'])

    <section class="auth-card" aria-labelledby="auth-title">

        @include('auth.partials.mobile-brand')

        <header class="auth-heading">
            <h1 id="auth-title">
                Set a new
                <span>password.</span>
            </h1>
            <p>Your account was created with your birthday as a temporary password. Choose a strong new password to continue.</p>
        </header>

        @if ($lockedSeconds > 0)
            <div class="auth-alert auth-alert--danger" role="alert">
                <i class="bi bi-lock-fill" aria-hidden="true"></i>
                <span>Too many incorrect attempts. Try again in {{ max(1, (int) ceil($lockedSeconds / 60)) }} {{ \Illuminate\Support\Str::plural('minute', max(1, (int) ceil($lockedSeconds / 60))) }}.</span>
            </div>
        @elseif ($errors->any())
            <div class="auth-alert auth-alert--danger" role="alert">
                <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                <span>Please fix the items below.</span>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('password.force-change.update') }}"
            id="force-password-change-form"
            data-auth-form
            data-policy="{{ json_encode($policy) }}"
            novalidate
        >
            @csrf
            @method('PUT')

            @foreach ([
                ['current_password', 'Current Password', 'current-password', 'Your birthday, for example 05152008', 'bi-shield-lock'],
                ['password', 'New Password', 'new-password', 'Enter a new password', 'bi-lock'],
                ['password_confirmation', 'Confirm New Password', 'new-password', 'Type the new password again', 'bi-lock'],
            ] as [$field, $label, $autocomplete, $placeholder, $icon])
                <div class="auth-field">
                    <label for="{{ $field }}" class="auth-label">
                        {{ $label }}
                        <span class="auth-required" aria-hidden="true">*</span>
                    </label>

                    <div class="auth-input-wrap auth-input-wrap--password">
                        <i class="bi {{ $icon }} auth-input-icon" aria-hidden="true"></i>
                        <input
                            id="{{ $field }}"
                            name="{{ $field }}"
                            type="password"
                            autocomplete="{{ $autocomplete }}"
                            maxlength="{{ $field === 'current_password' ? 255 : $policy['max'] }}"
                            placeholder="{{ $placeholder }}"
                            class="auth-input @error($field) is-invalid @enderror"
                            aria-invalid="{{ $errors->has($field) ? 'true' : 'false' }}"
                            @if ($errors->has($field)) aria-describedby="{{ $field }}Error" @endif
                            @if ($field === 'current_password') autofocus @endif
                            @disabled($lockedSeconds > 0)
                            required
                        >
                        <button
                            type="button"
                            class="auth-toggle"
                            data-password-toggle="{{ $field }}"
                            aria-controls="{{ $field }}"
                            aria-label="Show password"
                            aria-pressed="false"
                            title="Show password"
                        >
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>

                    {{-- Server-side errors: every broken rule is listed, not just the first. --}}
                    @if ($errors->has($field))
                        <div id="{{ $field }}Error" class="auth-error" role="alert">
                            <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                            @if (count($errors->get($field)) > 1)
                                <ul class="pw-errors">@foreach ($errors->get($field) as $message)<li>{{ $message }}</li>@endforeach</ul>
                            @else
                                <span>{{ $errors->first($field) }}</span>
                            @endif
                        </div>
                    @endif

                    @if ($field === 'password')
                        <div class="pw-meter" data-pw-meter data-level="" aria-live="polite">
                            <div class="pw-meter-bars" aria-hidden="true"><i></i><i></i><i></i></div>
                            <span class="pw-meter-text" data-pw-meter-text>Strength: not set</span>
                        </div>
                    @endif
                </div>
            @endforeach

            {{-- Live checklist: each line turns green as its rule is met. The server checks the same rules again. --}}
            <ul class="pw-checklist" data-pw-checklist aria-label="Password requirements" aria-live="polite">
                <li data-rule="length">12 to 64 characters</li>
                <li data-rule="uppercase">An uppercase letter (A-Z)</li>
                <li data-rule="lowercase">A lowercase letter (a-z)</li>
                <li data-rule="number">A number (0-9)</li>
                <li data-rule="special">A special character, like ! @ # $ or %</li>
                <li data-rule="spaces">No spaces</li>
                <li data-rule="different">Different from your current password</li>
                <li data-rule="birthday">Does not contain your birthday</li>
                <li data-rule="name">Does not contain your first or last name</li>
                <li data-rule="identity">Does not contain your username or ID number</li>
                <li data-rule="common">Not a common password (like Password123!)</li>
                <li data-rule="pattern">No simple patterns (123456, abcdef, qwerty, aaaa)</li>
                <li data-rule="match">Both new passwords match</li>
            </ul>

            <button type="submit" class="auth-btn auth-btn--spaced" data-loading-text="Updating password..." data-pw-submit disabled>
                <i class="bi bi-check2-circle" aria-hidden="true"></i>
                <span class="auth-btn-label">Update Password</span>
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="auth-footer-links">
            @csrf
            <button type="submit" class="auth-link">
                <i class="bi bi-box-arrow-left" aria-hidden="true"></i>
                Sign Out
            </button>
        </form>

    </section>

</div>

@endsection

@push('scripts')
<script>
// Mirrors App\Support\PasswordPolicy so the checklist matches the server. The server always checks again.
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('force-password-change-form');
    if (!form) return;

    const policy = JSON.parse(form.dataset.policy);
    const current = form.querySelector('#current_password');
    const password = form.querySelector('#password');
    const confirm = form.querySelector('#password_confirmation');
    const submit = form.querySelector('[data-pw-submit]');
    const meter = form.querySelector('[data-pw-meter]');
    const meterText = form.querySelector('[data-pw-meter-text]');
    const items = [...form.querySelectorAll('[data-pw-checklist] [data-rule]')];

    const containsAny = (text, list) => (list || []).some((piece) => piece && text.includes(String(piece)));
    const undoLookalikes = (text) => [...text].map((c) => policy.lookalikes[c] ?? c).join('');
    const hasSimplePattern = (lower) => {
        if (/(.)\1{3,}/u.test(lower)) return true;
        return Object.entries(policy.sequences).some(([run, length]) =>
            [run, [...run].reverse().join('')].some((seq) => {
                for (let i = 0; i + length <= seq.length; i++) if (lower.includes(seq.slice(i, i + length))) return true;
                return false;
            }));
    };

    function check() {
        const value = password.value;
        const lower = value.toLowerCase();
        const compact = lower.replace(/[^\p{L}\p{N}]+/gu, '');
        const length = [...value].length;
        const personal = policy.personal || {};

        const met = {
            length: length >= policy.min && length <= policy.max,
            uppercase: /\p{Lu}/u.test(value),
            lowercase: /\p{Ll}/u.test(value),
            number: /\p{N}/u.test(value),
            special: /[^\p{L}\p{N}\s]/u.test(value),
            spaces: value !== '' && !/\s/u.test(value),
            different: value !== '' && value !== current.value,
            birthday: value !== '' && !containsAny(compact, personal.birthday),
            name: value !== '' && !containsAny(compact, personal.name),
            identity: value !== '' && !containsAny(compact, personal.identity),
            common: value !== '' && !containsAny(lower, policy.common) && !containsAny(undoLookalikes(lower), policy.common),
            pattern: value !== '' && !hasSimplePattern(lower),
            match: value !== '' && value === confirm.value,
        };

        items.forEach((item) => {
            const ok = met[item.dataset.rule];
            item.classList.toggle('is-met', ok);
            item.setAttribute('aria-label', `${item.textContent.trim()}: ${ok ? 'done' : 'not yet'}`);
        });

        // Strength: weak until every rule passes; strong when long or very mixed.
        const rulesOk = Object.entries(met).every(([rule, ok]) => rule === 'match' || ok);
        const symbols = (value.match(/[^\p{L}\p{N}\s]/gu) || []).length;
        const level = value === '' ? '' : !rulesOk ? 'weak' : (length >= 16 || (length >= 14 && symbols >= 2)) ? 'strong' : 'medium';
        meter.dataset.level = level;
        meterText.textContent = `Strength: ${{ '': 'not set', weak: 'weak', medium: 'medium', strong: 'strong' }[level]}`;

        submit.disabled = current.disabled || current.value === '' || !Object.values(met).every(Boolean);
    }

    [current, password, confirm].forEach((input) => input.addEventListener('input', check));
    check();
});
</script>
@endpush
