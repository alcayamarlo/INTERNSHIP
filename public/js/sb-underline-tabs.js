/* Underline tabs: [role=tablist].sb-utabs with .sb-utab buttons (aria-controls = panel id) and .sb-upanel panels.
   Arrow keys, Home and End move between tabs; the selected tab is kept in the URL hash; with no hash, the tab that
   holds a validation error opens first. All panels stay in the DOM (only hidden), so forms and scripts keep working. */
(function () {
    var list = document.querySelector('.sb-utabs[role="tablist"]');
    if (!list) return;
    var tabs = [].slice.call(list.querySelectorAll('.sb-utab'));
    var ids = tabs.map(function (t) { return t.getAttribute('aria-controls'); });
    var panel = function (id) { return document.getElementById(id); };

    function show(id, opts) {
        opts = opts || {};
        if (ids.indexOf(id) === -1) return;
        tabs.forEach(function (tab) {
            var on = tab.getAttribute('aria-controls') === id;
            tab.setAttribute('aria-selected', on ? 'true' : 'false');
            tab.tabIndex = on ? 0 : -1;
            panel(tab.getAttribute('aria-controls')).hidden = !on;
        });
        if (opts.hash && location.hash !== '#' + id) history.replaceState(null, '', '#' + id);
        if (opts.focus) tabs[ids.indexOf(id)].focus();
    }

    tabs.forEach(function (tab, i) {
        tab.addEventListener('click', function () { show(tab.getAttribute('aria-controls'), { hash: true }); });
        tab.addEventListener('keydown', function (e) {
            var next = { ArrowRight: i + 1, ArrowLeft: i - 1, Home: 0, End: tabs.length - 1 }[e.key];
            if (next === undefined) return;
            e.preventDefault();
            show(ids[(next + tabs.length) % tabs.length], { hash: true, focus: true });
        });
    });
    window.addEventListener('hashchange', function () { show(location.hash.slice(1)); });

    var fromHash = location.hash.slice(1);
    var start = ids.indexOf(fromHash) !== -1 ? fromHash : null;
    if (!start) {
        var withError = ids.filter(function (id) { return panel(id).querySelector('.is-invalid, .invalid-feedback.d-block, .alert-danger'); })[0];
        start = withError || ids[0];
    }
    show(start);
})();
