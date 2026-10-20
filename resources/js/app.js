import './bootstrap';

document.addEventListener('submit', (event) => {
    if (event.defaultPrevented || !(event.target instanceof HTMLFormElement)) return;
    const form = event.target;
    if (form.dataset.noLoading === 'true') return;
    const button = event.submitter instanceof HTMLButtonElement
        ? event.submitter
        : form.querySelector('button[type="submit"], input[type="submit"]');
    if (!button || button.disabled) return;

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

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.dataset.ajaxNotification) return;

    event.preventDefault();
    event.stopImmediatePropagation();
    const submitButton = form.querySelector('button[type="submit"]');
    if (submitButton?.disabled) return;
    if (submitButton) submitButton.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const payload = await response.json().catch(() => ({ message: response.status === 413 ? 'The file is too large to upload.' : `Request failed (HTTP ${response.status}). Please try again.` }));
        if (!response.ok) throw new Error(payload.message || 'Unable to update notification.');

        updateUnreadNotificationBadge(payload.unread_count);

        const action = form.dataset.ajaxNotification;
        const item = form.closest('[data-notification-item]');

        if (action === 'read') {
            item?.classList.remove('is-unread', 'bg-light', 'border-start', 'border-primary', 'border-3');
            item?.querySelector('.badge.bg-primary')?.remove();
            form.remove();
            return;
        }

        if (action === 'archive' || action === 'restore') {
            item?.remove();
            return;
        }

        if (action === 'read-all') {
            document.querySelectorAll('[data-notification-item]').forEach((notificationItem) => {
                notificationItem.classList.remove('is-unread', 'bg-light', 'border-start', 'border-primary', 'border-3');
                notificationItem.querySelector('.badge.bg-primary')?.remove();
                notificationItem.querySelector('[data-ajax-notification="read"]')?.remove();
            });
            form.remove();
            return;
        }

        if (action === 'archive-all-read') {
            document.querySelectorAll('[data-notification-item]').forEach((notificationItem) => {
                const unreadBadge = notificationItem.querySelector('.badge.bg-primary');
                if (!unreadBadge) {
                    notificationItem.remove();
                }
            });
            form.remove();
        }
    } catch (error) {
        window.alert(error.message || 'Unable to update notification.');
    } finally {
        if (submitButton?.isConnected) submitButton.disabled = false;
    }
}, true);

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.dataset.ajaxResume) return;

    event.preventDefault();
    event.stopImmediatePropagation();
    const submitButton = event.submitter instanceof HTMLButtonElement
        ? event.submitter
        : form.querySelector('button[type="submit"]');
    if (submitButton?.disabled) return;

    const originalContent = submitButton?.innerHTML;
    const feedback = document.getElementById('resumeActionFeedback');
    const actionName = form.dataset.ajaxResume;
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.setAttribute('aria-busy', 'true');
        submitButton.innerHTML = '<span class="sb-submit-spinner" aria-hidden="true"></span>Working…';
    }
    if (feedback) {
        feedback.className = 'alert d-none';
        feedback.textContent = '';
    }

    const sendRequest = async () => {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const payload = await response.json().catch(() => ({ message: response.status === 413 ? 'The file is too large to upload.' : `Request failed (HTTP ${response.status}). Please try again.` }));
        return { response, payload };
    };

    try {
        let { response, payload } = await sendRequest();

        if (response.status === 409 && payload.confirmation_required) {
            if (!window.confirm(payload.message)) {
                if (feedback) {
                    feedback.className = 'alert alert-secondary';
                    feedback.textContent = 'Archive cancelled.';
                }
                return;
            }

            form.elements.namedItem('confirmed_active_applications').value = '1';
            ({ response, payload } = await sendRequest());
        }

        if (!response.ok) {
            throw new Error(payload.message || 'Unable to update this resume.');
        }

        if (feedback) {
            feedback.className = 'alert alert-success';
            feedback.textContent = payload.message || 'Resume updated.';
        }

        const row = form.closest('[data-resume-row]');
        const list = form.closest('[data-resume-list]');
        row?.remove();
        if (list && !list.querySelector('[data-resume-row]')) {
            const emptyRow = document.createElement('tr');
            emptyRow.dataset.resumeEmpty = '';
            const emptyCell = document.createElement('td');
            emptyCell.colSpan = 2;
            emptyCell.className = 'text-center text-muted py-4';
            emptyCell.textContent = list.dataset.emptyMessage || 'No resumes found.';
            emptyRow.append(emptyCell);
            list.append(emptyRow);
        }
    } catch (error) {
        if (feedback) {
            feedback.className = 'alert alert-danger';
            feedback.textContent = error.message || 'Unable to update this resume.';
        }
    } finally {
        if (submitButton?.isConnected) {
            submitButton.disabled = false;
            submitButton.removeAttribute('aria-busy');
            submitButton.innerHTML = originalContent;
            submitButton.querySelectorAll('.sb-submit-spinner').forEach((spinner) => spinner.remove());
        }
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

const focusRefreshTarget = document.querySelector('[data-refresh-on-focus]');
if (focusRefreshTarget) {
    let blurredAt = null;
    window.addEventListener('blur', () => {
        blurredAt = Date.now();
    });
    window.addEventListener('focus', () => {
        if (blurredAt !== null && Date.now() - blurredAt > 3000) {
            window.location.reload();
        }
        blurredAt = null;
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

document.querySelectorAll('#sbContent table').forEach((table) => {
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
