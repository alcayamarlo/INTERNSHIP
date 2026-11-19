/*
 * Two-factor pages: "Print" for the recovery codes, and dismissing the reminder banner for this browser.
 * Both are conveniences; without storage the banner simply comes back.
 */
(function () {
    'use strict';

    document.querySelectorAll('[data-recovery-print]').forEach(function (button) {
        button.addEventListener('click', function () {
            window.print();
        });
    });

    var banner = document.querySelector('[data-two-factor-banner]');
    if (!banner) {
        return;
    }
    var key = 'sb-two-factor-banner-dismissed';
    try {
        if (window.localStorage.getItem(key) === banner.getAttribute('data-banner-kind')) {
            banner.hidden = true;
        }
    } catch (e) { /* storage unavailable: show it */ }

    var close = banner.querySelector('[data-banner-close]');
    if (close) {
        close.addEventListener('click', function () {
            banner.hidden = true;
            try {
                window.localStorage.setItem(key, banner.getAttribute('data-banner-kind'));
            } catch (e) { /* ignore */ }
        });
    }
})();
