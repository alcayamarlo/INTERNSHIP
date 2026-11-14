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
        // Hover (mouse), focus (keyboard) and click/tap (touch) all switch the stage; no click needed on desktop.
        btns.forEach(function (b, i) {
            var go = function () { setStage(i + 1); };
            b.addEventListener('mouseenter', go);
            b.addEventListener('focus', go);
            b.addEventListener('click', go);
        });
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

    // ---- Features: role tabs (visual only: local show/hide of three card groups, arrow-key navigation) ----
    var feat = $('[data-features]');
    if (feat) {
        var ftabs = $$('[role="tab"]', feat), fpanels = $$('[role="tabpanel"]', feat);
        var fpick = function (i, focus) {
            ftabs.forEach(function (t, k) { t.setAttribute('aria-selected', k === i ? 'true' : 'false'); t.tabIndex = k === i ? 0 : -1; });
            fpanels.forEach(function (p, k) { p.hidden = k !== i; });
            if (focus) ftabs[i].focus();
        };
        ftabs.forEach(function (t, i) {
            t.addEventListener('click', function () { fpick(i); });
            t.addEventListener('keydown', function (e) {
                var n = null;
                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') n = (i + 1) % ftabs.length;
                else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') n = (i - 1 + ftabs.length) % ftabs.length;
                else if (e.key === 'Home') n = 0; else if (e.key === 'End') n = ftabs.length - 1;
                if (n !== null) { e.preventDefault(); fpick(n, true); }
            });
        });
    }

    // ---- About photo: eases toward the pointer (smoothed every frame; fine pointers only, off with reduced motion) ----
    var aboutPhoto = $('.sb-about-photo');
    if (aboutPhoto && window.matchMedia && matchMedia('(hover: hover) and (pointer: fine)').matches && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
        var tx = 0, ty = 0, cx = 0, cy = 0, running = false;
        var tick = function () {
            cx += (tx - cx) * 0.09; cy += (ty - cy) * 0.09;
            aboutPhoto.style.setProperty('--rx', (-cy * 7).toFixed(3) + 'deg');
            aboutPhoto.style.setProperty('--ry', (cx * 9).toFixed(3) + 'deg');
            aboutPhoto.style.setProperty('--px', (cx * 10).toFixed(2) + 'px');
            if (Math.abs(tx - cx) > 0.002 || Math.abs(ty - cy) > 0.002) requestAnimationFrame(tick); else running = false;
        };
        var kick = function () { if (!running) { running = true; requestAnimationFrame(tick); } };
        aboutPhoto.addEventListener('pointermove', function (e) {
            var r = aboutPhoto.getBoundingClientRect();
            tx = ((e.clientX - r.left) / r.width - .5) * 2; ty = ((e.clientY - r.top) / r.height - .5) * 2; kick();
        });
        aboutPhoto.addEventListener('pointerleave', function () { tx = 0; ty = 0; kick(); });
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

    // ---- Live hero: scroll depth, count-up numbers and a rotating "live feed" highlight on the card stack ----
    if (hero) {
        var still = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (!still) {
            var sRaf = 0;
            var onScroll = function () {
                sRaf = 0;
                var h = hero.offsetHeight || 1, y = Math.min(Math.max(window.scrollY / h, 0), 1);
                hero.style.setProperty('--sy', y.toFixed(3));
            };
            window.addEventListener('scroll', function () { if (!sRaf) sRaf = requestAnimationFrame(onScroll); }, { passive: true });
            onScroll();

            $$('[data-count]', hero).forEach(function (el) {
                var to = parseInt(el.getAttribute('data-count'), 10);
                if (!to || to < 1) return;
                var t0 = null, dur = 1100;
                el.textContent = '0';
                var tick = function (t) {
                    if (t0 === null) t0 = t;
                    var k = Math.min((t - t0) / dur, 1), e = 1 - Math.pow(1 - k, 3);
                    el.textContent = String(Math.round(to * e));
                    if (k < 1) requestAnimationFrame(tick);
                };
                setTimeout(function () { requestAnimationFrame(tick); }, 350);
            });

            var cards = $$('.sb-stack-card', hero), cur = 0;
            if (cards.length) {
                cards[0].classList.add('is-live');
                setInterval(function () {
                    if (document.hidden) return;
                    cards[cur].classList.remove('is-live');
                    cur = (cur + 1) % cards.length;
                    cards[cur].classList.add('is-live');
                }, 2600);
            }
        }
    }

    // ---- Internships popup: every link to the internships list opens it over the landing page ----
    var pop = $('[data-internships-pop]');
    if (pop) {
        var panel = $('.sb-pop-panel', pop), body = $('[data-pop-body]', pop), lastFocus = null, pushed = false, loaded = false;
        var isInternshipsLink = function (a) {
            if (!a || a.hasAttribute('data-no-pop')) return false;
            var h = a.getAttribute('href') || '';
            if (h === '#internships') return true;
            try {
                var u = new URL(h, location.href);
                return u.origin === location.origin && /\/internships\/?$/.test(u.pathname) && !u.search;
            } catch (err) { return false; }
        };
        // Course tabs + count, then show/hide course groups (pure DOM, no reload)
        var wire = function () {
            var tabs = $('[data-course-tabs]', body), groups = $$('[data-course-group]', body);
            var total = $$('.public-job-card', body).length;
            var count = document.createElement('p');
            count.className = 'sb-pop-count';
            count.textContent = total + ' ' + (total === 1 ? 'internship' : 'internships');
            if (tabs) {
                var label = $('.sb-course-jump-label', tabs); if (label) label.remove();
                tabs.appendChild(count);
                var btns = $$('button[data-course]', tabs);
                var show = function (id) {
                    btns.forEach(function (b) { b.setAttribute('aria-pressed', b.getAttribute('data-course') === id ? 'true' : 'false'); });
                    groups.forEach(function (g) { g.hidden = id !== 'all' && g.getAttribute('data-course-group') !== id; });
                    var n = id === 'all' ? total : $$('.public-job-card', groups.filter(function (g) { return g.getAttribute('data-course-group') === id; })[0] || document.createElement('i')).length;
                    count.textContent = n + ' ' + (n === 1 ? 'internship' : 'internships');
                    body.scrollTop = 0;
                };
                btns.forEach(function (b) { b.addEventListener('click', function () { show(b.getAttribute('data-course')); }); });
            } else { body.insertBefore(count, body.firstChild); }
        };
        var load = function () {
            if (loaded) return; loaded = true;
            fetch(pop.getAttribute('data-src'), { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
                .then(function (html) {
                    var doc = new DOMParser().parseFromString(html, 'text/html');
                    var tabs = doc.querySelector('[data-course-tabs]'), secs = [].slice.call(doc.querySelectorAll('[data-course-group]'));
                    body.textContent = '';
                    if (!secs.length) { body.innerHTML = '<p class="sb-pop-note">No open internships right now.</p>'; return; }
                    if (tabs) body.appendChild(document.importNode(tabs, true));
                    secs.forEach(function (sec) { body.appendChild(document.importNode(sec, true)); });
                    $$('.reveal, .sb-reveal', body).forEach(function (n) { n.classList.remove('reveal', 'sb-reveal'); });
                    wire();
                })
                .catch(function () {
                    loaded = false;
                    body.innerHTML = '<p class="sb-pop-note">Could not load the internships. <a href="' + pop.getAttribute('data-src') + '" data-no-pop>Open the full list</a>.</p>';
                });
        };
        var openPop = function (push) {
            if (!pop.hidden) return;
            lastFocus = document.activeElement;
            pop.hidden = false;
            document.documentElement.classList.add('sb-pop-open');
            requestAnimationFrame(function () { pop.classList.add('is-open'); if (panel) panel.focus({ preventScroll: true }); });
            if (push !== false && location.hash !== '#internships') { history.pushState({ sbPop: 1 }, '', '#internships'); pushed = true; }
            load();
        };
        var closePop = function (fromHistory) {
            if (pop.hidden) return;
            pop.classList.remove('is-open');
            document.documentElement.classList.remove('sb-pop-open');
            var done = function () { pop.hidden = true; if (lastFocus && lastFocus.focus) lastFocus.focus({ preventScroll: true }); };
            if (matchMedia('(prefers-reduced-motion: reduce)').matches) done(); else setTimeout(done, 200);
            if (fromHistory !== true && location.hash === '#internships') {
                if (pushed) { pushed = false; history.back(); } else { history.replaceState(null, '', location.pathname + location.search); }
            }
        };
        document.addEventListener('click', function (e) {
            var a = e.target.closest ? e.target.closest('a') : null;
            if (!a || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) return;
            if (isInternshipsLink(a)) { e.preventDefault(); openPop(); }
        });
        $$('[data-pop-close]', pop).forEach(function (el) { el.addEventListener('click', function () { closePop(); }); });
        // ---- Details popup (View details): sits on top of the internships popup ----
        var dpop = $('[data-detail-pop]'), dpanel = dpop && $('.sb-pop-panel', dpop), dFrom = null, dToken = 0;
        var openDetail = function (a) {
            if (!dpop) return;
            var group = a.closest('[data-course-group]'), card = a.closest('.public-job-card');
            var code = group && $('.sb-course-code', group), cname = group && $('h2', group);
            var title = card && $('h3', card);
            var company = card && $('.public-job-company', card);
            dFrom = a;
            var token = ++dToken;
            $('[data-d-eyebrow]', dpop).textContent = [code && code.textContent.trim(), company && company.textContent.trim()].filter(Boolean).join(' • ');
            $('[data-d-title]', dpop).textContent = title ? title.textContent.trim() : 'Internship';
            $('[data-d-by]', dpop).textContent = cname ? 'Open to: ' + cname.textContent.trim() : '';
            var applyBtn = $('[data-d-apply]', dpop); applyBtn.hidden = true;
            var dbody = $('[data-d-body]', dpop); dbody.innerHTML = '<p class="sb-pop-note">Loading details&hellip;</p>'; dbody.scrollTop = 0;
            dpop.hidden = false;
            requestAnimationFrame(function () { dpop.classList.add('is-open'); if (dpanel) dpanel.focus({ preventScroll: true }); });
            fetch(a.href, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
                .then(function (html) {
                    if (token !== dToken) return;
                    var doc = new DOMParser().parseFromString(html, 'text/html');
                    var body2 = doc.querySelector('.public-detail-body');
                    if (!body2) throw new Error('no body');
                    dbody.textContent = '';
                    var facts = doc.querySelectorAll('.sb-detail-side .public-job-meta li');
                    if (facts.length) {
                        var ul = document.createElement('ul'); ul.className = 'sb-d-facts';
                        [].forEach.call(facts, function (li) { var x = document.createElement('li'); x.textContent = li.textContent.replace(/\s+/g, ' ').trim(); ul.appendChild(x); });
                        dbody.appendChild(ul);
                    }
                    if (cname) { var line = document.createElement('p'); line.className = 'sb-d-course'; line.innerHTML = '<strong>Course</strong> '; line.appendChild(document.createTextNode(cname.textContent.trim())); dbody.appendChild(line); }
                    dbody.appendChild(document.importNode(body2, true));
                    var ap = doc.querySelector('.sb-detail-side .public-job-actions a.public-button-primary');
                    if (ap) { applyBtn.href = ap.getAttribute('href'); applyBtn.hidden = false; }
                    var note = doc.querySelector('.sb-detail-side .public-apply-note');
                    if (note) { var n = document.createElement('p'); n.className = 'sb-d-note'; n.textContent = note.textContent.trim(); dbody.appendChild(n); }
                })
                .catch(function () {
                    if (token !== dToken) return;
                    dbody.innerHTML = '<p class="sb-pop-note">Could not load the details. <a href="' + a.href + '" data-no-pop>Open the internship page</a>.</p>';
                });
        };
        var closeDetail = function () {
            if (!dpop || dpop.hidden) return;
            dToken++;
            dpop.classList.remove('is-open');
            var done = function () { dpop.hidden = true; if (dFrom && dFrom.focus) dFrom.focus({ preventScroll: true }); };
            if (matchMedia('(prefers-reduced-motion: reduce)').matches) done(); else setTimeout(done, 200);
        };
        if (dpop) {
            $$('[data-detail-close]', dpop).forEach(function (el) { el.addEventListener('click', closeDetail); });
            pop.addEventListener('click', function (e) {
                var a = e.target.closest ? e.target.closest('.public-job-card a[href]') : null;
                if (!a || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) return;
                if (/\/internships\/\d+\/?$/.test(new URL(a.href, location.href).pathname)) { e.preventDefault(); openDetail(a); }
            });
        }
        document.addEventListener('keydown', function (e) {
            if (pop.hidden) return;
            var top = dpop && !dpop.hidden ? dpop : pop, topPanel = top === pop ? panel : dpanel;
            if (e.key === 'Escape') { e.preventDefault(); if (top === dpop) closeDetail(); else closePop(); return; }
            if (e.key !== 'Tab') return;
            var f = $$('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])', top).filter(function (n) { return n.offsetParent !== null && !n.hidden; });
            if (!f.length) return;
            var first = f[0], last = f[f.length - 1];
            if (e.shiftKey && (document.activeElement === first || document.activeElement === topPanel)) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        });
        window.addEventListener('popstate', function () { if (location.hash === '#internships') openPop(false); else closePop(true); });
        if (location.hash === '#internships') openPop(false);
    }
})();
