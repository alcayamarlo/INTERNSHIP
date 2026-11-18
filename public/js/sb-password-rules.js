/* Live password checklist: ticks each rule as the password meets it. The same rules as App\Support\PasswordPolicy;
   the server checks again on submit, so this only shows progress and never blocks the form. */
(function () {
    'use strict';

    function init(list) {
        var password = document.getElementById(list.getAttribute('data-password'));
        var confirm = document.getElementById(list.getAttribute('data-confirm'));
        if (!password) return;
        var min = Number(list.getAttribute('data-min')) || 8;
        var max = Number(list.getAttribute('data-max')) || 64;
        var items = [].slice.call(list.querySelectorAll('[data-rule]'));

        function check() {
            var v = password.value;
            var n = Array.from(v).length;
            var met = {
                length: n >= min && n <= max,
                uppercase: /\p{Lu}/u.test(v),
                lowercase: /\p{Ll}/u.test(v),
                number: /\p{N}/u.test(v),
                special: /[^\p{L}\p{N}\s]/u.test(v),
                match: v !== '' && !!confirm && v === confirm.value
            };
            items.forEach(function (li) { li.classList.toggle('is-met', !!met[li.getAttribute('data-rule')]); });
        }

        password.addEventListener('input', check);
        if (confirm) confirm.addEventListener('input', check);
        check();
    }

    /* Show / hide button next to a password input (data-pw-eye="inputId"). */
    function initEye(btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-pw-eye'));
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            if (btn.firstElementChild) btn.firstElementChild.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    }

    /* Four-bar strength meter under a password input (data-pw-strength="inputId"), as on the company form. */
    function initStrength(meter) {
        var input = document.getElementById(meter.getAttribute('data-pw-strength'));
        if (!input) return;
        var bars = meter.querySelectorAll('span');
        function paint() {
            var v = input.value, score = 0;
            if (Array.from(v).length >= 8) score++;
            if (/\p{Ll}/u.test(v) && /\p{Lu}/u.test(v)) score++;
            if (/\p{N}/u.test(v)) score++;
            if (/[^\p{L}\p{N}\s]/u.test(v)) score++;
            [].forEach.call(bars, function (b, i) { b.className = i < score ? 'on s' + score : ''; });
        }
        input.addEventListener('input', paint);
        paint();
    }

    function boot() {
        [].forEach.call(document.querySelectorAll('[data-pw-rules]'), init);
        [].forEach.call(document.querySelectorAll('[data-pw-eye]'), initEye);
        [].forEach.call(document.querySelectorAll('[data-pw-strength]'), initStrength);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
