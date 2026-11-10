/**
 * Form modals (Bootstrap 5 modal): `.modal[data-sb-form-modal]` wrapping one form.
 * Bootstrap supplies focus trap, ESC/backdrop close, scroll lock and focus return to the trigger.
 * This adds: unsaved-input warning, form reset on close, double-submit guard,
 * and re-opening on load (`data-sb-open-on-load`) when the server returned validation errors.
 */
(function () {
    'use strict';

    function snapshot(form) {
        return JSON.stringify(Array.from(new FormData(form).entries()).map(function (entry) {
            var value = entry[1];
            return [entry[0], value instanceof File ? value.name + ':' + value.size : value];
        }));
    }

    document.querySelectorAll('.modal[data-sb-form-modal]').forEach(function (modalEl) {
        var form = modalEl.querySelector('form');
        if (!form || !window.bootstrap) return;

        var initial = '';
        var submitting = false;

        modalEl.addEventListener('shown.bs.modal', function () {
            initial = snapshot(form);
            var target = form.querySelector('.is-invalid') || form.querySelector('input:not([type=hidden]), select, textarea');
            if (target) target.focus();
        });

        modalEl.addEventListener('hide.bs.modal', function (event) {
            if (submitting) return;
            if (snapshot(form) !== initial && !window.confirm('You have unsaved changes. Discard them?')) {
                event.preventDefault();
            }
        });

        modalEl.addEventListener('hidden.bs.modal', function () {
            if (!submitting) form.reset();
        });

        function setBusy(busy) {
            document.querySelectorAll('button[type=submit][form="' + form.id + '"], button[type=submit]').forEach(function (button) {
                if (button.form === form) {
                    button.disabled = busy;
                    if (busy) {
                        button.setAttribute('aria-busy', 'true');
                    } else {
                        button.removeAttribute('aria-busy');
                    }
                }
            });
        }

        form.addEventListener('submit', function () {
            submitting = true;
            setBusy(true);
        });

        // Forms the page sends in the background (fetch) report back with events on the form:
        // 'sb:form-failed' unlocks it so the user can fix the errors and send again,
        // 'sb:form-saved' clears it and closes the modal without the unsaved-changes prompt.
        form.addEventListener('sb:form-failed', function () {
            submitting = false;
            setBusy(false);
        });

        form.addEventListener('sb:form-saved', function () {
            submitting = false;
            setBusy(false);
            form.reset();
            initial = snapshot(form);
            window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        });

        if (modalEl.hasAttribute('data-sb-open-on-load')) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    });
})();
