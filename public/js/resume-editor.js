/*
 * Resume Editor (resources/views/student/resume/edit.blade.php).
 *
 * The form is read into the editor JSON that App\Services\ResumeContent validates. Blank rows and
 * entries are left out, and every field gets a data-path ("experiences.0.rows.1.label") so server
 * validation errors land next to the right input even after items were added, moved, or removed.
 *
 * Live preview: the server renders the same template as the PDF (ResumeBuilderService::previewHtml),
 * so the preview always matches the download.
 */
(() => {
    'use strict';

    const form = document.querySelector('[data-resume-editor]');
    if (!form) return;

    const preview = document.querySelector('[data-resume-preview]');
    const frame = preview?.querySelector('[data-preview-frame]');
    const stage = preview?.querySelector('[data-preview-stage]');
    const previewStatus = preview?.querySelector('[data-preview-status]');
    const previewError = preview?.querySelector('[data-preview-error]');
    const previewErrorText = preview?.querySelector('[data-preview-error-text]');
    const saveStatus = form.querySelector('[data-save-status]');
    const errorSummary = form.querySelector('[data-error-summary]');
    const actionButtons = [...form.querySelectorAll('[data-editor-actions] button[type="submit"][name="intent"]')];
    const cancelForm = document.querySelector('[data-cancel-form]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const PAGE_WIDTH = 794; // A4 width (210mm) at 96 dpi
    const PAGE_HEIGHT = 1123; // A4 height (297mm)
    const PREVIEW_DELAY = 450;

    let dirty = false;
    let leaving = false;
    let busy = false;

    // ---------------------------------------------------------------- reading the form

    const isEmpty = (value) => (Array.isArray(value) ? value.length === 0 : value === '');

    const readList = (list, path) => {
        const isValues = list.classList.contains('re-list--values');
        const out = [];

        [...list.children].filter((child) => child.matches('[data-item]')).forEach((item) => {
            const base = `${path}.${out.length}`;
            const value = {};

            [...item.querySelectorAll('[data-key]')].filter((field) => field.closest('[data-item]') === item).forEach((field) => {
                const raw = field.value.trim();
                value[field.dataset.key] = field.dataset.split
                    ? raw.split(field.dataset.split).map((part) => part.trim()).filter(Boolean)
                    : raw;
                field.dataset.path = isValues ? base : `${base}.${field.dataset.key}`;
            });

            [...item.querySelectorAll('[data-list]')].filter((nested) => nested.closest('[data-item]') === item).forEach((nested) => {
                nested.dataset.path = `${base}.${nested.dataset.list}`;
                value[nested.dataset.list] = readList(nested, nested.dataset.path);
            });

            if (Object.values(value).every(isEmpty)) {
                // Blank items are skipped, so they never reach the PDF and take no index.
                item.querySelectorAll('[data-path]').forEach((element) => delete element.dataset.path);
                return;
            }

            out.push(isValues ? value.value : value);
        });

        return out;
    };

    const readForm = () => {
        const data = { contact: {} };

        form.querySelectorAll('[data-field]').forEach((field) => {
            const path = field.dataset.field;
            const value = field.type === 'checkbox' ? field.checked : field.value.trim();
            field.dataset.path = path;
            if (path.startsWith('contact.')) {
                data.contact[path.slice('contact.'.length)] = value;
            } else {
                data[path] = value;
            }
        });

        form.querySelectorAll('[data-list]').forEach((list) => {
            if (list.closest('[data-item]')) return; // nested lists are read with their entry
            list.dataset.path = list.dataset.list;
            data[list.dataset.list] = readList(list, list.dataset.list);
        });

        return data;
    };

    // ---------------------------------------------------------------- errors

    const clearFieldError = (element) => {
        element.classList.remove('is-invalid');
        element.removeAttribute('aria-invalid');
        const path = element.dataset.path;
        if (path) form.querySelectorAll(`[data-error-for="${CSS.escape(path)}"]`).forEach((note) => note.remove());
    };

    /** Removes errors from one source ("preview" or "save"), or all of them. */
    const clearErrors = (source = null) => {
        form.querySelectorAll('[data-error-for]').forEach((note) => {
            if (source && note.dataset.errorSource !== source) return;
            const target = form.querySelector(`[data-path="${CSS.escape(note.dataset.errorFor)}"]`);
            if (target) {
                target.classList.remove('is-invalid');
                target.removeAttribute('aria-invalid');
            }
            note.remove();
        });
        if (!source || source === 'save') {
            errorSummary.hidden = true;
            errorSummary.replaceChildren();
        }
    };

    const showSummary = (title, items = []) => {
        errorSummary.replaceChildren();
        const heading = document.createElement('strong');
        heading.textContent = title;
        errorSummary.append(heading);
        if (items.length) {
            const list = document.createElement('ul');
            items.forEach((text) => {
                const entry = document.createElement('li');
                entry.textContent = text;
                list.append(entry);
            });
            errorSummary.append(list);
        }
        errorSummary.hidden = false;
    };

    /** Puts each message next to its field. Returns the first field with an error. */
    const showErrors = (errors, source) => {
        clearErrors(source);
        const unplaced = [];
        let first = null;

        Object.entries(errors).forEach(([key, messages]) => {
            const message = Array.isArray(messages) ? messages[0] : String(messages);
            const target = form.querySelector(`[data-path="${CSS.escape(key)}"]`);
            if (!target) {
                unplaced.push(message);
                return;
            }

            const note = document.createElement('div');
            note.className = 'invalid-feedback d-block re-error';
            note.dataset.errorFor = key;
            note.dataset.errorSource = source;
            note.textContent = message;

            if (target.matches('[data-list]')) {
                target.after(note);
            } else {
                target.classList.add('is-invalid');
                target.setAttribute('aria-invalid', 'true');
                // Row inputs sit side by side, so their message goes under the whole row.
                const row = target.closest('.re-row, .re-value');
                if (row) row.append(note); else target.after(note);
            }
            first ??= target;
        });

        if (source === 'save') {
            const count = Object.keys(errors).length;
            showSummary(`Please fix ${count} ${count === 1 ? 'problem' : 'problems'} before continuing.`, unplaced);
        }

        return first;
    };

    // ---------------------------------------------------------------- status and busy state

    const setSaveStatus = (text, tone = '') => {
        saveStatus.textContent = text;
        saveStatus.dataset.tone = tone;
    };

    const setBusy = (state, button = null) => {
        busy = state;
        form.setAttribute('aria-busy', String(state));
        actionButtons.forEach((action) => {
            action.disabled = state;
        });
        if (state && button) {
            button.dataset.label = button.innerHTML;
            button.innerHTML = '<span class="sb-submit-spinner" aria-hidden="true"></span>' + button.textContent.trim();
        } else {
            actionButtons.forEach((action) => {
                if (action.dataset.label) {
                    action.innerHTML = action.dataset.label;
                    delete action.dataset.label;
                }
            });
        }
    };

    const markDirty = () => {
        dirty = true;
        setSaveStatus('Unsaved changes', 'warn');
        if (cancelForm) cancelForm.dataset.confirm = 'You have unsaved changes. Leave without saving them?';
    };

    const markClean = () => {
        dirty = false;
        if (cancelForm) delete cancelForm.dataset.confirm;
    };

    // ---------------------------------------------------------------- live preview

    const fitPreview = () => {
        if (!frame || !stage) return;
        const scale = Math.min(1, stage.clientWidth / PAGE_WIDTH) || 1;
        let height = PAGE_HEIGHT;
        try {
            height = Math.max(PAGE_HEIGHT, frame.contentDocument?.documentElement?.scrollHeight || 0);
        } catch {
            // Without same-origin access the frame keeps one A4 page of height.
        }
        frame.style.width = `${PAGE_WIDTH}px`;
        frame.style.height = `${height}px`;
        frame.style.transform = `scale(${scale})`;
        stage.style.height = `${Math.ceil(height * scale)}px`;
    };

    const setPreviewStatus = (text, tone = '') => {
        if (!previewStatus) return;
        previewStatus.textContent = text;
        previewStatus.dataset.tone = tone;
    };

    const showPreviewError = (text) => {
        if (!previewError) return;
        previewErrorText.textContent = text;
        previewError.hidden = false;
    };

    let previewTimer = null;
    let previewRequest = null;

    const refreshPreview = async () => {
        if (!frame) return;
        previewRequest?.abort();
        previewRequest = new AbortController();
        setPreviewStatus('Updating…', 'busy');
        stage?.classList.add('is-updating');

        try {
            const response = await fetch(form.dataset.previewUrl, {
                method: 'POST',
                headers: requestHeaders(),
                body: JSON.stringify(readForm()),
                signal: previewRequest.signal,
            });

            if (response.status === 422) {
                const payload = await response.json().catch(() => ({}));
                showErrors(payload.errors || {}, 'preview');
                showPreviewError('Preview paused. Fix the highlighted fields to see your changes.');
                setPreviewStatus('Paused', 'error');
                return;
            }
            if (response.status === 419) throw new Error('Your session expired. Reload the page to keep editing.');
            if (!response.ok) throw new Error('The preview could not update. Check your connection and try again.');

            const html = await response.text();
            clearErrors('preview');
            previewError.hidden = true;
            frame.srcdoc = html;
            setPreviewStatus('Up to date');
        } catch (error) {
            if (error.name === 'AbortError') return;
            showPreviewError(error.message);
            setPreviewStatus('Not updated', 'error');
        } finally {
            stage?.classList.remove('is-updating');
        }
    };

    const schedulePreview = () => {
        window.clearTimeout(previewTimer);
        setPreviewStatus('Changes pending…', 'busy');
        previewTimer = window.setTimeout(refreshPreview, PREVIEW_DELAY);
    };

    // ---------------------------------------------------------------- add / move / remove

    const updateEmptyStates = () => {
        form.querySelectorAll('[data-list]').forEach((list) => {
            list.classList.toggle('is-empty', !list.querySelector(':scope > [data-item]'));
        });
    };

    const changed = () => {
        markDirty();
        schedulePreview();
    };

    form.addEventListener('click', (event) => {
        const add = event.target.closest('[data-add]');
        if (add) {
            const template = document.querySelector(`template[data-template="${CSS.escape(add.dataset.add)}"]`);
            const list = add.previousElementSibling;
            if (!template || !list?.matches('[data-list]')) return;
            const item = template.content.firstElementChild.cloneNode(true);
            list.append(item);
            updateEmptyStates();
            item.querySelector('input, textarea')?.focus();
            changed();
            return;
        }

        const move = event.target.closest('[data-move]');
        if (move) {
            const item = move.closest('[data-item]');
            const up = Number(move.dataset.move) < 0;
            const sibling = up ? item.previousElementSibling : item.nextElementSibling;
            if (!sibling?.matches('[data-item]')) return;
            if (up) sibling.before(item); else sibling.after(item);
            move.focus();
            changed();
            return;
        }

        const remove = event.target.closest('[data-remove]');
        if (remove) {
            const item = remove.closest('[data-item]');
            const list = item.parentElement;
            const neighbour = item.nextElementSibling?.matches('[data-item]') ? item.nextElementSibling : item.previousElementSibling;
            item.remove();
            updateEmptyStates();
            // Keep keyboard focus nearby: the next item's remove button, or the list's Add button.
            (neighbour?.matches('[data-item]') ? neighbour.querySelector('[data-remove]') : list.nextElementSibling)?.focus();
            changed();
        }
    });

    form.addEventListener('input', (event) => {
        if (event.target.matches('.is-invalid')) clearFieldError(event.target);
        if (event.target.matches('[data-field="update_profile_summary"]')) {
            markDirty();
            return;
        }
        changed();
    });

    // ---------------------------------------------------------------- save / save as new / download

    function requestHeaders() {
        // Accept JSON so validation errors come back as 422 JSON (the preview still answers with HTML).
        return {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest',
        };
    }

    const downloadBlob = (blob, response) => {
        const match = /filename="?([^";]+)"?/i.exec(response.headers.get('Content-Disposition') || '');
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = match ? match[1] : 'resume.pdf';
        document.body.append(link);
        link.click();
        link.remove();
        window.setTimeout(() => URL.revokeObjectURL(link.href), 2000);
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (busy) return;

        const button = event.submitter instanceof HTMLButtonElement ? event.submitter : null;
        const intent = button?.value || 'save';
        const target = {
            save: { url: form.dataset.updateUrl, method: 'PUT', working: 'Saving…' },
            copy: { url: form.dataset.copyUrl, method: 'POST', working: 'Saving a new resume…' },
            pdf: { url: form.dataset.pdfUrl, method: 'POST', working: 'Preparing your PDF…' },
        }[intent];
        if (!target) return;

        setBusy(true, button);
        setSaveStatus(target.working, 'busy');

        try {
            const response = await fetch(target.url, {
                method: target.method,
                headers: requestHeaders(),
                body: JSON.stringify(readForm()),
            });

            if (response.status === 422) {
                const payload = await response.json().catch(() => ({}));
                if (payload.errors) {
                    const first = showErrors(payload.errors, 'save');
                    first?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    first?.focus({ preventScroll: true });
                } else {
                    showSummary(payload.message || 'This resume could not be saved.');
                }
                setSaveStatus('Not saved. Fix the highlighted fields.', 'error');
                return;
            }
            if (response.status === 419) throw new Error('Your session expired. Copy any long text you typed, then reload the page.');
            if (!response.ok) {
                const payload = await response.json().catch(() => ({}));
                throw new Error(payload.message || `Something went wrong (HTTP ${response.status}). Please try again.`);
            }

            clearErrors('save');

            if (intent === 'pdf') {
                downloadBlob(await response.blob(), response);
                setSaveStatus(dirty ? 'PDF downloaded. Your changes are not saved yet.' : 'PDF downloaded.', dirty ? 'warn' : 'ok');
                return;
            }

            const payload = await response.json();
            markClean();
            if (intent === 'copy') {
                leaving = true;
                const url = new URL(payload.edit_url, window.location.href);
                url.searchParams.set('copied', '1');
                window.location.assign(url.toString());
                return;
            }
            setSaveStatus(`${payload.message} ${payload.saved_at ? `(${payload.saved_at})` : ''}`.trim(), 'ok');
        } catch (error) {
            showSummary(error.message);
            setSaveStatus('Not saved.', 'error');
        } finally {
            if (!leaving) setBusy(false);
        }
    });

    // ---------------------------------------------------------------- leaving the page

    cancelForm?.addEventListener('submit', () => {
        leaving = true; // confirmed (or nothing to lose): skip the browser's own prompt
    });

    window.addEventListener('beforeunload', (event) => {
        if (!dirty || leaving) return;
        event.preventDefault();
        event.returnValue = '';
    });

    // ---------------------------------------------------------------- start

    preview?.querySelector('[data-preview-retry]')?.addEventListener('click', refreshPreview);
    frame?.addEventListener('load', fitPreview);
    window.addEventListener('resize', fitPreview);
    updateEmptyStates();
    readForm(); // assigns data-path to every field
    fitPreview();

    const params = new URLSearchParams(window.location.search);
    if (params.has('copied')) {
        setSaveStatus('Saved as a new resume. You are now editing the new copy.', 'ok');
        params.delete('copied');
        const query = params.toString();
        window.history.replaceState(null, '', window.location.pathname + (query ? `?${query}` : ''));
    }
})();
