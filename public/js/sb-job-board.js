/**
 * Landing-page job board: client-side search + chips over the cards already rendered by
 * partials/job-board.blade.php, a selected-detail pane (desktop) and a bottom sheet (mobile),
 * and the hero search that feeds the board. Vanilla JS, no dependencies.
 */
(function () {
    'use strict';

    var root = document.querySelector('[data-jb]');
    var heroForm = document.querySelector('[data-jb-hero-search]');
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var mobile = window.matchMedia('(max-width: 991.98px)');

    function norm(value) { return (value || '').toLowerCase().trim(); }
    function all(scope, selector) { return Array.prototype.slice.call(scope.querySelectorAll(selector)); }

    function scrollToBoard() {
        var section = document.getElementById('internships');
        if (section) section.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
    }

    if (!root) {
        // No board (no open internships): the hero search just jumps to the section.
        if (heroForm) heroForm.addEventListener('submit', function (e) { e.preventDefault(); scrollToBoard(); });
        return;
    }

    var what = root.querySelector('[data-jb-what]');
    var where = root.querySelector('[data-jb-where]');
    var form = root.querySelector('[data-jb-search]');
    var cards = all(root, '[data-jb-card]');
    var chips = all(root, '[data-jb-chip]');
    var details = all(root, '[data-jb-detail]');
    var count = root.querySelector('[data-jb-count]');
    var clearButtons = all(root, '[data-jb-clear]');
    var list = root.querySelector('[data-jb-list]');
    var noMatch = root.querySelector('[data-jb-nomatch]');
    var panel = root.querySelector('[data-jb-panel]');
    var placeholder = root.querySelector('[data-jb-placeholder]');
    var closeButton = root.querySelector('[data-jb-close]');
    var backdrop = root.querySelector('[data-jb-backdrop]');
    var headerClear = root.querySelector('.jb-results [data-jb-clear]');
    var selected = null;
    var lastTrigger = null;

    function activeValues(group) {
        return chips.filter(function (c) { return c.dataset.group === group && c.getAttribute('aria-pressed') === 'true'; })
            .map(function (c) { return c.dataset.value; });
    }

    function showDetail(card) {
        details.forEach(function (d) { d.hidden = !card || d.dataset.id !== card.dataset.id; });
        if (placeholder) placeholder.hidden = !!card;
        if (panel) panel.scrollTop = 0;
    }

    function select(card, opts) {
        opts = opts || {};
        selected = card;
        cards.forEach(function (c) {
            var on = c === card;
            c.classList.toggle('is-selected', on);
            var btn = c.querySelector('[data-jb-select]');
            if (btn) { if (on) btn.setAttribute('aria-current', 'true'); else btn.removeAttribute('aria-current'); }
        });
        showDetail(card);
        if (card && opts.open && mobile.matches) openSheet();
    }

    function apply() {
        var q = norm(what.value), place = norm(where.value);
        var setups = activeValues('setup'), durations = activeValues('duration'), locations = activeValues('location');
        var shown = 0, first = null;

        cards.forEach(function (card) {
            var d = card.dataset;
            var ok = (!q || d.text.indexOf(q) > -1)
                && (!place || d.where.indexOf(place) > -1)
                && (!setups.length || setups.indexOf(d.setup) > -1)
                && (!durations.length || durations.indexOf(d.duration) > -1)
                && (!locations.length || locations.indexOf(d.location) > -1);
            card.hidden = !ok;
            if (ok) { shown++; if (!first) first = card; }
        });

        count.textContent = shown + (shown === 1 ? ' internship' : ' internships') + ' found';
        if (list) list.hidden = shown === 0;
        if (noMatch) noMatch.hidden = shown !== 0;
        var filtered = !!(q || place || setups.length || durations.length || locations.length);
        if (headerClear) headerClear.hidden = !filtered;

        if (selected && selected.hidden) { select(null); closeSheet(); }
        if (!selected && first && !mobile.matches) select(first);
        if (!shown) select(null);
    }

    function clearAll() {
        what.value = '';
        where.value = '';
        chips.forEach(function (c) { c.setAttribute('aria-pressed', 'false'); });
        apply();
        what.focus();
    }

    /* ---- mobile bottom sheet ---- */
    function trap(e) {
        if (e.key === 'Escape') { closeSheet(); return; }
        if (e.key !== 'Tab') return;
        var focusables = all(panel, 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])').filter(function (el) { return el.offsetParent !== null; });
        if (!focusables.length) return;
        var firstEl = focusables[0], lastEl = focusables[focusables.length - 1];
        if (e.shiftKey && document.activeElement === firstEl) { e.preventDefault(); lastEl.focus(); }
        else if (!e.shiftKey && document.activeElement === lastEl) { e.preventDefault(); firstEl.focus(); }
    }

    function openSheet() {
        panel.classList.add('is-open');
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-modal', 'true');
        if (backdrop) backdrop.hidden = false;
        document.documentElement.classList.add('jb-lock');
        document.addEventListener('keydown', trap);
        if (closeButton) closeButton.focus();
    }

    function closeSheet() {
        if (!panel.classList.contains('is-open')) return;
        panel.classList.remove('is-open');
        panel.removeAttribute('role');
        panel.removeAttribute('aria-modal');
        if (backdrop) backdrop.hidden = true;
        document.documentElement.classList.remove('jb-lock');
        document.removeEventListener('keydown', trap);
        if (lastTrigger) lastTrigger.focus();
    }

    /* ---- events ---- */
    cards.forEach(function (card) {
        var btn = card.querySelector('[data-jb-select]');
        btn.addEventListener('click', function () { lastTrigger = btn; select(card, { open: true }); });
    });
    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            chip.setAttribute('aria-pressed', chip.getAttribute('aria-pressed') === 'true' ? 'false' : 'true');
            apply();
        });
    });
    clearButtons.forEach(function (b) { b.addEventListener('click', clearAll); });
    what.addEventListener('input', apply);
    where.addEventListener('input', apply);
    form.addEventListener('submit', function (e) { e.preventDefault(); apply(); if (list && !list.hidden) { var wrap = root.querySelector('[data-jb-listwrap]'); if (wrap) wrap.scrollTop = 0; } });
    if (closeButton) closeButton.addEventListener('click', closeSheet);
    if (backdrop) backdrop.addEventListener('click', closeSheet);
    var onViewportChange = function () { if (!mobile.matches) closeSheet(); apply(); };
    if (mobile.addEventListener) mobile.addEventListener('change', onViewportChange); else mobile.addListener(onViewportChange);

    /* ---- hero search feeds the board ---- */
    if (heroForm) {
        heroForm.addEventListener('submit', function (e) {
            e.preventDefault();
            what.value = heroForm.elements.what.value;
            where.value = heroForm.elements.where.value;
            apply();
            scrollToBoard();
        });
    }

    // Prefill from ?what=&where= (the hero form also works as a plain GET link).
    try {
        var params = new URLSearchParams(window.location.search);
        if (params.get('what')) what.value = params.get('what');
        if (params.get('where')) where.value = params.get('where');
    } catch (err) { /* old browser: ignore */ }

    apply();
})();
