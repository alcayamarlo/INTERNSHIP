/*
 * Idle sign-out warning (App\Http\Middleware\EndIdleSession). One minute before the session would end, this asks
 * the server how much time is really left (another tab may have been active), shows a warning with
 * "Stay signed in", and sends the person to the sign-in page once the server has ended the session.
 */
(function () {
    'use strict';

    var root = document.querySelector('[data-idle-timeout]');
    if (!root) {
        return;
    }

    var statusUrl = root.getAttribute('data-status-url');
    var keepAliveUrl = root.getAttribute('data-keep-alive-url');
    var loginUrl = root.getAttribute('data-login-url');
    var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var warnBefore = 60;
    var timer = null;
    var countdown = null;
    var panel = null;

    function schedule(secondsLeft) {
        window.clearTimeout(timer);
        timer = window.setTimeout(check, Math.max(5, secondsLeft - warnBefore) * 1000);
    }

    function signedOut() {
        window.location.href = loginUrl;
    }

    function check() {
        fetch(statusUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (response) {
                if (response.status === 401 || response.status === 419) {
                    signedOut();
                    return null;
                }
                return response.ok ? response.json() : null;
            })
            .then(function (data) {
                if (!data) {
                    return;
                }
                if (data.seconds_left > warnBefore + 5) {
                    hide();
                    schedule(data.seconds_left);
                } else {
                    show(data.seconds_left);
                }
            })
            .catch(function () {
                schedule(warnBefore * 2);
            });
    }

    function show(secondsLeft) {
        if (!panel) {
            panel = document.createElement('div');
            panel.className = 'sb-idle-warning';
            panel.setAttribute('role', 'alertdialog');
            panel.setAttribute('aria-live', 'assertive');
            panel.setAttribute('aria-labelledby', 'sbIdleTitle');
            panel.innerHTML = '<strong id="sbIdleTitle">Still there?</strong>'
                + '<p class="mb-2">You will be signed out in <span data-idle-seconds></span> seconds because there has been no activity.</p>'
                + '<button type="button" class="btn btn-sm btn-primary" data-idle-stay>Stay signed in</button>';
            document.body.appendChild(panel);
            panel.querySelector('[data-idle-stay]').addEventListener('click', stay);
        }
        panel.hidden = false;
        panel.querySelector('[data-idle-stay]').focus();
        var left = Math.max(0, secondsLeft);
        var label = panel.querySelector('[data-idle-seconds]');
        label.textContent = String(left);
        window.clearInterval(countdown);
        countdown = window.setInterval(function () {
            left -= 1;
            label.textContent = String(Math.max(0, left));
            if (left <= 0) {
                window.clearInterval(countdown);
                check();
            }
        }, 1000);
    }

    function hide() {
        window.clearInterval(countdown);
        if (panel) {
            panel.hidden = true;
        }
    }

    function stay() {
        fetch(keepAliveUrl, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (response) {
            if (response.status === 401 || response.status === 419) {
                signedOut();
                return null;
            }
            return response.json();
        }).then(function (data) {
            if (data) {
                hide();
                schedule(data.seconds_left);
            }
        }).catch(function () {
            check();
        });
    }

    schedule(parseInt(root.getAttribute('data-seconds-left'), 10) || 1800);
})();
