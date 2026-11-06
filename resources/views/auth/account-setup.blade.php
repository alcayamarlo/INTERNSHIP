@extends('layouts.guest')

@section('title', 'Set Up Your Account')
@section('subtitle', 'Add your Gmail, verify it, and set a new password')
@section('body_class', 'auth-page')

@push('styles')
<style>
    /* First-login setup: 3-step indicator and 6-box code input. Colors come from the existing auth theme. */
    .setup-steps { display: flex; align-items: center; gap: 8px; margin: 0 0 clamp(14px, 2.4vh, 22px); padding: 0; list-style: none; }
    .setup-steps li { display: flex; align-items: center; gap: 8px; color: rgba(255, 255, 255, .6); font-size: 12.5px; font-weight: 700; }
    .setup-steps li + li::before { content: ""; width: 22px; height: 1px; background: rgba(255, 255, 255, .3); }
    .setup-steps .dot { width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid rgba(255, 255, 255, .4); border-radius: 50%; font-size: 12px; }
    .setup-steps li.is-current { color: #fff; }
    .setup-steps li.is-current .dot { border-color: #ffc2c8; background: rgba(225, 29, 46, .85); color: #fff; }
    .setup-steps li.is-done { color: #ffc2c8; }
    .setup-steps li.is-done .dot { border-color: #ffc2c8; color: #ffc2c8; }
    @media (max-width: 420px) { .setup-steps .label { display: none; } .setup-steps li.is-current .label { display: inline; } }

    [data-setup-step][hidden] { display: none; }

    .setup-code { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: clamp(6px, 1.6vw, 10px); }
    .setup-code-box { width: 100%; min-width: 0; padding: 10px 0; border: 0; border-bottom: 1px solid rgba(255, 255, 255, .55); background: transparent; color: #fff; font: inherit; font-size: 22px; font-weight: 700; text-align: center; caret-color: #fff; outline: none; border-radius: 0; }
    .setup-code-box:focus { border-bottom-color: #fff; box-shadow: 0 1px 0 0 #fff; }
    .setup-code.is-invalid .setup-code-box { border-bottom-color: #ffb3bb; box-shadow: 0 1px 0 0 #ffb3bb; }
    .setup-code.is-shake { animation: setup-shake .36s ease; }
    @keyframes setup-shake { 0%, 100% { transform: translateX(0); } 20% { transform: translateX(-7px); } 40% { transform: translateX(7px); } 60% { transform: translateX(-4px); } 80% { transform: translateX(4px); } }
    @media (prefers-reduced-motion: reduce) { .setup-code.is-shake { animation: none; } }

    .auth-btn .sb-submit-spinner { flex: none; }
</style>
@endpush

@section('content')

@php
    $dashboardUrl = auth()->check() ? route(auth()->user()->dashboardRoute()) : route('login');
@endphp

<div class="auth-pane" id="setupFlow" data-dashboard-url="{{ $dashboardUrl }}">

    @include('auth.partials.top', ['secure' => 'Secure Account'])

    <section class="auth-card" aria-labelledby="auth-title">

        @include('auth.partials.mobile-brand')

        <ol class="setup-steps" aria-label="Account setup progress">
            <li data-progress="1"><span class="dot">1</span><span class="label">Gmail</span></li>
            <li data-progress="2"><span class="dot">2</span><span class="label">Verify</span></li>
            <li data-progress="3"><span class="dot">3</span><span class="label">Password</span></li>
        </ol>

        {{-- ================= STEP 1: ADD GMAIL ================= --}}
        <div data-setup-step="1" hidden>
            <header class="auth-heading">
                <h1>Add your <span>Gmail.</span></h1>
                <p>We'll send a verification code to confirm it's really you.</p>
            </header>

            <form id="gmailForm" novalidate data-no-loading="true">
                <div class="auth-field">
                    <label for="gmail" class="auth-label">Gmail Address <span class="auth-required" aria-hidden="true">*</span></label>
                    <div class="auth-input-wrap">
                        <i class="bi bi-envelope auth-input-icon" aria-hidden="true"></i>
                        <input id="gmail" type="email" inputmode="email" autocomplete="email" maxlength="255"
                               placeholder="juan.delacruz@gmail.com" class="auth-input" aria-describedby="gmailError" required>
                    </div>
                    <div id="gmailError" class="auth-error" role="alert" hidden>
                        <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i><span></span>
                    </div>
                </div>

                <button type="submit" class="auth-btn" data-loading-text="Sending code...">
                    <span class="auth-btn-label">Send Code</span>
                </button>
            </form>
        </div>

        {{-- ================= STEP 2: ENTER THE CODE ================= --}}
        <div data-setup-step="2" hidden>
            <header class="auth-heading">
                <h1>Enter the <span>code.</span></h1>
                <p>We sent a 6-digit code to <strong data-masked-email></strong>. Enter it below to continue.</p>
            </header>

            <div class="auth-alert auth-alert--success" role="status" data-resent hidden>
                <i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>A new code was sent.</span>
            </div>

            <form id="codeForm" novalidate data-no-loading="true">
                <div class="auth-field">
                    <div class="auth-label-row">
                        <label for="code-1" class="auth-label">Six-Digit Code <span class="auth-required" aria-hidden="true">*</span></label>
                        <span class="auth-countdown" role="timer" data-expiry><i class="bi bi-clock" aria-hidden="true"></i><span data-expiry-text>10:00</span></span>
                    </div>
                    <div class="setup-code" data-code-boxes role="group" aria-label="Six-digit code">
                        @for ($i = 1; $i <= 6; $i++)
                            <input id="code-{{ $i }}" class="setup-code-box" type="text" inputmode="numeric" maxlength="1"
                                   autocomplete="{{ $i === 1 ? 'one-time-code' : 'off' }}" aria-label="Digit {{ $i }}">
                        @endfor
                    </div>
                    <div id="codeError" class="auth-error" role="alert" hidden>
                        <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i><span></span>
                    </div>
                    <div class="auth-code-actions">
                        <span>Didn't get it? <button type="button" class="auth-link" data-resend disabled>Resend code</button> <span data-resend-note></span></span>
                        <button type="button" class="auth-link" data-change-email>Change email</button>
                    </div>
                </div>

                <button type="submit" class="auth-btn auth-btn--spaced" data-loading-text="Verifying..." data-verify disabled>
                    <span class="auth-btn-label">Verify Code</span>
                </button>
            </form>
        </div>

        {{-- ================= STEP 3: SET A NEW PASSWORD ================= --}}
        <div data-setup-step="3" hidden>
            <header class="auth-heading">
                <h1>Set a new <span>password.</span></h1>
                <p>Your account was created with a temporary password. Choose a new one to continue.</p>
            </header>

            <div class="auth-alert auth-alert--success" role="status" data-success hidden>
                <i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>Password updated. Taking you to your dashboard...</span>
            </div>

            <form id="passwordForm" novalidate data-no-loading="true">
                @foreach ([
                    ['newPassword', 'New Password', 'Enter a new password'],
                    ['confirmPassword', 'Confirm New Password', 'Confirm your new password'],
                ] as [$id, $label, $placeholder])
                    <div class="auth-field">
                        <label for="{{ $id }}" class="auth-label">{{ $label }} <span class="auth-required" aria-hidden="true">*</span></label>
                        <div class="auth-input-wrap auth-input-wrap--password">
                            <i class="bi bi-lock auth-input-icon" aria-hidden="true"></i>
                            <input id="{{ $id }}" type="password" autocomplete="new-password" maxlength="255"
                                   placeholder="{{ $placeholder }}" class="auth-input" aria-describedby="{{ $id }}Error" required>
                            <button type="button" class="auth-toggle" data-password-toggle="{{ $id }}" aria-controls="{{ $id }}"
                                    aria-label="Show password" aria-pressed="false" title="Show password">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div id="{{ $id }}Error" class="auth-error" role="alert" hidden>
                            <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i><span></span>
                        </div>
                        @if ($id === 'newPassword')
                            <p class="auth-hint">Use at least 8 characters.</p>
                        @endif
                    </div>
                @endforeach

                <button type="submit" class="auth-btn" data-loading-text="Updating password...">
                    <i class="bi bi-check2-circle" aria-hidden="true"></i>
                    <span class="auth-btn-label">Update Password</span>
                </button>
            </form>
        </div>

    </section>

</div>

@endsection

@push('scripts')
<script>
/*
 * First-login setup flow: FRONT-END ONLY. Nothing here talks to a server.
 * The three mock functions below are the only places to connect to the backend.
 */
(function () {
    'use strict';

    var CODE_TTL = 10 * 60;      // seconds until the code expires
    var RESEND_WAIT = 60;        // seconds before "Resend code" works again
    var STORE_KEY = 'sbAccountSetup';

    // ---------- MOCK API (replace these three with real requests) ----------

    // TODO: connect to the real API. Should email a 6-digit code to the given Gmail address.
    function sendVerificationCode(email) {
        return new Promise(function (resolve) { window.setTimeout(function () { resolve({ ok: true }); }, 1000); });
    }

    // TODO: replace with the real API. Mock rule for testing: only 123456 is valid.
    function verifyCode(code) {
        return new Promise(function (resolve) { window.setTimeout(function () { resolve({ ok: code === '123456' }); }, 1000); });
    }

    // TODO: connect to the real API. Should save the new password and clear the "must change password" flag.
    function updatePassword(password) {
        return new Promise(function (resolve) { window.setTimeout(function () { resolve({ ok: true }); }, 1000); });
    }

    // ---------- shared flow state (email + verified flag) ----------
    // TODO: once the backend exists, this state should come from the server session instead of sessionStorage.
    var state = { email: '', verified: false, codeSentAt: 0 };
    try { Object.assign(state, JSON.parse(sessionStorage.getItem(STORE_KEY) || '{}')); } catch (_) {}
    function save() { try { sessionStorage.setItem(STORE_KEY, JSON.stringify(state)); } catch (_) {} }

    var root = document.getElementById('setupFlow');
    var panels = {};
    [].forEach.call(root.querySelectorAll('[data-setup-step]'), function (p) { panels[p.dataset.setupStep] = p; });
    var $ = function (id) { return document.getElementById(id); };

    // ---------- helpers ----------
    function showError(id, msg, input) {
        var el = $(id);
        el.querySelector('span').textContent = msg || '';
        el.hidden = !msg;
        if (input) {
            input.classList.toggle('is-invalid', !!msg);
            input.setAttribute('aria-invalid', msg ? 'true' : 'false');
        }
    }
    function busy(button, on) {
        var label = button.querySelector('.auth-btn-label');
        var spinner = button.querySelector('.sb-submit-spinner');
        if (on) {
            if (!spinner) {
                spinner = document.createElement('span');
                spinner.className = 'sb-submit-spinner';
                spinner.setAttribute('aria-hidden', 'true');
                button.prepend(spinner);
            }
            label.dataset.original = label.textContent;
            label.textContent = button.dataset.loadingText;
            button.classList.add('is-loading');
            button.setAttribute('aria-busy', 'true');
            button.disabled = true;
        } else {
            if (spinner) spinner.remove();
            if (label.dataset.original) label.textContent = label.dataset.original;
            button.classList.remove('is-loading');
            button.removeAttribute('aria-busy');
            button.disabled = false;
        }
    }
    function maskEmail(email) {
        var at = email.indexOf('@');
        var name = email.slice(0, at);
        return name.slice(0, Math.min(2, name.length)) + '***' + email.slice(at);
    }
    function fmt(sec) { return String(Math.floor(sec / 60)).padStart(2, '0') + ':' + String(sec % 60).padStart(2, '0'); }

    // ---------- steps + route guards ----------
    // Step 2 needs an email, step 3 needs a verified code. Anything else goes back to the right earlier step.
    function allowedStep(wanted) {
        if (wanted >= 2 && !state.email) return 1;
        if (wanted >= 3 && !state.verified) return state.email ? 2 : 1;
        return wanted;
    }
    function stepFromHash() { var n = parseInt((location.hash.match(/^#step-(\d)$/) || [])[1], 10); return n >= 1 && n <= 3 ? n : 1; }
    function go(step) {
        var target = allowedStep(step);
        if (location.hash !== '#step-' + target) {
            try { history.replaceState(null, '', location.pathname + location.search + '#step-' + target); } catch (_) {}
        }
        render(target);
    }
    function render(step) {
        Object.keys(panels).forEach(function (k) { panels[k].hidden = Number(k) !== step; });
        [].forEach.call(root.querySelectorAll('[data-progress]'), function (li) {
            var n = Number(li.dataset.progress);
            li.classList.toggle('is-done', n < step);
            li.classList.toggle('is-current', n === step);
            if (n === step) li.setAttribute('aria-current', 'step'); else li.removeAttribute('aria-current');
        });
        if (step === 2) enterCodeStep();
        var first = panels[step].querySelector('input');
        if (first) first.focus({ preventScroll: true });
    }
    window.addEventListener('hashchange', function () { go(stepFromHash()); });

    // ---------- step 1: Gmail ----------
    var gmailForm = $('gmailForm'), gmail = $('gmail');
    if (state.email) gmail.value = state.email;
    gmail.addEventListener('input', function () { showError('gmailError', '', gmail); });
    gmailForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var v = gmail.value.trim().toLowerCase();
        var msg = '';
        if (!v) msg = 'Gmail address is required.';
        else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) msg = 'Enter a valid email address.';
        else if (!/@gmail\.com$/.test(v)) msg = 'Use a Gmail address that ends with @gmail.com.';
        showError('gmailError', msg, gmail);
        if (msg) { gmail.focus(); return; }

        var btn = gmailForm.querySelector('button[type="submit"]');
        busy(btn, true);
        sendVerificationCode(v).then(function () {
            busy(btn, false);
            // A new address starts a fresh code and clears any earlier verification.
            if (v !== state.email) state.verified = false;
            state.email = v;
            state.codeSentAt = Date.now();
            save();
            go(2);
        }).catch(function () {
            busy(btn, false);
            showError('gmailError', 'Could not send the code. Please try again.', gmail);
        });
    });

    // ---------- step 2: code ----------
    var codeForm = $('codeForm');
    var boxes = [].slice.call(codeForm.querySelectorAll('.setup-code-box'));
    var boxWrap = codeForm.querySelector('[data-code-boxes]');
    var verifyBtn = codeForm.querySelector('[data-verify]');
    var resendBtn = codeForm.querySelector('[data-resend]');
    var resendNote = codeForm.querySelector('[data-resend-note]');
    var expiryEl = codeForm.querySelector('[data-expiry]');
    var expiryText = codeForm.querySelector('[data-expiry-text]');
    var expiryTimer = null, resendTimer = null, expired = false;

    function codeValue() { return boxes.map(function (b) { return b.value; }).join(''); }
    function syncVerify() { verifyBtn.disabled = expired || codeValue().length !== 6; }
    function clearError() { showError('codeError', ''); boxWrap.classList.remove('is-invalid'); }
    function clearBoxes() { boxes.forEach(function (b) { b.value = ''; }); syncVerify(); }
    function fill(start, digits) {
        digits.split('').forEach(function (d, i) { if (boxes[start + i]) boxes[start + i].value = d; });
        syncVerify();
        boxes[Math.min(start + digits.length, boxes.length - 1)].focus();
    }
    boxes.forEach(function (box, i) {
        box.addEventListener('input', function () {
            var digits = box.value.replace(/\D/g, '');
            box.value = '';
            if (digits) fill(i, digits.slice(0, boxes.length - i)); else syncVerify();
            clearError();
        });
        box.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && !box.value && i > 0) { boxes[i - 1].value = ''; boxes[i - 1].focus(); syncVerify(); e.preventDefault(); }
            else if (e.key === 'ArrowLeft' && i > 0) boxes[i - 1].focus();
            else if (e.key === 'ArrowRight' && i < boxes.length - 1) boxes[i + 1].focus();
        });
        box.addEventListener('focus', function () { box.select(); });
        box.addEventListener('paste', function (e) {
            var digits = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            if (!digits) return;
            e.preventDefault();
            clearBoxes();
            fill(0, digits);
            clearError();
        });
    });

    function startExpiry() {
        window.clearInterval(expiryTimer);
        function tick() {
            var left = Math.max(0, CODE_TTL - Math.floor((Date.now() - state.codeSentAt) / 1000));
            expired = left === 0;
            expiryEl.classList.toggle('is-expired', expired);
            expiryText.textContent = expired ? 'Code expired' : fmt(left);
            if (expired) {
                window.clearInterval(expiryTimer);
                showError('codeError', 'This code has expired. Request a new one.');
            }
            syncVerify();
        }
        tick();
        expiryTimer = window.setInterval(tick, 1000);
    }
    function startResendWait() {
        window.clearInterval(resendTimer);
        function tick() {
            var left = Math.max(0, RESEND_WAIT - Math.floor((Date.now() - state.codeSentAt) / 1000));
            resendBtn.disabled = left > 0;
            resendNote.textContent = left > 0 ? 'in ' + left + 's' : '';
            if (left === 0) window.clearInterval(resendTimer);
        }
        tick();
        resendTimer = window.setInterval(tick, 1000);
    }
    function enterCodeStep() {
        panels[2].querySelector('[data-masked-email]').textContent = maskEmail(state.email);
        panels[2].querySelector('[data-resent]').hidden = true;
        clearBoxes(); clearError();
        startExpiry();
        startResendWait();
    }

    resendBtn.addEventListener('click', function () {
        resendBtn.disabled = true;
        resendNote.textContent = 'Sending...';
        sendVerificationCode(state.email).then(function () {
            state.codeSentAt = Date.now();
            save();
            clearBoxes(); clearError();
            panels[2].querySelector('[data-resent]').hidden = false;
            startExpiry();
            startResendWait();
            boxes[0].focus();
        }).catch(function () {
            resendNote.textContent = '';
            resendBtn.disabled = false;
            showError('codeError', 'Could not resend the code. Please try again.');
        });
    });
    codeForm.querySelector('[data-change-email]').addEventListener('click', function () {
        state.verified = false;
        save();
        go(1);
    });

    codeForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var code = codeValue();
        if (code.length !== 6 || expired) return;
        busy(verifyBtn, true);
        verifyCode(code).then(function (res) {
            busy(verifyBtn, false);
            if (res.ok) {
                state.verified = true;
                save();
                go(3);
                return;
            }
            syncVerify();
            showError('codeError', 'Invalid code. Please try again.');
            boxWrap.classList.add('is-invalid');
            boxWrap.classList.remove('is-shake');
            void boxWrap.offsetWidth;
            boxWrap.classList.add('is-shake');
            clearBoxes();
            boxes[0].focus();
        }).catch(function () {
            busy(verifyBtn, false);
            showError('codeError', 'Something went wrong. Please try again.');
        });
    });

    // ---------- step 3: new password ----------
    var passwordForm = $('passwordForm'), pw = $('newPassword'), pw2 = $('confirmPassword');
    pw.addEventListener('input', function () { showError('newPasswordError', '', pw); });
    pw2.addEventListener('input', function () { showError('confirmPasswordError', '', pw2); });
    passwordForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var okP = pw.value.length >= 8, okC = pw2.value !== '' && pw2.value === pw.value;
        showError('newPasswordError', pw.value === '' ? 'Enter a new password.' : (okP ? '' : 'Use at least 8 characters.'), pw);
        showError('confirmPasswordError', pw2.value === '' ? 'Confirm your new password.' : (okC ? '' : 'Passwords do not match.'), pw2);
        if (!okP || !okC) { (okP ? pw2 : pw).focus(); return; }

        var btn = passwordForm.querySelector('button[type="submit"]');
        busy(btn, true);
        updatePassword(pw.value).then(function () {
            panels[3].querySelector('[data-success]').hidden = false;
            try { sessionStorage.removeItem(STORE_KEY); } catch (_) {}
            // Leave the button busy while the redirect happens.
            window.setTimeout(function () { window.location.assign(root.dataset.dashboardUrl); }, 1200);
        }).catch(function () {
            busy(btn, false);
            showError('newPasswordError', 'Could not update the password. Please try again.', pw);
        });
    });

    go(stepFromHash());
})();
</script>
@endpush
