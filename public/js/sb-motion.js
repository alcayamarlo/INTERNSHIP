// Shared motion for signed-in pages (see public/css/sb-motion.css).
// Fills progress bars, score bars, level bars, rings and stepper lines from empty to their real
// value once, the first time each one is visible. Runs only when the layout added html.sb-anim
// (motion allowed); without it every value is already shown. Elements added later (live updates,
// tabs, dialogs) are picked up too.
(() => {
    'use strict';

    const root = document.documentElement;
    window.sbMotionReady = true;
    if (!root.classList.contains('sb-anim')) return;
    if (!('IntersectionObserver' in window) || !('MutationObserver' in window)) {
        root.classList.add('sb-anim-done');
        return;
    }

    const selector = '.progress-bar, .sb-score-track > span, .sb-level-fill, .sb-ring, .sb-stepper';
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-filled');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.15 });

    const watch = (node) => {
        if (node.nodeType !== Node.ELEMENT_NODE) return;
        if (node.matches(selector) && !node.classList.contains('is-filled')) observer.observe(node);
        node.querySelectorAll(selector).forEach((element) => {
            if (!element.classList.contains('is-filled')) observer.observe(element);
        });
    };

    const start = () => {
        watch(document.body);
        new MutationObserver((records) => {
            records.forEach((record) => record.addedNodes.forEach(watch));
        }).observe(document.body, { childList: true, subtree: true });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }
})();
