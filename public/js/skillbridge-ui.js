(() => {
    'use strict';

    const root = document.documentElement;
    const storageKey = 'skillbridge-theme';
    const themeQuery = window.matchMedia('(prefers-color-scheme: dark)');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const syncThemeButtons = () => {
        const isDark = root.dataset.theme === 'dark' || (!root.dataset.theme && themeQuery.matches);
        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            button.setAttribute('aria-pressed', String(isDark));
            button.setAttribute('aria-label', `Switch to ${isDark ? 'light' : 'dark'} mode`);
            const icon = button.querySelector('i');
            if (icon) icon.className = isDark ? 'bi bi-sun' : 'bi bi-moon-stars';
            const svgIcon = button.querySelector('[data-theme-icon]');
            if (svgIcon) {
                svgIcon.innerHTML = isDark
                    ? '<circle cx="12" cy="12" r="4"></circle><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"></path>'
                    : '<path d="M20.9 13A9 9 0 0 1 11 3.1 9 9 0 1 0 20.9 13Z"></path>';
            }
        });
    };

    try {
        const savedTheme = window.localStorage.getItem(storageKey);
        if (savedTheme === 'dark' || savedTheme === 'light') root.dataset.theme = savedTheme;
    } catch {
        // Leave the system preference in effect when storage is unavailable.
    }

    const initialize = () => {
        syncThemeButtons();
        themeQuery.addEventListener('change', () => {
            if (!root.dataset.theme) syncThemeButtons();
        });

        document.addEventListener('click', (event) => {
            const button = event.target instanceof Element ? event.target.closest('[data-theme-toggle]') : null;
            if (!button) return;

            const current = root.dataset.theme || (themeQuery.matches ? 'dark' : 'light');
            root.dataset.theme = current === 'dark' ? 'light' : 'dark';
            try {
                window.localStorage.setItem(storageKey, root.dataset.theme);
            } catch {
                // The selected theme remains active until this page is closed.
            }
            syncThemeButtons();
        });

        const publicNavigation = document.getElementById('publicNavigation');
        const publicNavigationToggle = document.querySelector('[data-public-nav-toggle]');
        if (publicNavigation && publicNavigationToggle) {
            publicNavigationToggle.addEventListener('click', () => {
                const isOpen = publicNavigation.classList.toggle('is-open');
                publicNavigationToggle.setAttribute('aria-expanded', String(isOpen));
            });
            publicNavigation.querySelectorAll('a[href^="#"]').forEach((link) => {
                link.addEventListener('click', () => {
                    publicNavigation.classList.remove('is-open');
                    publicNavigationToggle.setAttribute('aria-expanded', 'false');
                });
            });
            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape' || !publicNavigation.classList.contains('is-open')) return;
                publicNavigation.classList.remove('is-open');
                publicNavigationToggle.setAttribute('aria-expanded', 'false');
                publicNavigationToggle.focus();
            });
        }

        const progress = document.createElement('div');
        progress.className = 'sb-scroll-progress';
        progress.setAttribute('aria-hidden', 'true');
        document.body.append(progress);

        let progressFrame = 0;
        const updateProgress = () => {
            if (progressFrame) return;
            progressFrame = window.requestAnimationFrame(() => {
                progressFrame = 0;
                const scrollable = document.documentElement.scrollHeight - window.innerHeight;
                progress.style.transform = `scaleX(${scrollable > 0 ? Math.min(1, window.scrollY / scrollable) : 0})`;
            });
        };
        window.addEventListener('scroll', updateProgress, { passive: true });
        window.addEventListener('resize', updateProgress, { passive: true });
        updateProgress();

        const grain = document.createElement('div');
        grain.className = 'sb-film-grain';
        grain.setAttribute('aria-hidden', 'true');
        document.body.append(grain);

        if (!reduceMotion && window.matchMedia('(pointer: fine)').matches) {
            const glow = document.createElement('div');
            glow.className = 'sb-mouse-glow';
            glow.setAttribute('aria-hidden', 'true');
            document.body.append(glow);

            let glowFrame = 0;
            document.addEventListener('pointermove', (event) => {
                if (glowFrame) return;
                glowFrame = window.requestAnimationFrame(() => {
                    glowFrame = 0;
                    glow.style.left = `${event.clientX}px`;
                    glow.style.top = `${event.clientY}px`;
                });
            }, { passive: true });
        }

        if (!reduceMotion && 'IntersectionObserver' in window) {
            const revealTargets = document.querySelectorAll(
                '.skillbridge-public section, .skillbridge-app #sbContent .card, .skillbridge-app #sbContent [data-reveal], .skillbridge-guest .login-card, .skillbridge-guest .register-page'
            );
            const revealObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                });
            }, { threshold: 0.08 });

            revealTargets.forEach((target) => {
                target.classList.add('sb-reveal');
                revealObserver.observe(target);
            });
        }

        document.querySelectorAll(
            '.skillbridge-public .public-step-card, .skillbridge-public .public-role-card, .skillbridge-public .public-faq-item, .skillbridge-app #sbContent .card'
        ).forEach((card) => {
            if (getComputedStyle(card).position === 'static') card.style.position = 'relative';
            const spotlight = document.createElement('span');
            spotlight.className = 'sb-card-spotlight';
            spotlight.setAttribute('aria-hidden', 'true');
            card.prepend(spotlight);
            card.classList.add('sb-has-spotlight');
            card.addEventListener('pointermove', (event) => {
                const bounds = card.getBoundingClientRect();
                card.style.setProperty('--sb-spot-x', `${event.clientX - bounds.left}px`);
                card.style.setProperty('--sb-spot-y', `${event.clientY - bounds.top}px`);
            }, { passive: true });
        });

        document.addEventListener('submit', (event) => {
            if (event.defaultPrevented || !(event.target instanceof HTMLFormElement)) return;
            if (event.target.dataset.noLoading === 'true') return;
            const button = event.submitter instanceof HTMLButtonElement
                ? event.submitter
                : event.target.querySelector('button[type="submit"]');
            if (!button || button.querySelector('.sb-submit-spinner')) return;

            const spinner = document.createElement('span');
            spinner.className = 'sb-submit-spinner';
            spinner.setAttribute('aria-hidden', 'true');
            button.prepend(spinner);
            button.setAttribute('aria-busy', 'true');
        });

        window.addEventListener('pageshow', () => {
            document.querySelectorAll('button[aria-busy="true"] .sb-submit-spinner').forEach((spinner) => spinner.remove());
            document.querySelectorAll('button[aria-busy="true"]').forEach((button) => button.removeAttribute('aria-busy'));
        });

        document.querySelectorAll('[data-count-up]').forEach((element) => {
            const endValue = Number(element.dataset.countUp);
            if (!Number.isFinite(endValue)) return;
            if (reduceMotion) {
                element.textContent = String(endValue);
                return;
            }

            const initialValue = Number(element.textContent.replace(/[^\d.-]/g, ''));
            const startValue = Number.isFinite(initialValue) ? initialValue : 0;
            const startTime = performance.now();
            const tick = (now) => {
                const progress = Math.min(1, (now - startTime) / 800);
                const eased = 1 - (1 - progress) ** 3;
                element.textContent = String(Math.round(startValue + (endValue - startValue) * eased));
                if (progress < 1) window.requestAnimationFrame(tick);
            };
            window.requestAnimationFrame(tick);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();
