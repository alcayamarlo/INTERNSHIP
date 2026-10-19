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

        // App sidebar: collapse to an icon rail (desktop/tablet) and close the drawer with Escape.
        const rootElement = document.documentElement;
        const collapseButton = document.querySelector('[data-sidebar-collapse]');
        const syncSidebar = () => {
            const collapsed = rootElement.classList.contains('sb-collapsed');
            document.querySelectorAll('.sb-nav-link').forEach((link) => {
                const text = link.querySelector('span:not(.sb-nav-badge)');
                if (collapsed && text) {
                    link.setAttribute('title', text.textContent.trim());
                } else {
                    link.removeAttribute('title');
                }
            });
            if (collapseButton) {
                collapseButton.setAttribute('aria-pressed', String(collapsed));
                collapseButton.setAttribute('aria-label', collapsed ? 'Expand navigation' : 'Collapse navigation');
            }
        };
        syncSidebar();
        collapseButton?.addEventListener('click', () => {
            const collapsed = rootElement.classList.toggle('sb-collapsed');
            try {
                window.localStorage.setItem('skillbridge-sidebar', collapsed ? 'collapsed' : 'expanded');
            } catch {
                // The choice applies until the page is closed.
            }
            syncSidebar();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && document.getElementById('sidebar')?.classList.contains('show')) {
                window.closeSidebar?.();
            }
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

        if (!reduceMotion && 'IntersectionObserver' in window) {
            const revealTargets = document.querySelectorAll(
                '.skillbridge-public section'
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

        // Includes submit buttons placed outside the form but tied to it with form="id".
        const submitButtons = (form) => Array.from(form.elements).filter(
            (element) => element instanceof HTMLButtonElement && element.type === 'submit'
        );

        // Password visibility: <button data-password-toggle="inputId">.
        document.addEventListener('click', (event) => {
            const button = event.target instanceof Element ? event.target.closest('[data-password-toggle]') : null;
            if (!button) return;

            const input = document.getElementById(button.dataset.passwordToggle);
            if (!input) return;

            const reveal = input.type === 'password';
            const label = reveal ? 'Hide password' : 'Show password';
            input.type = reveal ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(reveal));
            button.setAttribute('aria-label', label);
            button.setAttribute('title', label);
            const icon = button.querySelector('i');
            if (icon) icon.className = `bi ${reveal ? 'bi-eye-slash' : 'bi-eye'}`;
        });

        document.addEventListener('submit', (event) => {
            if (!(event.target instanceof HTMLFormElement)) return;
            const form = event.target;
            const isAuthForm = form.hasAttribute('data-auth-form');

            // Auth forms: ignore a second submit while the first is in flight.
            if (isAuthForm && form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            if (event.defaultPrevented || form.dataset.noLoading === 'true') return;
            const button = event.submitter instanceof HTMLButtonElement
                ? event.submitter
                : form.querySelector('button[type="submit"]');
            if (!button || button.querySelector('.sb-submit-spinner')) return;

            const spinner = document.createElement('span');
            spinner.className = 'sb-submit-spinner';
            spinner.setAttribute('aria-hidden', 'true');
            button.prepend(spinner);
            button.setAttribute('aria-busy', 'true');

            if (!isAuthForm) return;

            form.dataset.submitting = 'true';
            button.classList.add('is-loading');

            const label = button.querySelector('.auth-btn-label');
            if (label && button.dataset.loadingText) {
                label.dataset.original = label.textContent;
                label.textContent = button.dataset.loadingText;
            }

            const icon = button.querySelector('i.bi');
            if (icon) icon.hidden = true;

            // Disable after the browser has built the form data, so the request still goes out.
            window.setTimeout(() => {
                submitButtons(form).forEach((submit) => {
                    submit.disabled = true;
                });
            }, 0);
        });

        window.addEventListener('pageshow', () => {
            document.querySelectorAll('button[aria-busy="true"] .sb-submit-spinner').forEach((spinner) => spinner.remove());
            document.querySelectorAll('button[aria-busy="true"]').forEach((button) => button.removeAttribute('aria-busy'));

            // Restore auth forms when the page comes back from the back/forward cache.
            document.querySelectorAll('form[data-auth-form][data-submitting]').forEach((form) => {
                delete form.dataset.submitting;
                submitButtons(form).forEach((submit) => {
                    submit.disabled = false;
                    submit.classList.remove('is-loading');
                    const label = submit.querySelector('.auth-btn-label[data-original]');
                    if (label) {
                        label.textContent = label.dataset.original;
                        delete label.dataset.original;
                    }
                    const icon = submit.querySelector('i.bi[hidden]');
                    if (icon) icon.hidden = false;
                });
            });
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
