/* Pointer tilt (max 6deg) for the featured card and the hero status tags. Fine pointers only; off for reduced motion. */
(function () {
    'use strict';
    if (!window.matchMedia) return;
    if (!matchMedia('(hover: hover) and (pointer: fine)').matches) return;
    if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var targets = [].slice.call(document.querySelectorAll('.sb-feature, .sb-hero-tags li'));
    if (!targets.length) return;
    var MAX = 6, RANGE = 360, px = 0, py = 0, frame = 0;
    function clamp(v) { return Math.max(-1, Math.min(1, v)); }
    function paint() {
        frame = 0;
        targets.forEach(function (el) {
            var r = el.getBoundingClientRect();
            el.style.setProperty('--ry', (clamp((px - (r.left + r.width / 2)) / RANGE) * MAX).toFixed(2) + 'deg');
            el.style.setProperty('--rx', (-clamp((py - (r.top + r.height / 2)) / RANGE) * MAX).toFixed(2) + 'deg');
        });
    }
    document.addEventListener('pointermove', function (e) {
        px = e.clientX; py = e.clientY;
        if (!frame) frame = requestAnimationFrame(paint);
    }, { passive: true });
    document.documentElement.addEventListener('pointerleave', function () {
        targets.forEach(function (el) { el.style.setProperty('--rx', '0deg'); el.style.setProperty('--ry', '0deg'); });
    });
})();
