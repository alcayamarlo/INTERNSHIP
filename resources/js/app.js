import './bootstrap';

document.addEventListener('submit', (event) => {
    if (event.defaultPrevented || !(event.target instanceof HTMLFormElement)) return;
    const form = event.target;
    if (form.dataset.noLoading === 'true') return;
    const button = event.submitter instanceof HTMLButtonElement
        ? event.submitter
        : form.querySelector('button[type="submit"], input[type="submit"]');
    if (!button || button.disabled) return;

    // A disabled button is left out of the submitted data, so keep its name/value (e.g. decision=rejected).
    if (button.name && !form.querySelector(`input[type="hidden"][data-submitter-value][name="${CSS.escape(button.name)}"]`)) {
        const carrier = document.createElement('input');
        carrier.type = 'hidden';
        carrier.name = button.name;
        carrier.value = button.value;
        carrier.dataset.submitterValue = '';
        form.append(carrier);
    }

    button.dataset.originalContent = button instanceof HTMLInputElement ? button.value : button.innerHTML;
    button.setAttribute('aria-busy', 'true');
    button.disabled = true;
    if (button instanceof HTMLInputElement) {
        button.value = 'Working…';
    } else {
        button.innerHTML = '<span class="sb-submit-spinner" aria-hidden="true"></span>Working…';
    }
});

const updateUnreadNotificationBadge = (value) => {
    const unreadCount = Math.max(0, Number(value) || 0);
    document.querySelectorAll('[data-unread-badge]').forEach((badge) => {
        badge.textContent = unreadCount > 9 ? '9+' : String(unreadCount);
        badge.hidden = unreadCount === 0;
    });
    document.querySelectorAll('.sb-top-action[aria-label^="Notifications"]').forEach((button) => {
        button.setAttribute('aria-label', `Notifications${unreadCount ? `, ${unreadCount} unread` : ''}`);
        if (unreadCount === 0) {
            button.querySelector('.sb-notification-badge')?.remove();
        } else if (!button.querySelector('.sb-notification-badge')) {
            const badge = document.createElement('span');
            badge.className = 'sb-notification-badge';
            badge.dataset.unreadBadge = '';
            badge.textContent = unreadCount > 9 ? '9+' : String(unreadCount);
            button.append(badge);
        }
    });
};

document.addEventListener('click', async (event) => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

    const link = event.target instanceof Element
        ? event.target.closest('a[data-notification-open]')
        : null;
    if (!link || link.target === '_blank') return;

    event.preventDefault();
    if (link.dataset.markingRead === 'true') return;
    link.dataset.markingRead = 'true';

    try {
        const response = await fetch(link.dataset.readUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        const payload = await response.json().catch(() => ({ message: response.status === 413 ? 'The file is too large to upload.' : `Request failed (HTTP ${response.status}). Please try again.` }));
        if (!response.ok) throw new Error(payload.message || 'Unable to open notification.');

        link.closest('[data-notification-item]')?.classList.remove('is-unread', 'bg-light', 'border-start', 'border-primary', 'border-3');
        updateUnreadNotificationBadge(payload.unread_count);
        window.location.assign(link.href);
    } catch (error) {
        delete link.dataset.markingRead;
        window.alert(error.message || 'Unable to open notification.');
    }
}, true);

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-ajax-application')) return;

    event.preventDefault();
    event.stopImmediatePropagation();
    const submitButton = form.querySelector('button[type="submit"]');
    if (!submitButton || submitButton.disabled) return;

    const originalContent = submitButton.innerHTML;
    const feedback = form.querySelector('[data-application-feedback]');
    submitButton.disabled = true;
    submitButton.setAttribute('aria-busy', 'true');
    submitButton.innerHTML = '<span class="sb-submit-spinner" aria-hidden="true"></span>Submitting…';
    if (feedback) {
        feedback.className = 'small mt-2';
        feedback.textContent = '';
    }

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const payload = await response.json().catch(() => ({ message: response.status === 413 ? 'The file is too large to upload.' : `Request failed (HTTP ${response.status}). Please try again.` }));
        if (!response.ok) {
            const firstError = payload.errors ? Object.values(payload.errors).flat()[0] : null;
            throw new Error(firstError || payload.message || 'Unable to submit application.');
        }

        submitButton.textContent = 'Applied';
        submitButton.classList.remove('btn-primary');
        submitButton.classList.add('btn-success');
        submitButton.disabled = true;
        form.querySelectorAll('select, textarea').forEach((input) => {
            input.disabled = true;
        });
        if (feedback) {
            feedback.classList.add('text-success');
            feedback.textContent = payload.message || 'Application submitted successfully.';
            if (payload.applications_url) {
                const link = document.createElement('a');
                link.href = payload.applications_url;
                link.textContent = ' View My Applications';
                feedback.append(link);
            }
        }
    } catch (error) {
        if (feedback) {
            feedback.classList.add('text-danger');
            feedback.textContent = error.message || 'Unable to submit application.';
        }
    } finally {
        submitButton.removeAttribute('aria-busy');
        if (submitButton.textContent !== 'Applied') {
            submitButton.disabled = false;
            submitButton.innerHTML = originalContent;
        }
    }
}, true);

// ---------------------------------------------------------------------------
// Notification actions: bell dropdown (layouts/app.blade.php) and the Notifications
// page (notifications/index.blade.php). Every response carries unread_count and the
// Inbox/Archived/Trash counts, so the badge and tabs update without a page refresh.
// ---------------------------------------------------------------------------

/** Actions after which the notification no longer belongs in the list being shown. */
const NOTIFICATION_REMOVING_ACTIONS = ['archive', 'unarchive', 'restore', 'delete', 'restore-deleted', 'force-delete'];

/** Every copy of a notification on the page (the dropdown and the page list can both show it). */
const notificationItems = (id) => document.querySelectorAll(`[data-notification-item][data-notification-id="${CSS.escape(String(id))}"]`);

const markNotificationItemRead = (item) => {
    item.classList.remove('is-unread', 'bg-light', 'border-start', 'border-primary', 'border-3');
    item.querySelector('.badge.bg-primary')?.remove();
    item.querySelector('[data-ajax-notification="read"]')?.remove();
};

/** Bulk buttons work only while something is selected; "select all" mirrors the row boxes. */
const syncNotificationSelection = () => {
    const boxes = [...document.querySelectorAll('[data-notification-select]')];
    const checked = boxes.filter((box) => box.checked).length;
    document.querySelectorAll('[data-bulk-action]').forEach((button) => {
        button.disabled = checked === 0 || button.dataset.busy === 'true';
    });
    const selectAll = document.querySelector('[data-notification-select-all]');
    if (selectAll) {
        selectAll.checked = boxes.length > 0 && checked === boxes.length;
        selectAll.indeterminate = checked > 0 && checked < boxes.length;
        selectAll.disabled = boxes.length === 0;
    }
};

const removeNotificationItem = (item) => {
    const list = item.closest('[data-notification-list]');
    item.remove();
    // Last row on the page gone: show this tab's empty state and hide the bulk bar.
    if (list && !list.querySelector('[data-notification-item]')) {
        const emptyTemplate = document.querySelector('template[data-notification-empty]');
        if (emptyTemplate) list.replaceChildren(emptyTemplate.content.cloneNode(true));
        document.getElementById('ntBulkForm')?.setAttribute('hidden', '');
    }
};

const updateNotificationCounts = (counts, unreadCount) => {
    if (counts) {
        Object.entries(counts).forEach(([key, value]) => {
            document.querySelectorAll(`[data-notification-count="${key}"]`).forEach((element) => {
                element.textContent = String(value);
            });
        });
    }
    const page = document.querySelector('[data-notification-page]');
    const summary = page?.querySelector('[data-notification-summary]');
    if (summary && page.dataset.view === 'active' && unreadCount !== undefined) {
        summary.textContent = unreadCount > 0
            ? `You have ${unreadCount} unread notification${unreadCount === 1 ? '' : 's'}.`
            : "You're all caught up.";
    }
};

/** Success/error line on the Notifications page; the dropdown falls back to an alert for errors. */
const showNotificationFeedback = (message, isError = false) => {
    const feedback = document.querySelector('[data-notification-feedback]');
    if (!feedback) {
        if (isError) window.alert(message);
        return;
    }
    feedback.textContent = message;
    feedback.classList.toggle('is-error', isError);
    feedback.classList.toggle('is-success', !isError);
    feedback.hidden = false;
};

document.addEventListener('change', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLInputElement)) return;
    if (target.matches('[data-notification-select-all]')) {
        document.querySelectorAll('[data-notification-select]').forEach((box) => {
            box.checked = target.checked;
        });
    }
    if (target.matches('[data-notification-select-all], [data-notification-select]')) {
        syncNotificationSelection();
    }
});

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.dataset.ajaxNotification) return;

    event.preventDefault();
    event.stopImmediatePropagation();
    // Double-click guard: while this form's request runs, further submits are ignored.
    if (form.dataset.busy === 'true') return;

    const action = form.dataset.ajaxNotification;
    const submitter = event.submitter instanceof HTMLButtonElement ? event.submitter : null;
    const buttons = [...form.querySelectorAll('button[type="submit"]')];
    const body = new FormData(form);
    // FormData(form) leaves out the clicked button, but the bulk form needs its action value.
    if (submitter?.name) body.set(submitter.name, submitter.value);

    form.dataset.busy = 'true';
    buttons.forEach((button) => {
        button.dataset.busy = 'true';
        button.disabled = true;
    });

    try {
        // Always POST; DELETE forms carry _method=DELETE, which Laravel reads.
        const response = await fetch(form.action, {
            method: 'POST',
            body,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const payload = await response.json().catch(() => ({ message: response.status === 413 ? 'The file is too large to upload.' : `Request failed (HTTP ${response.status}). Please try again.` }));
        if (!response.ok) {
            const firstError = payload.errors ? Object.values(payload.errors).flat()[0] : null;
            throw new Error(firstError || payload.message || 'Unable to update notification.');
        }

        if (payload.unread_count !== undefined) updateUnreadNotificationBadge(payload.unread_count);
        updateNotificationCounts(payload.counts, payload.unread_count);

        const itemId = form.closest('[data-notification-item]')?.dataset.notificationId;

        if (action === 'read' && itemId) {
            notificationItems(itemId).forEach(markNotificationItemRead);
        } else if (NOTIFICATION_REMOVING_ACTIONS.includes(action) && itemId) {
            notificationItems(itemId).forEach(removeNotificationItem);
        } else if (action === 'bulk') {
            // Only the ids the server actually changed (it ignores ids that are not this user's).
            (payload.ids || []).forEach((id) => {
                notificationItems(id).forEach(payload.action === 'read' ? markNotificationItemRead : removeNotificationItem);
            });
            document.querySelectorAll('[data-notification-select]').forEach((box) => {
                box.checked = false;
            });
        } else if (action === 'read-all') {
            document.querySelectorAll('[data-notification-item]').forEach(markNotificationItemRead);
        } else if (action === 'archive-all-read') {
            // Read items leave the inbox: in the dropdown and on the Inbox tab.
            document.querySelectorAll('[data-notification-item]').forEach((item) => {
                if (!item.classList.contains('is-unread')) removeNotificationItem(item);
            });
        }

        showNotificationFeedback(payload.message || 'Done.');
    } catch (error) {
        showNotificationFeedback(error.message || 'Unable to update notification.', true);
    } finally {
        delete form.dataset.busy;
        buttons.forEach((button) => {
            delete button.dataset.busy;
            if (button.isConnected) button.disabled = false;
        });
        syncNotificationSelection();
    }
}, true);

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-ajax-form')) return;

    event.preventDefault();
    event.stopImmediatePropagation();
    const submitButton = event.submitter instanceof HTMLButtonElement
        ? event.submitter
        : form.querySelector('button[type="submit"], input[type="submit"]');
    if (submitButton?.disabled) return;
    const originalContent = submitButton instanceof HTMLInputElement
        ? submitButton.value
        : submitButton?.innerHTML;
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.setAttribute('aria-busy', 'true');
        if (submitButton instanceof HTMLInputElement) {
            submitButton.value = 'Working…';
        } else {
            submitButton.innerHTML = '<span class="sb-submit-spinner" aria-hidden="true"></span>Working…';
        }
    }

    let feedback = form.querySelector('[data-ajax-feedback]');
    if (!feedback) {
        feedback = document.createElement('div');
        feedback.dataset.ajaxFeedback = '';
        feedback.className = 'small mt-2';
        feedback.setAttribute('role', 'status');
        feedback.setAttribute('aria-live', 'polite');
        form.append(feedback);
    }
    feedback.textContent = '';
    feedback.classList.remove('text-danger', 'text-success');

    try {
        const response = await fetch(form.action, {
            method: form.method || 'POST',
            body: new FormData(form),
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const payload = await response.json().catch(() => ({ message: response.status === 413 ? 'The file is too large to upload.' : `Request failed (HTTP ${response.status}). Please try again.` }));
        if (!response.ok) {
            const firstError = payload.errors ? Object.values(payload.errors).flat()[0] : null;
            throw new Error(firstError || payload.message || 'Unable to complete this action.');
        }

        feedback.textContent = payload.message || 'Changes saved.';
        feedback.classList.add('text-success');
        if (form.hasAttribute('data-application-status-form')) {
            const statusBadge = document.querySelector('[data-application-status]');
            if (statusBadge && payload.status_label && payload.status_badge_class) {
                statusBadge.textContent = payload.status_label;
                statusBadge.className = `badge ${payload.status_badge_class} fs-6`;
            }

            const statusSelect = form.querySelector('[name="status"]');
            if (statusSelect && Array.isArray(payload.available_statuses)) {
                statusSelect.replaceChildren(...payload.available_statuses.map(({ value, label }) => new Option(label, value)));
                if (payload.available_statuses.length === 0) {
                    form.hidden = true;
                    const finalStatusMessage = document.createElement('p');
                    finalStatusMessage.className = 'text-muted mb-0';
                    finalStatusMessage.textContent = 'No further status changes are available.';
                    form.insertAdjacentElement('afterend', finalStatusMessage);
                }
            }
        }
        if (form.hasAttribute('data-ajax-redirect') && payload.redirect_url) {
            window.location.assign(payload.redirect_url);
            return;
        }
        if (form.dataset.ajaxReset === 'true') form.reset();
    } catch (error) {
        feedback.textContent = error.message || 'Unable to complete this action.';
        feedback.classList.add('text-danger');
    } finally {
        if (submitButton?.isConnected) {
            submitButton.disabled = false;
            submitButton.removeAttribute('aria-busy');
            if (submitButton instanceof HTMLInputElement) {
                submitButton.value = originalContent || submitButton.value;
            } else if (originalContent !== undefined) {
                submitButton.innerHTML = originalContent;
            }
        }
    }
}, true);

// Set by the live listeners below when something changed while this tab was in the background.
let liveChangesWhileAway = false;

const focusRefreshTarget = document.querySelector('[data-refresh-on-focus]');
if (focusRefreshTarget) {
    let blurredAt = null;
    window.addEventListener('blur', () => {
        blurredAt = Date.now();
    });
    window.addEventListener('focus', () => {
        // With live updates on, reload only if something actually changed while away
        // (it used to reload on every return to the tab, which felt slow).
        const changed = window.Echo ? liveChangesWhileAway : blurredAt !== null && Date.now() - blurredAt > 3000;
        blurredAt = null;
        liveChangesWhileAway = false;
        if (changed) window.location.reload();
    });
}

window.addEventListener('pageshow', () => {
    document.querySelectorAll('[aria-busy="true"]').forEach((button) => {
        button.disabled = false;
        button.removeAttribute('aria-busy');
        if (button instanceof HTMLInputElement) {
            button.value = button.dataset.originalContent || button.value;
        } else if (button.dataset.originalContent) {
            button.innerHTML = button.dataset.originalContent;
        }
    });
});

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const revealTargets = document.querySelectorAll('.skillbridge-public .public-hero, .skillbridge-public .public-section, .skillbridge-public .public-cta');
if (!reducedMotion && 'IntersectionObserver' in window) {
    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08 });

    revealTargets.forEach((target) => {
        target.classList.add('sb-reveal');
        revealObserver.observe(target);
    });
} else {
    revealTargets.forEach((target) => target.classList.add('is-visible'));
}

// Mobile table layout: copies each column heading onto its cells. Re-run on live-refreshed areas.
const labelResponsiveTables = (root) => {
    root.querySelectorAll('table').forEach((table) => {
        const headings = [...table.querySelectorAll('thead th')].map((heading) => heading.textContent.trim());
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
const mainContent = document.getElementById('sbContent');
if (mainContent) labelResponsiveTables(mainContent);

// ---------------------------------------------------------------------------
// Live regions. A page marks areas with data-live-region="unique-name" and
// data-live-topics="applications placements notifications". When a live event
// arrives (Echo listeners below), the current page is fetched in the background
// and each matching area is swapped in place: no full reload, no lost scroll.
// An area is never swapped while the user is working in it (typing, items
// selected, a dialog open); a "New updates" bar offers a refresh instead.
// ---------------------------------------------------------------------------

const LIVE_REFRESH_DELAY_MS = 700;
let liveRefreshTimer = null;
let liveRefreshRunning = false;
let liveTopicsWaiting = new Set();

const liveRegionIsBusy = (region) => {
    if (document.querySelector('.modal.show, dialog[open]')) return true;
    const active = document.activeElement;
    if (active && region.contains(active) && active.matches('input, textarea, select, [contenteditable="true"]')) return true;
    if (region.querySelector('[aria-busy="true"], form[data-busy="true"], [data-notification-select]:checked')) return true;
    // Typed or changed but not saved yet.
    const edited = [...region.querySelectorAll('input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]), textarea')]
        .some((field) => field.value !== field.defaultValue);
    const reselected = [...region.querySelectorAll('select')]
        .some((select) => [...select.options].some((option) => option.selected !== option.defaultSelected));
    return edited || reselected;
};

const showLiveUpdatesBar = () => {
    if (document.getElementById('sbLiveUpdates')) return;
    const bar = document.createElement('div');
    bar.id = 'sbLiveUpdates';
    bar.className = 'sb-live-updates';
    bar.setAttribute('role', 'status');
    bar.innerHTML = '<span>This page has new updates.</span>';
    const refresh = document.createElement('button');
    refresh.type = 'button';
    refresh.className = 'btn btn-sm btn-primary';
    refresh.textContent = 'Refresh';
    refresh.addEventListener('click', () => window.location.reload());
    const dismiss = document.createElement('button');
    dismiss.type = 'button';
    dismiss.className = 'btn-close';
    dismiss.setAttribute('aria-label', 'Dismiss');
    dismiss.addEventListener('click', () => bar.remove());
    bar.append(refresh, dismiss);
    document.body.append(bar);
};

const regionMatches = (region, topics) => topics.has('*')
    || (region.dataset.liveTopics || '').split(/\s+/).some((topic) => topics.has(topic));

const refreshLiveRegions = async () => {
    const topics = liveTopicsWaiting;
    liveTopicsWaiting = new Set();
    const regions = [...document.querySelectorAll('[data-live-region]')].filter((region) => regionMatches(region, topics));
    if (!regions.length) return;

    liveRefreshRunning = true;
    try {
        const response = await fetch(window.location.href, {
            headers: { 'Accept': 'text/html', 'X-Live-Refresh': '1' },
            credentials: 'same-origin',
            cache: 'no-store'
        });
        // Signed out, page gone, or redirected elsewhere: leave the page as it is.
        if (!response.ok || response.redirected) return;
        const freshPage = new DOMParser().parseFromString(await response.text(), 'text/html');

        let skipped = false;
        regions.forEach((region) => {
            const fresh = freshPage.querySelector(`[data-live-region="${CSS.escape(region.dataset.liveRegion)}"]`);
            if (!fresh || fresh.innerHTML === region.innerHTML) return;
            if (liveRegionIsBusy(region)) {
                skipped = true;
                return;
            }
            const adopted = document.adoptNode(fresh);
            region.replaceWith(adopted);
            labelResponsiveTables(adopted);
        });
        if (skipped) showLiveUpdatesBar();
    } catch {
        // Network blip: the next live event tries again.
    } finally {
        liveRefreshRunning = false;
        if (liveTopicsWaiting.size) scheduleLiveRefresh();
    }
};

/** Collects topics and refreshes once after a short pause (several changes arrive together). */
function scheduleLiveRefresh(topics = []) {
    topics.forEach((topic) => liveTopicsWaiting.add(topic));
    if (document.hidden) {
        liveChangesWhileAway = true;
        return; // done when the tab is visible again (visibilitychange below)
    }
    if (liveRefreshRunning) return; // picked up in refreshLiveRegions' finally
    window.clearTimeout(liveRefreshTimer);
    liveRefreshTimer = window.setTimeout(refreshLiveRegions, LIVE_REFRESH_DELAY_MS);
}

document.addEventListener('visibilitychange', () => {
    if (!document.hidden && liveTopicsWaiting.size) scheduleLiveRefresh();
});

// ---------------------------------------------------------------------------
// Live notifications and messages (Pusher via window.Echo). window.Echo is
// only set when VITE_PUSHER_APP_KEY is configured (see resources/js/bootstrap.js);
// everything below is a no-op otherwise, so the app degrades to normal
// page-load-only updates with no Pusher app set up.
// ---------------------------------------------------------------------------

const showLiveToast = (title, body, href) => {
    const container = document.getElementById('toastContainer');
    if (!container || typeof bootstrap === 'undefined') return;

    const toastEl = document.createElement('div');
    toastEl.className = 'toast';
    toastEl.setAttribute('role', 'status');
    toastEl.setAttribute('aria-live', 'polite');
    toastEl.setAttribute('aria-atomic', 'true');
    toastEl.innerHTML = `
        <div class="toast-header">
            <strong class="me-auto">${title}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body"></div>
    `;
    const bodyEl = toastEl.querySelector('.toast-body');
    if (href) {
        const link = document.createElement('a');
        link.href = href;
        link.textContent = body;
        link.className = 'stretched-link';
        bodyEl.append(link);
    } else {
        bodyEl.textContent = body;
    }

    container.append(toastEl);
    const toast = new bootstrap.Toast(toastEl, { delay: 8000 });
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    toast.show();
};

const currentUserId = document.querySelector('meta[name="current-user-id"]')?.content;

if (window.Echo && currentUserId) {
    const channel = window.Echo.private(`App.Models.User.${currentUserId}`);

    channel.listen('.notification.created', (payload) => {
        document.querySelectorAll('[data-unread-badge]').forEach((badge) => {
            const next = (Number(badge.textContent === '9+' ? 10 : badge.textContent) || 0) + 1;
            badge.textContent = next > 9 ? '9+' : String(next);
            badge.hidden = false;
        });
        document.querySelectorAll('.sb-top-action[aria-label^="Notifications"]').forEach((button) => {
            if (!button.querySelector('.sb-notification-badge')) {
                const badge = document.createElement('span');
                badge.className = 'sb-notification-badge';
                badge.dataset.unreadBadge = '';
                badge.textContent = '1';
                button.append(badge);
            }
        });

        const menu = document.querySelector('.sb-notification-menu');
        const emptyState = menu?.querySelector('.sb-empty');
        if (menu) {
            emptyState?.remove();
            const item = document.createElement('div');
            item.className = 'sb-notification-item is-unread';
            item.dataset.notificationItem = '';
            item.dataset.notificationId = payload.id;
            const link = document.createElement('a');
            link.className = 'notification-open-link';
            link.href = payload.show_url;
            link.dataset.notificationOpen = '';
            link.dataset.readUrl = payload.read_url;
            const title = document.createElement('strong');
            title.textContent = payload.title;
            const body = document.createElement('span');
            body.textContent = payload.message;
            const time = document.createElement('time');
            time.textContent = 'just now';
            link.append(title, body, time);
            item.append(link);
            menu.insertBefore(item, menu.querySelector('.dropdown-header').nextSibling);
        }

        showLiveToast(payload.title, payload.message, payload.show_url);

        // A notification usually means something on this page changed: refresh its live areas.
        scheduleLiveRefresh(['*']);

        // Lets an open chat with that company unlock in place (messages/show.blade.php).
        if (payload.messaging_open_with) {
            window.dispatchEvent(new CustomEvent('sb:messaging-opened', { detail: payload.messaging_open_with }));
        }
    });

    // Data you can see changed (App\Events\LiveUpdate); payload.topics says which kind.
    channel.listen('.live.update', (payload) => {
        scheduleLiveRefresh(Array.isArray(payload.topics) ? payload.topics : ['*']);
    });

    channel.listen('.message.sent', (payload) => {
        const thread = document.getElementById('messageThread');
        const onThisThread = thread && thread.dataset.threadUserId === String(payload.sender_id);

        if (onThisThread) {
            const emptyState = thread.querySelector('.messenger-empty');
            if (emptyState) emptyState.remove();
            const row = document.createElement('div');
            row.className = 'messenger-row incoming';
            const bubble = document.createElement('div');
            bubble.className = 'messenger-bubble';
            const message = document.createElement('p');
            message.className = 'messenger-message';
            message.textContent = payload.body;
            const time = document.createElement('span');
            time.className = 'messenger-time';
            time.textContent = payload.created_at;
            bubble.append(message, time);
            row.append(bubble);
            thread.append(row);
            thread.scrollTop = thread.scrollHeight;
        } else {
            showLiveToast(`New message from ${payload.sender_name}`, payload.body, payload.thread_url);
        }
    });
}

document.querySelectorAll('[data-count-up]').forEach((element) => {
    const endValue = Number(element.dataset.countUp);
    if (!Number.isFinite(endValue)) return;
    if (reducedMotion) {
        element.textContent = String(endValue);
        return;
    }

    const start = performance.now();
    const duration = 800;
    const tick = (now) => {
        const progress = Math.min(1, (now - start) / duration);
        const eased = 1 - (1 - progress) ** 3;
        element.textContent = String(Math.round(endValue * eased));
        if (progress < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
});
