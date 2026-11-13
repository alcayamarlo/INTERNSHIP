/* SkillBridge landing: sticky section nav, step controls for the CSS bridge stage, FAQ tabs.
   No libraries. Copy comes from the markup (data-* attributes and the page text itself). */
(function () {
    'use strict';
    var $ = function (s, r) { return (r || document).querySelector(s); };
    var $$ = function (s, r) { return [].slice.call((r || document).querySelectorAll(s)); };

    // ---- Steps: the three buttons drive the bridge stage (CSS only) ----
    var steps = $('[data-steps]');
    if (steps) {
        var CAPS = ['Step 1 · Foundations', 'Step 2 · The arch rises', 'Step 3 · The span closes'];
        var btns = $$('[data-step]', steps), cap = $('[data-stage-cap]', steps), bars = $$('.sb-stage-bars i', steps);
        var setStage = function (n) {
            steps.setAttribute('data-stage', String(n));
            btns.forEach(function (b, i) { b.setAttribute('aria-pressed', i === n - 1 ? 'true' : 'false'); });
            bars.forEach(function (b, i) { b.classList.toggle('is-on', i < n); });
            if (cap) cap.textContent = CAPS[n - 1];
        };
        btns.forEach(function (b, i) { b.addEventListener('click', function () { setStage(i + 1); }); });
        setStage(1);
    }

    // ---- FAQ: questions left, selected answer right ----
    var faq = $('[data-faq]');
    if (faq) {
        var tabs = $$('[role="tab"]', faq), panels = $$('[role="tabpanel"]', faq);
        var pick = function (i, focus) {
            tabs.forEach(function (t, k) { t.setAttribute('aria-selected', k === i ? 'true' : 'false'); t.tabIndex = k === i ? 0 : -1; });
            panels.forEach(function (p, k) { p.hidden = k !== i; });
            if (focus) tabs[i].focus();
        };
        tabs.forEach(function (t, i) {
            t.addEventListener('click', function () { pick(i); });
            t.addEventListener('keydown', function (e) {
                var n = null;
                if (e.key === 'ArrowDown' || e.key === 'ArrowRight') n = (i + 1) % tabs.length;
                else if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') n = (i - 1 + tabs.length) % tabs.length;
                else if (e.key === 'Home') n = 0; else if (e.key === 'End') n = tabs.length - 1;
                if (n !== null) { e.preventDefault(); pick(n, true); }
            });
        });
    }

    // ---- Sticky compact nav: appears once the hero is out of view; the section being read is highlighted ----
    var sticky = $('[data-sticky-nav]'), hero = $('.public-hero');
    if (sticky && hero && 'IntersectionObserver' in window) {
        new IntersectionObserver(function (e) { sticky.classList.toggle('is-on', !e[0].isIntersecting); }, { threshold: 0.02 }).observe(hero);
        var links = {};
        $$('.sb-sticky-links a', sticky).forEach(function (a) { links[a.getAttribute('href').slice(1)] = a; });
        var spy = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (!en.isIntersecting || !links[en.target.id]) return;
                Object.keys(links).forEach(function (k) { links[k].classList.remove('is-active'); links[k].removeAttribute('aria-current'); });
                links[en.target.id].classList.add('is-active'); links[en.target.id].setAttribute('aria-current', 'true');
            });
        }, { rootMargin: '-45% 0px -50% 0px' });
        Object.keys(links).forEach(function (id) { var el = document.getElementById(id); if (el) spy.observe(el); });
    }

    // ---- Depth: the hero photo, headline and the card stack move at different speeds with the pointer (fine pointers only) ----
    var hero = $('.public-hero');
    if (hero && window.matchMedia && matchMedia('(hover: hover) and (pointer: fine)').matches && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
        var raf = 0, nx = 0, ny = 0;
        var paint = function () { raf = 0; hero.style.setProperty('--px', nx.toFixed(3)); hero.style.setProperty('--py', ny.toFixed(3)); };
        hero.addEventListener('pointermove', function (e) {
            var r = hero.getBoundingClientRect();
            nx = ((e.clientX - r.left) / r.width - .5) * 2; ny = ((e.clientY - r.top) / r.height - .5) * 2;
            if (!raf) raf = requestAnimationFrame(paint);
        });
        hero.addEventListener('pointerleave', function () { nx = 0; ny = 0; if (!raf) raf = requestAnimationFrame(paint); });
    }
})();
