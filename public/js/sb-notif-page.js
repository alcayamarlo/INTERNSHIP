/* Notifications page helpers (no requests of their own): row click, "N selected", toast auto-hide,
   the "..." menu on small screens, and hiding "Mark all read" when nothing is unread. */
(function () {
    'use strict';
    var toastTimer = null;

    /* Whole row opens the notification: reuse the existing link, so the existing handler marks it read first. */
    document.addEventListener('click', function (e) {
        if (e.defaultPrevented || e.button !== 0 || !e.target.closest) return;
        var row = e.target.closest('.nt-card [data-notification-item]');
        if (!row) return;
        if (e.target.closest('a, button, input, label, form, select, textarea')) return;
        var link = row.querySelector('a.notification-open-link');
        if (link) link.click();
    });

    /* "N selected" label next to the select-all checkbox. */
    function syncSelected() {
        var label = document.querySelector('[data-nt-selcount]');
        if (!label) return;
        var n = document.querySelectorAll('[data-notification-select]:checked').length;
        label.textContent = n ? n + ' selected' : '';
    }
    document.addEventListener('change', function (e) {
        if (e.target.matches && e.target.matches('[data-notification-select], [data-notification-select-all]')) {
            // The existing handler syncs the boxes first; read the result on the next tick.
            window.setTimeout(syncSelected, 0);
        }
    });
    // Bulk actions clear the checkboxes without a change event.
    document.addEventListener('submit', function () { window.setTimeout(syncSelected, 600); }, true);

    /* Toast: hide 4 seconds after the existing code shows a message; the X dismisses it. */
    function watchToast() {
        var feedback = document.querySelector('[data-notification-feedback]');
        if (!feedback || feedback.dataset.ntWatched) return;
        feedback.dataset.ntWatched = '1';
        new MutationObserver(function () {
            window.clearTimeout(toastTimer);
            if (!feedback.hidden) toastTimer = window.setTimeout(function () { feedback.hidden = true; }, 4000);
        }).observe(feedback, { attributes: true, attributeFilter: ['hidden'], childList: true, characterData: true, subtree: true });
    }
    document.addEventListener('click', function (e) {
        if (!e.target.closest) return;
        if (e.target.closest('[data-nt-toast-close]')) {
            var f = document.querySelector('[data-notification-feedback]');
            if (f) f.hidden = true;
        }
        /* "..." menu (below 900px). */
        var toggle = e.target.closest('[data-nt-more-toggle]');
        var wrap = document.querySelector('[data-nt-more]');
        if (toggle && wrap) {
            var open = wrap.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        } else if (wrap && wrap.classList.contains('is-open') && !e.target.closest('[data-nt-more]')) {
            wrap.classList.remove('is-open');
            var t = wrap.querySelector('[data-nt-more-toggle]');
            if (t) t.setAttribute('aria-expanded', 'false');
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        var wrap = document.querySelector('[data-nt-more].is-open');
        if (wrap) {
            wrap.classList.remove('is-open');
            var t = wrap.querySelector('[data-nt-more-toggle]');
            if (t) { t.setAttribute('aria-expanded', 'false'); t.focus(); }
        }
    });

    /* "Mark all read" only while something is unread (follows the bell badge the existing code updates). */
    function watchUnread() {
        var readAll = document.querySelector('[data-nt-readall]');
        var bell = document.querySelector('[aria-controls="sbNotificationPanel"]');
        if (!readAll || !bell) return;
        function sync() {
            var badge = bell.querySelector('[data-unread-badge]');
            readAll.hidden = !(badge && !badge.hidden && badge.textContent.trim() !== '');
        }
        new MutationObserver(sync).observe(bell, { childList: true, characterData: true, subtree: true, attributes: true, attributeFilter: ['hidden'] });
    }

    function init() { watchToast(); watchUnread(); syncSelected(); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
