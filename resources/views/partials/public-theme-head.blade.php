{{-- Put inside <head> of every public page, after the CSS links.
     Applies the saved light/dark theme before first paint, makes the switch react instantly,
     and styles the switch button. --}}
<script>
    (function () {
        var KEY = 'sb-landing-theme';
        var root = document.documentElement;

        // 1) Apply the saved theme BEFORE first paint (no flash). Default is light.
        //    To follow the visitor's device setting instead, change 'light' to
        //    (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light').
        var theme = 'light';
        try {
            var saved = localStorage.getItem(KEY);
            if (saved === 'dark' || saved === 'light') theme = saved;
        } catch (e) {}
        root.setAttribute('data-theme', theme);

        function isDark() { return root.getAttribute('data-theme') === 'dark'; }
        function sync() {
            var label = isDark() ? 'Switch to light mode' : 'Switch to dark mode';
            [].forEach.call(document.querySelectorAll('[data-landing-theme-toggle]'), function (btn) {
                btn.setAttribute('aria-pressed', isDark() ? 'true' : 'false');
                btn.setAttribute('aria-label', label);
                btn.setAttribute('title', label);
            });
        }

        // 2) Swap colors instantly: transitions are switched off for the swap, then restored.
        function apply(next) {
            root.classList.add('theme-switching');
            root.setAttribute('data-theme', next);
            void root.offsetHeight; // force the new colors to be applied right now
            requestAnimationFrame(function () {
                requestAnimationFrame(function () { root.classList.remove('theme-switching'); });
            });
        }

        // 3) One delegated listener, registered immediately, so the button works the moment it exists.
        document.addEventListener('click', function (e) {
            var btn = e.target && e.target.closest ? e.target.closest('[data-landing-theme-toggle]') : null;
            if (!btn) return;
            var next = isDark() ? 'light' : 'dark';
            apply(next);
            try { localStorage.setItem(KEY, next); } catch (err) {}
            sync();
        });

        // Keep other open tabs in step.
        window.addEventListener('storage', function (e) {
            if (e.key === KEY && (e.newValue === 'dark' || e.newValue === 'light') && e.newValue !== root.getAttribute('data-theme')) {
                apply(e.newValue);
                sync();
            }
        });

        window.sbLandingTheme = { sync: sync };
    })();
</script>
<style>
    html[data-theme="light"] { color-scheme: light; }
    html[data-theme="dark"] { color-scheme: dark; }
    html.theme-switching *, html.theme-switching *::before, html.theme-switching *::after { transition: none !important; }
    .landing-theme-toggle { display: inline-grid; flex: 0 0 auto; width: 42px; height: 42px; padding: 0; place-items: center; border: 1px solid var(--sb-border); border-radius: 50%; color: var(--sb-ink); background: var(--sb-surface); cursor: pointer; touch-action: manipulation; -webkit-tap-highlight-color: transparent; transition: transform 120ms ease; }
    .landing-theme-toggle:hover { border-color: var(--sb-red); color: var(--sb-brand-content); background: var(--sb-red-soft); }
    .landing-theme-toggle:active { transform: scale(.94); }
    .landing-theme-toggle:focus-visible { outline: 3px solid color-mix(in srgb, var(--sb-brand) 60%, transparent); outline-offset: 3px; }
    .landing-theme-toggle svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.8; pointer-events: none; }
    .landing-theme-toggle .icon-sun { display: none; }
    html[data-theme="dark"] .landing-theme-toggle .icon-moon { display: none; }
    html[data-theme="dark"] .landing-theme-toggle .icon-sun { display: block; }
</style>