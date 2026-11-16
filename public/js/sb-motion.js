// Shared motion for signed-in pages (see public/css/sb-motion.css).
//  - Fills: progress, score and level bars, rings and stepper lines grow from empty to their real
//    value once, the first time each one is visible.
//  - Count-up: KPI, stat and tile numbers (and [data-count-up]) count from 0 to their real value
//    once, the first time each one is visible. Only plain numbers ("486", "1,204", "89%",
//    "12.5 h"); anything else ("0/2 verified", "4.45 / 5", "—") is shown as it is.
// Runs only when the layout added html.sb-anim (motion allowed); without it every value is shown
// at once. Content opened later (tabs, dialogs, a selected certificate) animates too; content a
// live region refreshes shows its new values at once, so updates never replay the motion.
(() => {
    'use strict';

    const root = document.documentElement;
    window.sbMotionReady = true;
    if (!root.classList.contains('sb-anim')) return;
    if (!('IntersectionObserver' in window) || !('MutationObserver' in window)) {
        root.classList.add('sb-anim-done');
        return;
    }

    const FILLS = '.progress-bar, .sb-score-track > span, .sb-level-fill, .sb-ring, .sb-stepper';
    const COUNTERS = '.sb-kpi-value, .sb-stat-value, .sb-tile-value, [data-count-up]';
    const ALL = `${FILLS}, ${COUNTERS}`;
    const NUMBER = /^(\D*?)(\d{1,3}(?:,\d{3})+|\d+)(\.\d+)?(\D*)$/;
    const DURATION = 600;

    const settle = (element) => element.classList.add(element.matches(COUNTERS) ? 'is-counted' : 'is-filled');

    const countUp = (element) => {
        element.classList.add('is-counted');
        const explicit = element.hasAttribute('data-count-up');
        if (!explicit && element.children.length) return; // a wrapper; its inner number counts itself
        const finalText = element.textContent;
        const match = String(explicit ? element.dataset.countUp : finalText.trim()).match(NUMBER);
        if (!match) return;
        const decimals = match[3] ? match[3].length - 1 : 0;
        const end = Number(match[2].replace(/,/g, '') + (match[3] || ''));
        if (!(end > 0)) return;
        const prefix = explicit ? '' : match[1];
        const suffix = explicit ? '' : match[4];
        const format = (value) => prefix + value.toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
            useGrouping: match[2].includes(','),
        }) + suffix;
        const start = performance.now();
        const tick = (now) => {
            const progress = Math.min(1, (now - start) / DURATION);
            if (progress < 1) {
                element.textContent = format(end * (1 - (1 - progress) ** 3));
                requestAnimationFrame(tick);
            } else {
                element.textContent = finalText;
            }
        };
        element.textContent = format(0);
        requestAnimationFrame(tick);
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            observer.unobserve(entry.target);
            if (entry.target.matches(COUNTERS)) countUp(entry.target);
            else entry.target.classList.add('is-filled');
        });
    }, { threshold: 0.15 });

    const done = (element) => element.classList.contains('is-filled') || element.classList.contains('is-counted');
    const watch = (node, instant) => {
        if (node.nodeType !== Node.ELEMENT_NODE) return;
        const targets = node.matches(ALL) ? [node, ...node.querySelectorAll(ALL)] : [...node.querySelectorAll(ALL)];
        targets.forEach((element) => {
            if (done(element)) return;
            // A 0% bar has nothing to grow (and never reports as visible): show it as it is.
            // Bars inside a hidden tab (no offsetParent) still wait until the tab opens.
            const empty = element.matches(FILLS) && element.offsetParent !== null && element.offsetWidth === 0;
            if (instant || empty) settle(element);
            else observer.observe(element);
        });
    };

    const start = () => {
        watch(document.body, false);
        new MutationObserver((records) => {
            records.forEach((record) => {
                const live = record.target instanceof Element && record.target.closest('[data-live-region]');
                record.addedNodes.forEach((node) => watch(node, Boolean(live)));
            });
        }).observe(document.body, { childList: true, subtree: true });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }
})();
