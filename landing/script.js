(function () {
  'use strict';

  // Chips: single-select per group; clicking the selected chip clears it. State only, no filtering.
  document.querySelectorAll('.fgroup').forEach(function (group) {
    var chips = [].slice.call(group.querySelectorAll('.chip'));
    chips.forEach(function (chip) {
      chip.addEventListener('click', function () {
        var wasOn = chip.getAttribute('aria-pressed') === 'true';
        chips.forEach(function (c) { c.setAttribute('aria-pressed', 'false'); });
        if (!wasOn) chip.setAttribute('aria-pressed', 'true');
      });
    });
  });

  // Internships search form: visual only.
  var board = document.querySelector('[data-board-search]');
  if (board) board.addEventListener('submit', function (e) { e.preventDefault(); });

  // Header search button: scrolls to the internships search and focuses "What".
  var s = document.querySelector('[data-search]');
  if (s) s.addEventListener('click', function () {
    var what = document.getElementById('what');
    var sec = document.getElementById('internships');
    if (sec) sec.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    if (what) setTimeout(function () { what.focus({ preventScroll: true }); }, 350);
  });

  // Polish: nav highlight for the section in view, and a gentle reveal.
  if (!('IntersectionObserver' in window)) return;
  var links = {};
  document.querySelectorAll('.pillnav a').forEach(function (a) { links[a.getAttribute('href').slice(1)] = a; });
  var spy = new IntersectionObserver(function (entries) {
    entries.forEach(function (en) {
      var a = links[en.target.id];
      if (!a) return;
      if (en.isIntersecting) {
        Object.keys(links).forEach(function (k) { links[k].removeAttribute('aria-current'); });
        a.setAttribute('aria-current', 'true');
      }
    });
  }, { rootMargin: '-40% 0px -50% 0px' });
  Object.keys(links).forEach(function (id) { var el = document.getElementById(id); if (el) spy.observe(el); });

  var targets = document.querySelectorAll('.panel, .closing, .step, .role, .faq details');
  targets.forEach(function (el) { el.classList.add('reveal'); });
  var rev = new IntersectionObserver(function (entries) {
    entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('in'); rev.unobserve(en.target); } });
  }, { threshold: .08 });
  targets.forEach(function (el) { rev.observe(el); });
})();
