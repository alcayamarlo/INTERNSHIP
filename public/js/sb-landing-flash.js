/* After a company account is created: show the banner, open the Sign in modal, prefill the email,
   put the cursor in the password field. The flash is one-time, so a refresh does nothing. Nothing is stored. */
(function () {
    'use strict';
    var banner = document.querySelector('[data-lf-banner]');
    if (!banner) return;

    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var email = banner.getAttribute('data-email') || '';
    var loginUrl = banner.getAttribute('data-login-url') || '';
    var hideTimer = null;

    function hide() {
        window.clearTimeout(hideTimer);
        banner.classList.remove('is-in');
        banner.classList.add('is-out');
        window.setTimeout(function () { if (banner.parentNode) banner.parentNode.removeChild(banner); }, reduce ? 0 : 260);
    }

    // Paint first, then slide the banner in.
    window.requestAnimationFrame(function () { window.requestAnimationFrame(function () { banner.classList.add('is-in'); }); });
    banner.querySelector('[data-lf-close]').addEventListener('click', hide);
    hideTimer = window.setTimeout(hide, 8000);

    // Open the Sign in modal the same way a click on "Sign in" does (the modal binds those links).
    function findSignInLink() {
        var links = document.querySelectorAll('a[href]');
        for (var i = 0; i < links.length; i++) {
            if (links[i].href === loginUrl && !banner.contains(links[i])) return links[i];
        }
        return null;
    }

    function fillAndFocus() {
        var login = document.getElementById('lm-login');
        var password = document.getElementById('lm-password');
        if (login && email) {
            login.value = email;
            login.dispatchEvent(new Event('input', { bubbles: true }));
        }
        if (password) password.focus();
    }

    window.setTimeout(function () {
        var link = findSignInLink();
        if (!link) return;
        link.click();
        var root = document.getElementById('loginModal');
        var tries = 0;
        var wait = window.setInterval(function () {
            tries++;
            if (root && root.classList.contains('is-open')) {
                window.clearInterval(wait);
                // The modal focuses its first field a moment after opening; move on to the password after that.
                window.setTimeout(fillAndFocus, reduce ? 50 : 650);
            } else if (tries > 30) {
                window.clearInterval(wait);
            }
        }, 100);
    }, 400);
})();
