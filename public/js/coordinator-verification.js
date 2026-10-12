(() => {
    'use strict';

    const page = document.getElementById('verificationPage');
    const dataElement = document.getElementById('verificationData');
    if (!page || !dataElement) return;

    const state = {
        students: JSON.parse(dataElement.textContent),
        type: 'all',
        status: 'pending',
        search: '',
        program: '',
        undo: null,
        undoTimer: null,
        activeStudentId: null,
        returnFocus: null,
    };

    const groups = document.getElementById('studentGroups');
    const emptyState = document.getElementById('emptyQueue');
    const drawer = document.getElementById('studentDrawer');
    const backdrop = document.getElementById('studentDrawerBackdrop');
    const toast = document.getElementById('verificationToast');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    const create = (tag, className, text) => {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (text !== undefined && text !== null) element.textContent = String(text);
        return element;
    };

    const getTypeLabel = (type) => ({
        competencies: 'Competency',
        certificates: 'Certificate',
        portfolio: 'Portfolio',
        resumes: 'Resume',
    }[type] ?? type);

    const getUpdateUrl = (type, id) => `${page.dataset.baseUrl}/${encodeURIComponent(type)}/${encodeURIComponent(id)}`;
    const getEvidenceUrl = (type, id) => page.dataset.evidenceUrl
        .replace('__type__', encodeURIComponent(type))
        .replace('__id__', encodeURIComponent(id));
    const getDownloadUrl = (type, id) => page.dataset.downloadUrl
        .replace('__type__', encodeURIComponent(type))
        .replace('__id__', encodeURIComponent(id));
    const getStudentUrl = (studentId) => page.dataset.studentsUrl.replace('__student__', encodeURIComponent(studentId));
    const getVerifyAllUrl = (studentId) => page.dataset.verifyAllUrl.replace('__student__', encodeURIComponent(studentId));

    const matchesItem = (item) => (
        (state.type === 'all' || item.type === state.type)
        && (state.status === 'all' || item.status === state.status)
    );

    const matchesStudent = (student) => {
        const searchTerm = state.search.trim().toLocaleLowerCase();
        const searchable = `${student.name} ${student.student_id_number ?? ''}`.toLocaleLowerCase();
        return (!searchTerm || searchable.includes(searchTerm))
            && (!state.program || student.program === state.program)
            && student.items.some(matchesItem);
    };

    const visibleStudents = () => state.students.filter(matchesStudent);
    const visibleItems = () => visibleStudents().flatMap((student) => student.items.filter(matchesItem));

    const updateCounters = () => {
        const counts = { pending: 0, verified: 0, rejected: 0 };
        visibleItems().filter((item) => item.category !== 'resume').forEach((item) => { counts[item.status] += 1; });
        document.getElementById('countPending').textContent = counts.pending;
        document.getElementById('countVerified').textContent = counts.verified;
        document.getElementById('countRejected').textContent = counts.rejected;
    };

    const makeProgress = (items, className = 'progress-track') => {
        const verified = items.filter((item) => item.status === 'verified').length;
        const track = create('div', className);
        const fill = create('span');
        fill.style.width = `${items.length ? Math.round((verified / items.length) * 100) : 0}%`;
        track.append(fill);
        return { track, verified, total: items.length };
    };

    const updateItem = (student, replacement) => {
        student.items = student.items.map((item) => (
            item.id === replacement.id && item.type === replacement.type ? { ...item, ...replacement } : item
        ));
    };

    const makeButton = (label, className, onClick) => {
        const button = create('button', className, label);
        button.type = 'button';
        button.addEventListener('click', onClick);
        return button;
    };

    const buildRejectPanel = (student, item) => {
        const panel = create('form', 'reject-panel');
        const reasons = create('div', 'quick-reasons');
        const input = create('input', 'reject-reason');
        input.type = 'text';
        input.maxLength = 2000;
        input.required = true;
        input.placeholder = 'Reason for rejection (required)';
        input.setAttribute('aria-label', 'Reason for rejection');
        input.setAttribute('aria-invalid', 'false');
        const error = create('p', 'reject-error', 'Please add a reason');
        error.hidden = true;

        ['Evidence unclear', 'Certificate expired', 'Wrong file', 'Needs more detail'].forEach((reason) => {
            const reasonButton = makeButton(reason, 'quick-reason', () => {
                input.value = reason;
                input.setAttribute('aria-invalid', 'false');
                error.hidden = true;
                input.focus();
            });
            reasonButton.addEventListener('click', (event) => event.preventDefault());
            reasons.append(reasonButton);
        });

        const controls = create('div', 'reject-panel-actions');
        const cancel = makeButton('Cancel', 'cancel-reject', () => panel.remove());
        const confirm = makeButton('Confirm reject', 'confirm-reject', async () => {
            const note = input.value.trim();
            if (!note) {
                input.setAttribute('aria-invalid', 'true');
                error.hidden = false;
                input.focus();
                return;
            }

            confirm.disabled = true;
            try {
                await changeStatus(student, item, 'rejected', note);
            } finally {
                confirm.disabled = false;
            }
        });

        panel.addEventListener('submit', (event) => event.preventDefault());
        controls.append(cancel, confirm);
        panel.append(reasons, input, error, controls);
        return panel;
    };

    const renderSubmission = (student, item) => {
        const row = create('article', 'submission-row');
        const description = create('div', 'submission-description');
        const title = create('strong', 'submission-title', item.title);
        const typeTag = create('span', `type-tag type-${item.type}`, getTypeLabel(item.type));
        const subtype = create('span', 'submission-subtype', item.subtype || '');
        const submitted = create('span', 'submission-time', `Submitted ${item.submitted || 'recently'}`);
        description.append(title, typeTag, subtype, submitted);

        const actions = create('div', 'submission-actions');
        const view = makeButton('View', 'action-button', () => openFilePreview(item));
        if (!item.has_evidence) {
            view.disabled = true;
            view.setAttribute('aria-disabled', 'true');
        }
        actions.append(view);
        if (item.has_evidence) {
            const download = create('a', 'action-button', 'Download');
            download.href = getDownloadUrl(item.type, item.id);
            actions.append(download);
        }
        if (item.status === 'pending') {
            const reject = makeButton('Reject', 'action-button reject', () => {
                const current = row.querySelector('.reject-panel');
                if (current) {
                    current.remove();
                } else {
                    row.append(buildRejectPanel(student, item));
                    row.querySelector('.reject-reason')?.focus();
                }
            });
            const isDeploymentDocument = item.category === 'deployment_document';
            const verify = makeButton(isDeploymentDocument ? 'Approve' : 'Verify', 'action-button verify', async () => {
                verify.disabled = true;
                try {
                    await changeStatus(student, item, isDeploymentDocument ? 'approved' : 'verified');
                } finally {
                    verify.disabled = false;
                }
            });
            actions.append(reject, verify);
        } else {
            const status = create('span', `completed-status ${item.status}`);
            status.textContent = item.category === 'resume'
                ? 'Available'
                : item.status === 'verified'
                    ? (item.category === 'deployment_document' ? '✓ Approved' : '✓ Verified')
                    : '✕ Rejected';
            actions.append(status);
            if (item.category !== 'resume') {
                const restore = makeButton('Move back to pending', 'move-pending-button', async () => {
                    restore.disabled = true;
                    try {
                        await changeStatus(student, item, item.category === 'deployment_document' ? 'submitted' : 'pending');
                    } finally {
                        restore.disabled = false;
                    }
                });
                actions.append(restore);
            }
        }

        row.append(description, actions);
        return row;
    };

    const renderStudent = (student) => {
        const matchingItems = student.items.filter(matchesItem);
        const card = create('article', 'student-group');
        const header = create('header', 'student-group-header');
        const identity = create('div', 'student-identity');
        identity.append(create('span', 'student-avatar', student.initials || '?'));
        const identityCopy = create('div', 'student-identity-copy');
        identityCopy.append(
            makeButton(student.name, 'student-name-button', () => openDrawer(student.id)),
            create('span', 'student-meta', `${student.program || 'Program not set'} · ${student.year_level || 'Year level not set'} · ID ${student.student_id_number || '—'}`),
        );
        identity.append(identityCopy);

        const progress = makeProgress(student.items);
        const progressWrap = create('div', 'student-progress');
        progressWrap.append(
            create('p', 'student-progress-label', `${progress.verified} of ${progress.total} verified`),
            progress.track,
        );

        const pendingCount = student.items.filter((item) => item.status === 'pending' && item.category !== 'deployment_document').length;
        const verifyAll = makeButton(`Verify all ${pendingCount} pending`, 'verify-all-button', () => verifyAllPending(student));
        verifyAll.disabled = pendingCount === 0;
        header.append(identity, progressWrap, verifyAll);

        const submissions = create('div', 'submission-list');
        matchingItems.forEach((item) => submissions.append(renderSubmission(student, item)));
        card.append(header, submissions);
        return card;
    };

    const render = () => {
        const students = visibleStudents();
        groups.replaceChildren(...students.map(renderStudent));
        emptyState.hidden = students.length > 0;
        updateCounters();
        if (state.activeStudentId !== null && !drawer.getAttribute('aria-hidden')?.includes('true')) {
            refreshDrawer();
        }
    };

    const showToast = (message, undoable = false) => {
        window.clearTimeout(state.undoTimer);
        document.getElementById('toastMessage').textContent = message;
        const undoButton = document.getElementById('undoAction');
        undoButton.hidden = !undoable;
        toast.hidden = false;
        state.undoTimer = window.setTimeout(() => {
            toast.hidden = true;
            undoButton.hidden = true;
            state.undo = null;
        }, 5000);
    };

    const requestJson = async (url, method, payload) => {
        const response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        });

        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
            const message = result.message
                || Object.values(result.errors ?? {}).flat()[0]
                || 'The request could not be completed.';
            throw new Error(message);
        }
        return result;
    };

    const findStudentItem = (studentId, item) => {
        const student = state.students.find((candidate) => candidate.id === studentId);
        const current = student?.items.find((candidate) => candidate.id === item.id && candidate.type === item.type);
        return { student, current };
    };

    const changeStatus = async (student, item, status, note = null, notify = true) => {
        const previous = { ...item };
        try {
            const result = await requestJson(getUpdateUrl(item.type, item.id), 'PATCH', { status, note });
            updateItem(student, result.item);
            render();
            state.undo = [{ studentId: student.id, previous }];
            showToast(result.message, true);
            if (notify) refreshDrawer();
        } catch (error) {
            showToast(error.message);
        }
    };

    const verifyAllPending = async (student) => {
        const pending = student.items.filter((item) => item.status === 'pending' && item.category !== 'deployment_document');
        if (!pending.length) return;

        try {
            const result = await requestJson(getVerifyAllUrl(student.id), 'POST', {});
            result.items.forEach((updated) => updateItem(student, updated));
            render();
            state.undo = pending.map((previous) => ({ studentId: student.id, previous: { ...previous } }));
            showToast(result.message, true);
            refreshDrawer();
        } catch (error) {
            showToast(error.message);
        }
    };

    const undoChanges = async () => {
        const undo = state.undo;
        if (!undo) return;
        document.getElementById('undoAction').disabled = true;

        try {
            for (const entry of undo) {
                const { student, current } = findStudentItem(entry.studentId, entry.previous);
                if (!student || !current) continue;
                const previous = entry.previous;
                const status = previous.category === 'deployment_document'
                    ? (previous.status === 'verified' ? 'approved' : 'submitted')
                    : previous.status;
                const result = await requestJson(getUpdateUrl(previous.type, previous.id), 'PATCH', {
                    status,
                    note: previous.reviewer_note ?? null,
                });
                updateItem(student, result.item);
            }

            state.undo = null;
            toast.hidden = true;
            render();
            refreshDrawer();
            showToast('Moved back to previous status');
            window.setTimeout(() => { toast.hidden = true; }, 1800);
        } catch (error) {
            showToast(error.message);
        } finally {
            document.getElementById('undoAction').disabled = false;
        }
    };

    const previewModal = document.getElementById('verificationFilePreview');
    const previewPane = document.getElementById('verificationPreviewPane');
    const openFilePreview = (item) => {
        document.getElementById('verificationPreviewTitle').textContent = item.title;
        document.getElementById('verificationPreviewCategory').textContent = item.category === 'deployment_document'
            ? 'Deployment document'
            : item.category === 'certificate' || item.type === 'certificates'
                ? 'Certificate'
                : item.category === 'resume' ? 'Resume' : item.category === 'project' ? 'Project' : item.subtype || 'File';
        document.getElementById('verificationPreviewIssuer').textContent = item.type === 'certificates' ? (item.subtype || 'Not provided') : 'Not provided';
        document.getElementById('verificationPreviewUploaded').textContent = item.created_at
            ? new Date(item.created_at).toLocaleDateString()
            : 'Unknown';
        document.getElementById('verificationPreviewStatus').textContent = item.category === 'resume'
            ? 'Available'
            : item.category === 'deployment_document' && item.status === 'verified'
                ? 'Approved'
                : item.status.charAt(0).toUpperCase() + item.status.slice(1);
        document.getElementById('verificationPreviewReviewer').textContent = item.reviewer_name || 'Not reviewed';
        document.getElementById('verificationPreviewReviewedAt').textContent = item.reviewed_at
            ? new Date(item.reviewed_at).toLocaleDateString()
            : 'Not reviewed';
        document.getElementById('verificationPreviewSize').textContent = item.file_size || 'Unavailable';
        document.getElementById('verificationPreviewDownload').href = getDownloadUrl(item.type, item.id);
        previewPane.replaceChildren();
        if (item.extension === 'pdf') {
            const frame = create('iframe', 'preview-frame');
            frame.title = `Preview of ${item.title}`;
            frame.src = getEvidenceUrl(item.type, item.id);
            frame.style.cssText = 'width:100%;min-height:60vh;border:1px solid #1c3250;background:#0b1a2e;';
            frame.addEventListener('error', () => {
                previewPane.replaceChildren(create('p', '', 'Preview not available. The file could not be loaded.'));
            }, { once: true });
            previewPane.append(frame);
        } else if (['jpg', 'jpeg', 'png', 'webp'].includes(item.extension)) {
            const image = create('img', 'preview-image');
            image.src = getEvidenceUrl(item.type, item.id);
            image.alt = item.title;
            image.style.cssText = 'display:block;max-width:100%;max-height:60vh;margin:auto;';
            image.addEventListener('error', () => {
                previewPane.replaceChildren(create('p', '', 'Preview not available. The file could not be loaded.'));
            }, { once: true });
            previewPane.append(image);
        } else {
            previewPane.append(create('p', '', 'This file type cannot be previewed here. Download the file to open it.'));
        }
        previewModal.showModal();
    };
    document.getElementById('closeVerificationPreview').addEventListener('click', () => previewModal.close());
    previewModal.addEventListener('click', (event) => {
        if (event.target === previewModal) previewModal.close();
    });

    const addDetail = (list, label, value) => {
        const item = create('div', 'drawer-detail');
        item.append(create('dt', '', label), create('dd', '', value || '—'));
        list.append(item);
    };

    const renderDrawer = (data) => {
        const student = data.student;
        document.getElementById('deploymentDocumentUploadForm').action = data.deployment_document_upload_url;
        const deploymentRequirementSelect = document.getElementById('deploymentDocumentType');
        deploymentRequirementSelect.replaceChildren();
        (data.coordinator_upload_requirements || []).forEach((requirement) => {
            const option = document.createElement('option');
            option.value = requirement.id;
            option.textContent = requirement.name;
            deploymentRequirementSelect.append(option);
        });
        document.getElementById('deploymentDocumentUploadSection').hidden =
            deploymentRequirementSelect.options.length === 0;
        document.getElementById('drawerAvatar').textContent = student.initials || '?';
        document.getElementById('drawerName').textContent = student.name;
        document.getElementById('drawerStudentId').textContent = `Student ID ${student.student_id_number || '—'}`;

        const details = document.getElementById('drawerDetails');
        details.replaceChildren();
        addDetail(details, 'Program', student.program);
        addDetail(details, 'Department', student.department);
        addDetail(details, 'Year level', student.year_level);
        addDetail(details, 'Email', student.email);
        addDetail(details, 'Internship field', student.preferred_internship_field);
        addDetail(details, 'Work setup', student.preferred_work_setup);
        addDetail(details, 'Location', student.preferred_location);

        const { verified, total, percent } = data.progress;
        document.getElementById('drawerProgressTitle').textContent = `Verification progress · ${verified} of ${total}`;
        document.getElementById('drawerProgressBar').style.width = `${percent}%`;
        document.getElementById('drawerProgressText').textContent = `${verified} of ${total} verified`;

        const portfolioStats = document.getElementById('drawerPortfolioStats');
        portfolioStats.replaceChildren();
        addDetail(portfolioStats, 'Total files', data.portfolio_stats.total);
        addDetail(portfolioStats, 'Certificates', data.portfolio_stats.certificates);
        addDetail(portfolioStats, 'Projects', data.portfolio_stats.projects);
        addDetail(portfolioStats, 'Storage used', `${data.portfolio_stats.storage_used} / 10 MB`);

        const drawerItems = document.getElementById('drawerItems');
        drawerItems.replaceChildren();
        data.items.forEach((item) => {
            const entry = create('li', 'drawer-item');
            const line = create('div', 'drawer-item-line');
            line.append(
                create('span', '', item.title),
                create('span', `drawer-item-status ${item.status}`, item.status),
            );
            entry.append(line);
            drawerItems.append(entry);
        });

        const activity = document.getElementById('drawerActivity');
        activity.replaceChildren();
        if (!data.activity.length) {
            activity.append(create('li', 'drawer-activity', 'No review activity yet.'));
        } else {
            data.activity.forEach((entry) => {
                const row = create('li', 'drawer-activity');
                row.append(
                    create('div', 'drawer-activity-line', `${entry.who} ${entry.action} ${entry.item}`),
                    create('p', '', `${entry.created_at}${entry.note ? ` · ${entry.note}` : ''}`),
                );
                activity.append(row);
            });
        }
    };

    const refreshDrawer = async () => {
        if (state.activeStudentId === null || drawer.getAttribute('aria-hidden') === 'true') return;
        try {
            const response = await fetch(getStudentUrl(state.activeStudentId), {
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) throw new Error('Unable to load student details.');
            renderDrawer(await response.json());
        } catch (error) {
            showToast(error.message);
        }
    };

    const openDrawer = async (studentId) => {
        state.returnFocus = document.activeElement;
        state.activeStudentId = studentId;
        drawer.setAttribute('aria-hidden', 'false');
        backdrop.hidden = false;
        requestAnimationFrame(() => {
            drawer.classList.add('is-open');
            backdrop.classList.add('is-open');
            drawer.focus();
        });
        await refreshDrawer();
    };

    const closeDrawer = () => {
        if (drawer.getAttribute('aria-hidden') === 'true') return;
        drawer.classList.remove('is-open');
        backdrop.classList.remove('is-open');
        drawer.setAttribute('aria-hidden', 'true');
        state.activeStudentId = null;
        window.setTimeout(() => { backdrop.hidden = true; }, 200);
        state.returnFocus?.focus?.();
    };

    document.getElementById('studentSearch').addEventListener('input', (event) => {
        state.search = event.target.value;
        render();
    });

    document.getElementById('programFilter').addEventListener('change', (event) => {
        state.program = event.target.value;
        render();
    });

    document.querySelectorAll('[data-filter-group]').forEach((button) => {
        button.addEventListener('click', () => {
            state[button.dataset.filterGroup] = button.dataset.filterValue;
            document.querySelectorAll(`[data-filter-group="${button.dataset.filterGroup}"]`).forEach((chip) => {
                const active = chip === button;
                chip.classList.toggle('is-active', active);
                chip.setAttribute('aria-pressed', String(active));
            });
            render();
        });
    });

    document.getElementById('clearFilters').addEventListener('click', () => {
        document.getElementById('studentSearch').value = '';
        document.getElementById('programFilter').value = '';
        state.search = '';
        state.program = '';
        state.type = 'all';
        state.status = 'all';
        document.querySelectorAll('[data-filter-group]').forEach((chip) => {
            const active = chip.dataset.filterValue === 'all'
                && (chip.dataset.filterGroup === 'type' || chip.dataset.filterGroup === 'status');
            chip.classList.toggle('is-active', active);
            chip.setAttribute('aria-pressed', String(active));
        });
        render();
    });

    document.getElementById('undoAction').addEventListener('click', undoChanges);
    document.getElementById('closeStudentDrawer').addEventListener('click', closeDrawer);
    backdrop.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeDrawer();
    });

    render();
})();
