/* Company invitation: three steps in one form. Server validation stays the source of truth. */
(function () {
    'use strict';
    var form = document.querySelector('[data-inv-form]');
    if (!form) return;

    var panels = Array.prototype.slice.call(form.querySelectorAll('[data-step]'));
    var dots = Array.prototype.slice.call(document.querySelectorAll('[data-step-dot]'));
    var bar = document.querySelector('[data-inv-bar]');
    var live = document.querySelector('[data-inv-live]');
    var btnBack = form.querySelector('[data-inv-back]');
    var btnNext = form.querySelector('[data-inv-next]');
    var btnSubmit = form.querySelector('[data-inv-submit]');
    var current = 1;
    var submitting = false;
    var names = { 1: 'Company', 2: 'Account', 3: 'Review' };

    function field(name) { return form.querySelector('[name="' + name + '"]'); }
    function val(name) { var f = field(name); return f ? f.value.trim() : ''; }

    /* ----- messages ----- */
    function setError(input, msg) {
        var wrap = input.closest('.inv-f') || input.parentNode;
        var box = wrap.querySelector('.inv-err');
        if (!msg) {
            if (box) box.remove();
            input.classList.remove('is-invalid');
            input.removeAttribute('aria-invalid');
            return;
        }
        if (!box) {
            box = document.createElement('div');
            box.className = 'invalid-feedback inv-err';
            box.setAttribute('role', 'alert');
            wrap.appendChild(box);
        }
        box.textContent = msg;
        input.classList.add('is-invalid');
        input.setAttribute('aria-invalid', 'true');
    }

    function checkInput(input) {
        var v = input.value.trim();
        if (input.required && !v) { setError(input, 'This field is required.'); return false; }
        if (input.type === 'url' && v && !input.checkValidity()) { setError(input, 'Enter a full web address, e.g. https://example.com.'); return false; }
        if (input.name === 'password' && v && v.length < 8) { setError(input, 'Use at least 8 characters.'); return false; }
        if (input.name === 'password_confirmation' && v && v !== field('password').value) { setError(input, 'Passwords do not match.'); return false; }
        setError(input, '');
        return true;
    }

    function validateStep(n) {
        var panel = form.querySelector('[data-step="' + n + '"]');
        var inputs = Array.prototype.slice.call(panel.querySelectorAll('input[name], textarea[name]'));
        var first = null;
        inputs.forEach(function (i) { if (!checkInput(i) && !first) first = i; });
        if (first) first.focus();
        return !first;
    }

    form.addEventListener('blur', function (e) {
        if (e.target.matches && e.target.matches('input[name], textarea[name]') && e.target.value !== '') checkInput(e.target);
    }, true);
    form.addEventListener('input', function (e) {
        if (e.target.classList && e.target.classList.contains('is-invalid')) checkInput(e.target);
        if (e.target.name === 'password') strength(e.target.value);
    });

    /* ----- password ----- */
    function strength(v) {
        var score = 0;
        if (v.length >= 8) score++;
        if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
        if (/\d/.test(v)) score++;
        if (/[^A-Za-z0-9]/.test(v)) score++;
        var bars = form.querySelectorAll('[data-inv-strength] span');
        Array.prototype.forEach.call(bars, function (b, i) { b.className = i < score ? 'on s' + score : ''; });
    }
    Array.prototype.forEach.call(form.querySelectorAll('[data-inv-eye]'), function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-inv-eye'));
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            btn.firstElementChild.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });

    /* ----- steps ----- */
    function fillReview() {
        Array.prototype.forEach.call(form.querySelectorAll('[data-sum]'), function (el) {
            var key = el.getAttribute('data-sum');
            var v = key === 'password' ? (val('password') ? '••••••••' : '') : val(key);
            el.textContent = v || '—';
        });
    }

    function show(n, focus) {
        current = n;
        panels.forEach(function (p) { p.hidden = Number(p.getAttribute('data-step')) !== n; });
        dots.forEach(function (d) {
            var i = Number(d.getAttribute('data-step-dot'));
            d.classList.toggle('is-active', i === n);
            d.classList.toggle('is-done', i < n);
            var dot = d.querySelector('.inv-dot');
            dot.innerHTML = i < n ? '<i class="bi bi-check-lg" aria-hidden="true"></i>' : String(i);
            if (i === n) d.setAttribute('aria-current', 'step'); else d.removeAttribute('aria-current');
        });
        bar.style.width = (n === 1 ? 33 : n === 2 ? 66 : 100) + '%';
        btnBack.hidden = n === 1;
        btnNext.hidden = n === 3;
        btnSubmit.hidden = n !== 3;
        if (n === 3) fillReview();
        if (live) live.textContent = 'Step ' + n + ' of 3: ' + names[n];
        if (focus !== false) {
            var target = panels[n - 1].querySelector('.is-invalid, input:not([readonly]), textarea');
            if (target) target.focus();
            else btnSubmit.focus();
        }
        var body = form.querySelector('.inv-body');
        if (body) body.scrollTop = 0;
    }

    function next() {
        if (current < 3 && validateStep(current)) show(current + 1);
    }
    function back() { if (current > 1) show(current - 1); }

    btnNext.addEventListener('click', next);
    btnBack.addEventListener('click', back);
    Array.prototype.forEach.call(form.querySelectorAll('[data-inv-goto]'), function (b) {
        b.addEventListener('click', function () { show(Number(b.getAttribute('data-inv-goto'))); });
    });
    form.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && current < 3 && e.target.tagName !== 'TEXTAREA' && e.target.tagName !== 'BUTTON') {
            e.preventDefault();
            next();
        }
    });

    form.addEventListener('submit', function (e) {
        if (submitting) { e.preventDefault(); return; }
        if (!validateStep(1)) { e.preventDefault(); show(1); return; }
        if (!validateStep(2)) { e.preventDefault(); show(2); return; }
        submitting = true;
        btnSubmit.disabled = true;
        btnSubmit.classList.add('is-loading');
        btnSubmit.querySelector('[data-inv-submit-text]').textContent = 'Creating account...';
    });

    /* ----- start on the step that has a server error ----- */
    var firstStep = 1;
    for (var i = 0; i < panels.length; i++) {
        if (panels[i].querySelector('.is-invalid')) { firstStep = Number(panels[i].getAttribute('data-step')); break; }
    }
    show(firstStep, firstStep !== 1);
    strength(val('password'));
})();
