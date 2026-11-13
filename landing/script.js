(function () {
  'use strict';

  // ---- Chips: toggle aria-pressed (state only, no filtering) ----
  document.querySelectorAll('.chip').forEach(function (chip) {
    chip.addEventListener('click', function () {
      chip.setAttribute('aria-pressed', chip.getAttribute('aria-pressed') === 'true' ? 'false' : 'true');
    });
  });

  // ---- Header search: toggle button and "/" shortcut (not wired to results) ----
  var toggle = document.getElementById('searchToggle');
  var form = document.getElementById('navSearch');
  var input = document.getElementById('navSearchInput');
  function setSearch(open) {
    form.hidden = !open;
    toggle.setAttribute('aria-expanded', String(open));
    if (open) input.focus(); else toggle.focus();
  }
  toggle.addEventListener('click', function () { setSearch(form.hidden); });
  form.addEventListener('submit', function (e) { e.preventDefault(); });
  document.addEventListener('keydown', function (e) {
    var t = e.target, typing = t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.isContentEditable);
    if (e.key === '/' && !typing && !e.metaKey && !e.ctrlKey && !e.altKey) { e.preventDefault(); setSearch(true); }
    else if (e.key === 'Escape' && !form.hidden) { setSearch(false); }
  });

  // ---- Internships search form: not wired ----
  var board = document.getElementById('boardSearch');
  if (board) board.addEventListener('submit', function (e) { e.preventDefault(); });

  // ---- Pointer tilt (max 6deg): featured card and hero status labels. Fine pointers only. ----
  if (!window.matchMedia) return;
  if (!matchMedia('(hover: hover) and (pointer: fine)').matches) return;
  if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  var MAX = 6, RANGE = 360, px = 0, py = 0, frame = 0;
  var targets = [].slice.call(document.querySelectorAll('[data-tilt], .stamp'));
  function clamp(v) { return Math.max(-1, Math.min(1, v)); }
  function paint() {
    frame = 0;
    targets.forEach(function (el) {
      var r = el.getBoundingClientRect();
      var nx = clamp((px - (r.left + r.width / 2)) / RANGE);
      var ny = clamp((py - (r.top + r.height / 2)) / RANGE);
      el.style.setProperty('--ry', (nx * MAX).toFixed(2) + 'deg');
      el.style.setProperty('--rx', (-ny * MAX).toFixed(2) + 'deg');
    });
  }
  document.addEventListener('pointermove', function (e) {
    px = e.clientX; py = e.clientY;
    if (!frame) frame = requestAnimationFrame(paint);
  }, { passive: true });
  document.addEventListener('pointerleave', function () {
    targets.forEach(function (el) { el.style.setProperty('--rx', '0deg'); el.style.setProperty('--ry', '0deg'); });
  });
})();
