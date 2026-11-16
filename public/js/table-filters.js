/*
 * Shared filter bar for student lists (<x-table-filters>).
 * Results update as you type (debounced) or change a dropdown: the page is fetched with the
 * filters in the URL and only the results region is swapped, so the search box keeps focus.
 * Changing a filter always goes back to page 1 (the form never sends a page number).
 * The URL is kept in sync, so live updates and reloads keep the same filters.
 * Without JavaScript the form still works as a normal GET form.
 */
(() => {
    'use strict';

    // Same mobile labelling app.js applies to tables, re-run after a swap.
    const labelTables = (root) => {
        root.querySelectorAll('table').forEach((table) => {
            const headings = [...table.querySelectorAll('thead th')].map((th) => th.textContent.trim());
            if (!headings.length) return;
            table.classList.add('sb-responsive-table');
            table.querySelectorAll('tbody tr').forEach((row) => {
                row.querySelectorAll('td').forEach((cell, index) => {
                    if (cell.hasAttribute('colspan') || cell.dataset.label || !headings[index]) return;
                    cell.dataset.label = headings[index];
                });
            });
        });
    };

    const init = (form) => {
        const selector = form.dataset.target;
        const region = () => document.querySelector(selector);
        const count = form.querySelector('[data-tf-count]');
        const clear = form.querySelector('[data-tf-clear]');
        let timer = null;
        let controller = null;

        form.classList.add('is-enhanced');

        const isActive = () => [...new FormData(form).values()].some((value) => String(value).trim() !== '');

        const syncCount = () => {
            const meta = region()?.querySelector('[data-tf-meta]');
            if (meta && count) count.textContent = `Showing ${meta.dataset.showing} of ${meta.dataset.total}`;
            if (clear) {
                if (isActive()) clear.removeAttribute('aria-disabled');
                else clear.setAttribute('aria-disabled', 'true');
            }
        };

        const urlFromForm = () => {
            const url = new URL(form.action, window.location.href);
            new FormData(form).forEach((value, key) => {
                if (String(value).trim() !== '') url.searchParams.set(key, String(value).trim());
            });
            return url;
        };

        const load = async (url) => {
            const current = region();
            if (!current) {
                window.location.assign(url);
                return;
            }

            controller?.abort();
            controller = new AbortController();
            current.classList.add('is-loading');
            current.setAttribute('aria-busy', 'true');

            try {
                const response = await fetch(url, {
                    headers: { Accept: 'text/html' },
                    credentials: 'same-origin',
                    signal: controller.signal,
                });
                if (!response.ok || response.redirected) throw new Error(`HTTP ${response.status}`);

                const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                const fresh = page.querySelector(selector);
                if (!fresh) throw new Error('Results region missing');

                const adopted = document.adoptNode(fresh);
                labelTables(adopted);
                region()?.replaceWith(adopted);
                window.history.replaceState(window.history.state, '', url);
                syncCount();
            } catch (error) {
                if (error.name === 'AbortError') return;
                window.location.assign(url); // Fall back to a normal page load.
            } finally {
                region()?.classList.remove('is-loading');
                region()?.removeAttribute('aria-busy');
            }
        };

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            clearTimeout(timer);
            load(urlFromForm());
        });

        form.querySelectorAll('select').forEach((select) => {
            select.addEventListener('change', () => form.requestSubmit());
        });

        const search = form.querySelector('input[type="search"]');
        search?.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => form.requestSubmit(), 300);
        });

        clear?.addEventListener('click', (event) => {
            event.preventDefault();
            form.querySelectorAll('input[type="search"], select').forEach((field) => { field.value = ''; });
            clearTimeout(timer);
            load(new URL(form.action, window.location.href));
            search?.focus();
        });

        // Pagination links inside the results keep the filters (withQueryString) and load in place.
        document.addEventListener('click', (event) => {
            const link = event.target.closest('.pagination a[href]');
            const current = region();
            if (!link || !current || !current.contains(link) || event.ctrlKey || event.metaKey || event.shiftKey) return;
            event.preventDefault();
            load(new URL(link.href, window.location.href)).then(() => {
                const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                region()?.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
            });
        });

        // Live updates (app.js) replace the region too; keep the count in step with it.
        const parent = region()?.parentElement;
        if (parent) new MutationObserver(syncCount).observe(parent, { childList: true });
    };

    document.querySelectorAll('form[data-table-filters]').forEach(init);
})();
