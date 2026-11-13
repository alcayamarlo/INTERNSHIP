/* SkillBridge: live 3D bridge that builds in three stages (Three.js 0.160.0, procedural geometry only).
   Stage 1: piers + deck. Stage 2: arch + hangers. Stage 3: the final span, in crimson. */
import * as THREE from './vendor/three.module.min.js';

const COLORS = { bg: 0xf3e9e1, ink: 0x2a2522, steel: 0x5b504b, signal: 0xc8102e, water: 0xe6d6ca, edge: 0x1c1816 };
const LABELS = ['Build your profile', 'Develop competencies', 'Explore opportunities'];
const DURATION = 800;            // ms per stage
const MAX_TILT = 6 * Math.PI / 180;
const BREATH = 0.6 * Math.PI / 180;

const clamp = (v, a = 0, b = 1) => Math.min(b, Math.max(a, v));
const ease = (t) => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2);

function createScene(container) {
  const canvas = document.createElement('canvas');
  const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, powerPreference: 'low-power' });
  renderer.setClearColor(COLORS.bg);
  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(32, 2, 0.1, 1500);
  const target = new THREE.Vector3(0, 2.7, 0);

  scene.add(new THREE.AmbientLight(0xffffff, 1.15));
  const sun = new THREE.DirectionalLight(0xffffff, 1.6);
  sun.position.set(6, 11, 8);
  scene.add(sun);

  const geos = [], mats = [];
  const std = (c) => { const m = new THREE.MeshStandardMaterial({ color: c, roughness: 0.9, metalness: 0, flatShading: true }); mats.push(m); return m; };
  const flat = (c) => { const m = new THREE.MeshBasicMaterial({ color: c }); mats.push(m); return m; };   // water: one flat, unlit tone
  const M = { ink: std(COLORS.ink), steel: std(COLORS.steel), signal: std(COLORS.signal), water: flat(COLORS.water) };
  const edgeMat = new THREE.LineBasicMaterial({ color: COLORS.edge }); mats.push(edgeMat);

  // A mesh with thin dark edge lines as a child, so both scale together.
  function part(geo, mat, edges = true) {
    geos.push(geo);
    const mesh = new THREE.Mesh(geo, mat);
    if (edges) { const eg = new THREE.EdgesGeometry(geo); geos.push(eg); mesh.add(new THREE.LineSegments(eg, edgeMat)); }
    scene.add(mesh);
    return mesh;
  }

  const water = part(new THREE.PlaneGeometry(1000, 1000), M.water, false);
  water.rotation.x = -Math.PI / 2;

  const DECK_Y = 2.3, PIER_X = 5.2, SPAN_X = 1.6, ZW = 1.7;
  const archY = (x) => DECK_Y + 0.14 + 4.0 * (1 - (x / PIER_X) ** 2);

  // Stage 1: piers and the two side decks (origin at the base / outer end so they grow from there).
  const piers = [-1, 1].map((s) => {
    const m = part(new THREE.BoxGeometry(0.9, 2.7, ZW).translate(0, 1.35, 0), M.ink);
    m.position.set(s * PIER_X, -0.4, 0); return m;
  });
  const sideLen = 6.2 - SPAN_X;
  const decks = [-1, 1].map((s) => {
    const m = part(new THREE.BoxGeometry(sideLen, 0.28, ZW).translate(-s * sideLen / 2, 0, 0), M.steel);
    m.position.set(s * 6.2, DECK_Y, 0); return m;
  });

  // Stage 2: the arch (two ribs built from straight segments, growing from both ends) and the outer hangers.
  const N = 28, ribs = [];
  [-0.72, 0.72].forEach((z) => {
    for (let i = 0; i < N; i++) {
      const x0 = -PIER_X + (2 * PIER_X * i) / N, x1 = -PIER_X + (2 * PIER_X * (i + 1)) / N;
      const y0 = archY(x0), y1 = archY(x1);
      const m = part(new THREE.BoxGeometry(Math.hypot(x1 - x0, y1 - y0) + 0.02, 0.26, 0.26), M.ink);
      m.position.set((x0 + x1) / 2, (y0 + y1) / 2, z);
      m.rotation.z = Math.atan2(y1 - y0, x1 - x0);
      m.userData.order = Math.min(i, N - 1 - i);
      ribs.push(m);
    }
  });
  const hangers = [];
  for (let k = 0; k < 9; k++) {
    const x = -4.4 + 1.1 * k, inner = Math.abs(x) < SPAN_X, h = archY(x) - (DECK_Y + 0.14);
    [-0.72, 0.72].forEach((z) => {
      const m = part(new THREE.BoxGeometry(0.07, h, 0.07).translate(0, h / 2, 0), inner ? M.signal : M.steel);
      m.position.set(x, DECK_Y + 0.14, z);
      m.userData = { inner, order: inner ? 0 : Math.min(k, 8 - k) };
      hangers.push(m);
    });
  }

  // Stage 3: the final span, crimson.
  const span = part(new THREE.BoxGeometry(2 * SPAN_X, 0.28, ZW), M.signal);

  // Progress per stage (0..1). Stages animate in order: a stage only starts once the one before it is mostly done.
  const level = [0, 0, 0], goal = [0, 0, 0];
  function applyLevels() {
    const [p1, p2, p3] = level;
    piers.forEach((m) => { m.scale.y = Math.max(ease(clamp(p1 * 2)), 0.0001); m.visible = p1 > 0; });
    decks.forEach((m) => { m.scale.x = Math.max(ease(clamp((p1 - 0.4) / 0.6)), 0.0001); m.visible = p1 > 0.4; });
    ribs.forEach((m) => { const t = ease(clamp(p2 * (N / 2 + 4) - m.userData.order)); m.scale.setScalar(Math.max(t, 0.0001)); m.visible = t > 0.001; });
    hangers.forEach((m) => {
      const t = m.userData.inner ? ease(clamp((p3 - 0.35) * 1.8)) : ease(clamp((p2 - 0.45) * 2.2 - m.userData.order * 0.12));
      m.scale.y = Math.max(t, 0.0001); m.visible = t > 0.001;
    });
    const e = ease(clamp(p3 * 1.2));
    span.scale.x = Math.max(e, 0.0001); span.position.set(0, DECK_Y + (1 - e) * 0.9, 0); span.visible = p3 > 0;
  }
  const settled = () => level.every((v, i) => v === goal[i]);
  function step(dt) {
    for (let i = 0; i < 3; i++) {
      if (level[i] < goal[i] && (i === 0 || level[i - 1] >= 0.6)) level[i] = Math.min(goal[i], level[i] + dt / DURATION);
      else if (level[i] > goal[i] && (i === 2 || level[i + 1] <= 0.4)) level[i] = Math.max(goal[i], level[i] - dt / DURATION);
    }
    applyLevels();
  }
  applyLevels();

  // Camera: fixed three-quarter view; pointer parallax (max 6deg) plus a very slow breath.
  let radius = 18, azim = 0.62, polar = 1.36, tx = 0, ty = 0, cx = 0, cy = 0;
  function placeCamera(time, parallax) {
    cx += (tx - cx) * 0.06; cy += (ty - cy) * 0.06;
    const breath = parallax ? Math.sin(time / 1000 * 0.5) * BREATH : 0;
    const a = azim + cx + breath, p = clamp(polar + cy, 0.6, 1.5);
    camera.position.set(target.x + radius * Math.sin(p) * Math.sin(a), target.y + radius * Math.cos(p), target.z + radius * Math.sin(p) * Math.cos(a));
    camera.lookAt(target);
  }
  function resize() {
    const w = Math.max(1, container.clientWidth), h = Math.max(1, container.clientHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.setSize(w, h, false);
    camera.aspect = w / h;
    const hf = 2 * Math.atan(Math.tan(THREE.MathUtils.degToRad(camera.fov) / 2) * camera.aspect);
    radius = Math.max(18, 14 / (2 * Math.tan(hf / 2)));
    camera.updateProjectionMatrix();
  }
  container.appendChild(canvas);
  const render = (time = 0, parallax = false) => { placeCamera(time, parallax); renderer.render(scene, camera); };

  function dispose() {
    geos.forEach((g) => g.dispose()); mats.forEach((m) => m.dispose());
    renderer.dispose(); canvas.remove();
  }
  return { canvas, level, goal, step, settled, applyLevels, resize, render, dispose, loop: (fn) => renderer.setAnimationLoop(fn), setTilt: (x, y) => { tx = x; ty = y; } };
}

function boot(root) {
  const stage = root.querySelector('[data-bridge-stage]');
  const buttons = [...root.querySelectorAll('[data-bridge-btn]')];
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
  let gl = null;
  try { gl = createScene(stage); } catch (err) { gl = null; }
  if (!gl) return;                       // no WebGL: the static SVG of the finished bridge stays visible

  root.classList.add('is-live');
  gl.canvas.setAttribute('role', 'img');
  let current = 0, running = false, visible = false, last = 0;

  const describe = (n) => 'Stylised arch bridge in three stages. Stage ' + n + ' of 3: ' + LABELS[n - 1] + '.';
  function setStage(n, instant) {
    current = n;
    for (let i = 0; i < 3; i++) gl.goal[i] = i < n ? 1 : 0;
    buttons.forEach((b, i) => b.setAttribute('aria-pressed', String(i === n - 1)));
    gl.canvas.setAttribute('aria-label', describe(n));
    if (instant || reduce.matches) { for (let i = 0; i < 3; i++) gl.level[i] = gl.goal[i]; gl.applyLevels(); gl.render(); }
  }
  function frame(time) {
    const dt = Math.min(64, last ? time - last : 16); last = time;
    if (!gl.settled()) gl.step(dt);
    gl.render(time, !reduce.matches);
  }
  function update() {
    const should = visible && !document.hidden && !reduce.matches;
    if (should && !running) { running = true; last = 0; gl.loop(frame); }
    else if (!should && running) { running = false; gl.loop(null); }
  }

  buttons.forEach((b, i) => b.addEventListener('click', () => setStage(i + 1)));
  const ro = new ResizeObserver(() => { gl.resize(); if (!running) gl.render(); }); ro.observe(stage);
  const io = new IntersectionObserver((e) => { visible = e[0].isIntersecting; update(); if (visible && !current) setStage(reduce.matches ? 3 : 1); }, { threshold: 0.15 });
  io.observe(stage);
  const onVis = () => update();
  const onMove = (e) => {
    if (reduce.matches || e.pointerType === 'touch') return;
    gl.setTilt(clamp((e.clientX / innerWidth - 0.5) * 2, -1, 1) * MAX_TILT, clamp((e.clientY / innerHeight - 0.5) * 2, -1, 1) * -MAX_TILT * 0.6);
  };
  const onReduce = () => { if (reduce.matches) { setStage(current || 3, true); gl.setTilt(0, 0); } update(); };
  document.addEventListener('visibilitychange', onVis);
  window.addEventListener('pointermove', onMove, { passive: true });
  reduce.addEventListener('change', onReduce);
  gl.resize();
  gl.canvas.setAttribute('aria-label', 'Stylised arch bridge, not yet built.');
  if (reduce.matches) setStage(3, true);
  update();
  if (!running) gl.render();

  window.addEventListener('pagehide', () => {
    io.disconnect(); ro.disconnect(); document.removeEventListener('visibilitychange', onVis);
    window.removeEventListener('pointermove', onMove); reduce.removeEventListener('change', onReduce);
    gl.loop(null); gl.dispose();
  }, { once: true });
}

const root = document.querySelector('[data-sb-bridge]');
if (root) boot(root);
