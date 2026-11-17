/* Shared behaviour for <x-modal> (native <dialog class="sb-modal">).
   Open: any [data-open="<id>"]. Close: X, Cancel, Esc, backdrop. Focus returns to the trigger,
   page scroll is locked while open, and a dialog with data-reopen opens on load (server validation errors). */
(function () {
    var root = document.documentElement;
    var openers = new WeakMap();

    function open(dialog, trigger) {
        if (!dialog || typeof dialog.showModal !== 'function' || dialog.open) return;
        openers.set(dialog, trigger || document.activeElement);
        root.classList.add('sb-modal-open');
        dialog.showModal();
        var target = dialog.querySelector('.is-invalid') || dialog.querySelector('.sb-modal__body :is(input, select, textarea):not([type="hidden"]):not([readonly]):not([disabled])');
        if (target) target.focus({ preventScroll: true });
    }
    function close(dialog) { if (dialog && dialog.open) dialog.close(); }

    document.addEventListener('click', function (e) {
        var opener = e.target.closest && e.target.closest('[data-open]');
        if (opener) {
            var dialog = document.getElementById(opener.getAttribute('data-open'));
            if (dialog && dialog.matches('[data-sb-modal]')) { e.preventDefault(); open(dialog, opener); return; }
        }
        var closer = e.target.closest && e.target.closest('[data-modal-close]');
        if (closer) { close(closer.closest('dialog')); return; }
        if (e.target.matches && e.target.matches('dialog[data-sb-modal]')) close(e.target); // backdrop
    });

    document.addEventListener('close', function (e) {
        var dialog = e.target;
        if (!dialog.matches || !dialog.matches('dialog[data-sb-modal]')) return;
        if (!document.querySelector('dialog[data-sb-modal][open]')) root.classList.remove('sb-modal-open');
        var opener = openers.get(dialog);
        if (opener && opener.isConnected && opener.focus) opener.focus();
    }, true);

    document.addEventListener('DOMContentLoaded', function () {
        var first = document.querySelector('dialog[data-sb-modal][data-reopen="1"]');
        if (first) open(first, null);
    });
})();

/* Password show/hide buttons ([data-pw-toggle="<input id>"]) and a loading state on the submit button of modal forms. */
(function () {
    document.addEventListener('click', function (e) {
        var btn = e.target.closest && e.target.closest('[data-pw-toggle]');
        if (!btn) return;
        var input = document.getElementById(btn.getAttribute('data-pw-toggle'));
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        var icon = btn.querySelector('i');
        if (icon) icon.className = 'bi ' + (show ? 'bi-eye-slash' : 'bi-eye');
    });

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.closest || !form.closest('dialog[data-sb-modal]')) return;
        var btn = form.querySelector('.sb-modal__footer [type="submit"]');
        if (!btn || btn.dataset.loading) return;
        btn.dataset.loading = '1';
        btn.dataset.original = btn.innerHTML;
        btn.style.minWidth = btn.offsetWidth + 'px';
        // Disable on the next tick so the submit itself still goes through.
        setTimeout(function () { btn.disabled = true; btn.innerHTML = '<span class="sb-modal__spinner" aria-hidden="true"></span>Saving…'; }, 0);
    });
    // Coming back with the back button: make the button usable again.
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        [].forEach.call(document.querySelectorAll('dialog[data-sb-modal] [data-loading]'), function (btn) {
            btn.disabled = false; btn.innerHTML = btn.dataset.original; delete btn.dataset.loading;
        });
    });
})();
