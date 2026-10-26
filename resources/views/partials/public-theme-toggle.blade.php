{{-- Put this RIGHT AFTER the navbar include (@include('partials.public-nav')), not at the end of the page,
     so the switch appears together with the navbar. It adds the button to .public-nav-actions
     (or fills in an existing [data-landing-theme-toggle] button). --}}
<script>
    (function () {
        var ICONS = '<svg class="icon-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A8.5 8.5 0 1 1 11.2 3a6.6 6.6 0 0 0 9.8 9.8Z"></path></svg>'
                  + '<svg class="icon-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"></path></svg>';
        if (!document.querySelector('[data-landing-theme-toggle]')) {
            var actions = document.querySelector('.public-nav-actions');
            if (actions) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'landing-theme-toggle';
                btn.setAttribute('data-landing-theme-toggle', '');
                actions.insertBefore(btn, actions.firstChild);
            }
        }
        [].forEach.call(document.querySelectorAll('[data-landing-theme-toggle]'), function (b) {
            if (!b.querySelector('svg')) b.innerHTML = ICONS;
        });
        if (window.sbLandingTheme) window.sbLandingTheme.sync();
    })();
</script>