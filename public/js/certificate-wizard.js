(() => {
    'use strict';

    const modalEl = document.getElementById('certificateWizard');
    const page = document.getElementById('competenciesOverview');
    if (!modalEl || !page) return;

    const form = document.getElementById('certificateWizardForm');
    const modal = window.bootstrap ? window.bootstrap.Modal.getOrCreateInstance(modalEl) : null;
    const JSON_HEADERS = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
    const stepFields = JSON.parse(modalEl.dataset.stepFields || '{}');
    const maxSkills = Number(modalEl.dataset.maxSkills || 15);
    const maxBytes = Number(modalEl.dataset.maxBytes || 5242880);
    const toast = document.getElementById('certificateToast');
    const MESSAGES = {
        certificate_type: 'Please choose Seminar, Workshop, Training, or Certificate.',
        name: 'Please type the title of your certificate.',
        skill_type: 'Please choose Technical or Soft Skill.',
        proficiency_level: 'Please choose Beginner, Intermediate, or Advanced.',
        skills: 'Add at least 1 skill you learned.',
        duplicate: 'You already added this skill.',
        tooMany: `You can add up to ${maxSkills} skills per certificate.`,
        noFile: 'Please upload a photo or PDF of your certificate.',
        fileType: 'Only PDF, JPG, or PNG files are allowed.',
        fileSize: 'The file is too large. The limit is 5 MB.',
    };

    const el = (selector) => modalEl.querySelector(selector);
    const steps = Array.from(modalEl.querySelectorAll('[data-step]'));
    const skillInput = el('#cwSkillInput');
    const chips = el('[data-skill-chips]');
    const skillInputs = el('[data-skill-inputs]');
    const suggestionList = el('#cwSkillSuggestions');
    const fileInput = el('#cwEvidence');
    const formError = el('[data-wizard-error]');
    const titleInput = el('#cwTitle');

    let step = 1;
    let skills = [];
    let mode = 'add';
    let fileRequired = true;
    let dirty = false;
    let closingConfirmed = false;
    let submitting = false;
    let suggestTimer;
    let suggestions = [];
    let activeSuggestion = -1;

    const showToast = (message) => {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add('is-visible');
        window.clearTimeout(showToast.timer);
        showToast.timer = window.setTimeout(() => toast.classList.remove('is-visible'), 3200);
    };
    const normalize = (value) => value.replace(/\s+/g, ' ').trim();

    /* ---------- Errors ---------- */
    const stepOf = (field) => {
        const base = field.split('.')[0];
        return Number(Object.keys(stepFields).find((key) => stepFields[key].includes(base)) || 3);
    };
    const setError = (field, message) => {
        const box = modalEl.querySelector(`[data-error-for="${field}"]`);
        if (box) box.textContent = message || '';
        modalEl.querySelectorAll(`[name="${field}"]`).forEach((input) => {
            input.classList.toggle('is-invalid', Boolean(message));
            input.setAttribute('aria-invalid', message ? 'true' : 'false');
        });
        if (field === 'skills') skillInput.classList.toggle('is-invalid', Boolean(message));
    };
    const clearErrors = () => {
        modalEl.querySelectorAll('[data-error-for]').forEach((box) => setError(box.dataset.errorFor, ''));
        formError.hidden = true;
    };
    const showErrors = (errors) => {
        Object.entries(errors).forEach(([field, message]) => setError(field.split('.')[0], message));
        const firstStep = Math.min(...Object.keys(errors).map(stepOf));
        goTo(firstStep);
        const firstField = Object.keys(errors).map((field) => field.split('.')[0]).find((field) => stepOf(field) === firstStep);
        (modalEl.querySelector(`[name="${firstField}"]`) || (firstField === 'skills' ? skillInput : null))?.focus();
    };

    /* ---------- Validation (mirrors StoreCompetencyRequest) ---------- */
    const checked = (name) => form.querySelector(`[name="${name}"]:checked`)?.value || '';
    const validate = (stepNumber) => {
        const errors = {};
        if (stepNumber === 1) {
            if (!checked('certificate_type')) errors.certificate_type = MESSAGES.certificate_type;
            if (!normalize(titleInput.value)) errors.name = MESSAGES.name;
            if (!checked('skill_type')) errors.skill_type = MESSAGES.skill_type;
            if (!checked('proficiency_level')) errors.proficiency_level = MESSAGES.proficiency_level;
        }
        if (stepNumber === 2 && skills.length === 0) errors.skills = MESSAGES.skills;
        if (stepNumber === 3) {
            const file = fileInput.files[0];
            if (!file && fileRequired) errors.evidence = MESSAGES.noFile;
            if (file && !/\.(pdf|jpe?g|png)$/i.test(file.name)) errors.evidence = MESSAGES.fileType;
            else if (file && file.size > maxBytes) errors.evidence = MESSAGES.fileSize;
        }
        return errors;
    };

    /* ---------- Steps ---------- */
    const goTo = (number) => {
        step = number;
        steps.forEach((fieldset) => { fieldset.hidden = Number(fieldset.dataset.step) !== step; });
        el('[data-wizard-progress]').textContent = `Step ${step} of 3`;
        modalEl.querySelectorAll('[data-progress-step]').forEach((bar) => {
            bar.classList.toggle('is-done', Number(bar.dataset.progressStep) <= step);
        });
        el('[data-wizard-back]').hidden = step === 1;
        el('[data-wizard-next]').hidden = step === 3;
        el('[data-wizard-submit]').hidden = step !== 3;
    };
    el('[data-wizard-next]').addEventListener('click', () => {
        clearErrors();
        const errors = validate(step);
        if (Object.keys(errors).length) {
            showErrors(errors);
            return;
        }
        goTo(step + 1);
        steps[step - 1].querySelector('input:not([type="hidden"]), textarea')?.focus();
    });
    el('[data-wizard-back]').addEventListener('click', () => {
        clearErrors();
        goTo(step - 1);
    });

    /* ---------- Title example follows the chosen type ---------- */
    const titleHint = el('[data-title-example]');
    const updateTitleExample = () => {
        const selected = form.querySelector('[name="certificate_type"]:checked');
        titleHint.textContent = selected ? `Example: ${selected.dataset.example}` : 'The name of the certificate or activity.';
    };
    form.querySelectorAll('[name="certificate_type"]').forEach((radio) => radio.addEventListener('change', updateTitleExample));

    /* ---------- Skills you learned (tag input) ---------- */
    const renderSkills = () => {
        chips.replaceChildren(...skills.map((name, index) => {
            const chip = document.createElement('li');
            chip.className = 'cw-chip';
            const text = document.createElement('span');
            text.textContent = name;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'cw-chip-remove';
            remove.setAttribute('aria-label', `Remove ${name}`);
            remove.textContent = '×';
            remove.addEventListener('click', () => {
                skills.splice(index, 1);
                dirty = true;
                renderSkills();
                skillInput.focus();
            });
            chip.append(text, remove);
            return chip;
        }));
        skillInputs.replaceChildren(...skills.map((name) => {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'skills[]';
            hidden.value = name;
            return hidden;
        }));
        el('[data-skill-count]').textContent = skills.length;
        const atLimit = skills.length >= maxSkills;
        skillInput.disabled = atLimit;
        el('[data-skill-add]').disabled = atLimit;
    };
    const addSkill = (raw) => {
        const name = normalize(raw);
        closeSuggestions();
        if (!name) return;
        if (skills.some((existing) => existing.toLocaleLowerCase() === name.toLocaleLowerCase())) {
            setError('skills', MESSAGES.duplicate);
            return;
        }
        if (skills.length >= maxSkills) {
            setError('skills', MESSAGES.tooMany);
            return;
        }
        skills.push(name.slice(0, 60));
        dirty = true;
        setError('skills', '');
        skillInput.value = '';
        renderSkills();
        if (!skillInput.disabled) skillInput.focus();
    };
    el('[data-skill-add]').addEventListener('click', () => addSkill(skillInput.value));

    const closeSuggestions = () => {
        suggestions = [];
        activeSuggestion = -1;
        suggestionList.hidden = true;
        suggestionList.replaceChildren();
        skillInput.setAttribute('aria-expanded', 'false');
        skillInput.removeAttribute('aria-activedescendant');
    };
    const highlight = (index) => {
        activeSuggestion = index;
        Array.from(suggestionList.children).forEach((option, optionIndex) => option.setAttribute('aria-selected', String(optionIndex === index)));
        skillInput.setAttribute('aria-activedescendant', `cwSuggestion-${index}`);
    };
    skillInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            addSkill(activeSuggestion >= 0 ? suggestions[activeSuggestion] : skillInput.value);
        } else if (event.key === 'ArrowDown' && suggestions.length) {
            event.preventDefault();
            highlight((activeSuggestion + 1) % suggestions.length);
        } else if (event.key === 'ArrowUp' && suggestions.length) {
            event.preventDefault();
            highlight((activeSuggestion - 1 + suggestions.length) % suggestions.length);
        } else if (event.key === 'Escape' && suggestions.length) {
            event.stopPropagation();
            closeSuggestions();
        }
    });
    skillInput.addEventListener('input', () => {
        setError('skills', '');
        window.clearTimeout(suggestTimer);
        const term = normalize(skillInput.value);
        if (!term) {
            closeSuggestions();
            return;
        }
        suggestTimer = window.setTimeout(async () => {
            try {
                const response = await fetch(`${modalEl.dataset.suggestUrl}?q=${encodeURIComponent(term)}`, { headers: JSON_HEADERS });
                const payload = await response.json();
                if (normalize(skillInput.value) !== term) return;
                suggestions = (payload.suggestions || [])
                    .filter((name) => !skills.some((existing) => existing.toLocaleLowerCase() === name.toLocaleLowerCase()));
                activeSuggestion = -1;
                suggestionList.replaceChildren(...suggestions.map((name, index) => {
                    const option = document.createElement('li');
                    option.id = `cwSuggestion-${index}`;
                    option.setAttribute('role', 'option');
                    option.setAttribute('aria-selected', 'false');
                    option.textContent = name;
                    option.addEventListener('mousedown', (event) => {
                        event.preventDefault();
                        addSkill(name);
                    });
                    return option;
                }));
                suggestionList.hidden = suggestions.length === 0;
                skillInput.setAttribute('aria-expanded', String(suggestions.length > 0));
            } catch {
                closeSuggestions();
            }
        }, 150);
    });
    skillInput.addEventListener('blur', () => window.setTimeout(closeSuggestions, 120));

    /* ---------- Other fields ---------- */
    const noteCount = el('[data-note-count]');
    form.elements.description.addEventListener('input', () => { noteCount.textContent = form.elements.description.value.length; });
    fileInput.addEventListener('change', () => {
        setError('evidence', '');
        const errors = validate(3);
        if (errors.evidence && fileInput.files.length) {
            setError('evidence', errors.evidence);
            fileInput.value = '';
        }
    });
    form.addEventListener('input', () => { dirty = true; });
    form.addEventListener('change', () => { dirty = true; });

    /* ---------- Open / close ---------- */
    const setRadio = (name, value) => {
        form.querySelectorAll(`[name="${name}"]`).forEach((radio) => { radio.checked = radio.value === value; });
    };
    const open = (nextMode, values = {}, options = {}) => {
        form.reset();
        clearErrors();
        mode = nextMode;
        skills = [...(values.skills || [])];
        renderSkills();
        setRadio('certificate_type', values.certificate_type || '');
        setRadio('skill_type', values.skill_type || '');
        el('[data-expert-option]').hidden = values.proficiency_level !== 'expert';
        setRadio('proficiency_level', values.proficiency_level || '');
        titleInput.value = values.name || '';
        form.elements.issuing_organization.value = values.issuing_organization || '';
        form.elements.obtained_at.value = values.obtained_at || '';
        form.elements.description.value = values.description || '';
        noteCount.textContent = form.elements.description.value.length;
        updateTitleExample();

        form.action = options.action || modalEl.dataset.storeUrl;
        form.querySelector('[data-method-field]').value = mode === 'edit' ? 'PUT' : 'POST';
        form.dataset.legacyId = options.legacyId || '';
        el('[data-wizard-mode]').value = mode;
        el('[data-wizard-action]').value = form.getAttribute('action');
        el('[data-wizard-legacy-id]').value = options.legacyId || '';
        fileRequired = mode === 'add' || (mode === 'legacy' && !values.evidence_name);
        el('[data-file-required-mark]').hidden = !fileRequired;
        const currentFile = el('[data-current-file]');
        currentFile.hidden = !values.evidence_name;
        currentFile.textContent = values.evidence_name ? `Current file: ${values.evidence_name}. Choose a new file only if you want to replace it.` : '';
        el('[data-wizard-title]').textContent = { add: 'Add a certificate', edit: 'Edit certificate', legacy: 'Add the missing details' }[mode];
        el('[data-wizard-submit]').textContent = mode === 'add' ? 'Add certificate' : 'Save certificate';

        dirty = false;
        closingConfirmed = false;
        submitting = false;
        goTo(1);
        modal?.show();
    };

    modalEl.addEventListener('shown.bs.modal', () => form.querySelector('[name="certificate_type"]')?.focus());
    modalEl.addEventListener('hide.bs.modal', (event) => {
        if (submitting || closingConfirmed || !dirty) return;
        if (!window.confirm('Discard this certificate?')) {
            event.preventDefault();
            return;
        }
        closingConfirmed = true;
    });
    // Reopening starts fresh.
    modalEl.addEventListener('hidden.bs.modal', () => {
        form.reset();
        skills = [];
        renderSkills();
        clearErrors();
        dirty = false;
    });
    modalEl.querySelectorAll('[data-wizard-cancel]').forEach((button) => button.addEventListener('click', () => modal?.hide()));

    document.addEventListener('click', (event) => {
        const addButton = event.target.closest('[data-wizard-open]');
        if (addButton) {
            open('add');
            return;
        }
        const editButton = event.target.closest('[data-wizard-edit]');
        if (editButton) {
            const certificate = window.sbCertificates?.find(editButton.dataset.wizardEdit);
            if (certificate) open('edit', certificate.form, { action: modalEl.dataset.updateUrlTemplate.replace('__ID__', certificate.id) });
            return;
        }
        const legacyButton = event.target.closest('[data-wizard-legacy]');
        if (legacyButton) {
            const legacy = JSON.parse(legacyButton.dataset.wizardLegacy);
            open('legacy', {
                certificate_type: 'certificate',
                name: legacy.title,
                issuing_organization: legacy.issuer,
                obtained_at: legacy.obtained_at,
                evidence_name: legacy.evidence_name,
            }, { action: legacy.complete_url, legacyId: legacy.id });
            return;
        }
        const deleteButton = event.target.closest('[data-certificate-delete-url]');
        if (deleteButton) {
            const deleteForm = document.createElement('form');
            deleteForm.method = 'POST';
            deleteForm.action = deleteButton.dataset.certificateDeleteUrl;
            deleteForm.dataset.certificateDelete = '';
            deleteForm.dataset.confirm = `Delete "${deleteButton.dataset.certificateName}"? This cannot be undone.`;
            deleteForm.dataset.confirmTitle = 'Delete certificate';
            deleteForm.hidden = true;
            deleteForm.innerHTML = `<input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_token" value="${form.elements._token.value}">`;
            document.body.append(deleteForm);
            deleteForm.requestSubmit();
        }
    });

    /* ---------- Submit ---------- */
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors();
        const errors = { ...validate(1), ...validate(2), ...validate(3) };
        if (Object.keys(errors).length) {
            showErrors(errors);
            return;
        }
        const submitButton = el('[data-wizard-submit]');
        submitButton.disabled = true;
        submitButton.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: JSON_HEADERS });
            const payload = await response.json().catch(() => ({
                message: response.status === 413 ? MESSAGES.fileSize : `Something went wrong (HTTP ${response.status}). Please try again.`,
            }));
            if (response.status === 422 && payload.errors) {
                showErrors(Object.fromEntries(Object.entries(payload.errors).map(([field, messages]) => [field, messages[0]])));
                return;
            }
            if (!response.ok) throw new Error(payload.message || 'Unable to save this certificate.');

            submitting = true;
            modal?.hide();
            if (payload.completed_legacy_id) {
                page.querySelector(`[data-legacy-id="${payload.completed_legacy_id}"]`)?.remove();
                const legacySection = document.getElementById('legacyCertificates');
                if (legacySection && !legacySection.querySelector('[data-legacy-id]')) legacySection.remove();
            }
            document.dispatchEvent(new CustomEvent('certificates:changed', { detail: { overview: payload.overview, selectId: payload.id } }));
            showToast(payload.message);
        } catch (error) {
            formError.textContent = error.message;
            formError.hidden = false;
        } finally {
            submitButton.disabled = false;
            submitButton.removeAttribute('aria-busy');
        }
    });

    // Runs after the shared confirm dialog approves the delete.
    document.addEventListener('submit', async (event) => {
        const deleteForm = event.target;
        if (!(deleteForm instanceof HTMLFormElement) || !deleteForm.matches('[data-certificate-delete]')) return;
        event.preventDefault();
        const response = await fetch(deleteForm.action, { method: 'POST', body: new FormData(deleteForm), headers: JSON_HEADERS });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            showToast(payload.message || 'Unable to delete this certificate.');
            return;
        }
        const legacyRow = deleteForm.closest('[data-legacy-id]');
        if (legacyRow) {
            legacyRow.remove();
            const legacySection = document.getElementById('legacyCertificates');
            if (legacySection && !legacySection.querySelector('[data-legacy-id]')) legacySection.remove();
        } else {
            deleteForm.remove();
        }
        if (payload.overview) document.dispatchEvent(new CustomEvent('certificates:changed', { detail: { overview: payload.overview } }));
        showToast(payload.message || 'Certificate deleted.');
    });

    if (page.dataset.flashSuccess) showToast(page.dataset.flashSuccess);
    if (modalEl.dataset.old) {
        // Server-side fallback: reopen with the submitted data and jump to the step with the error.
        const old = JSON.parse(modalEl.dataset.old);
        open(old.mode || 'add', old.values || {}, { action: old.action, legacyId: old.legacy_id });
        dirty = true;
        if (old.errors && Object.keys(old.errors).length) showErrors(old.errors);
    } else if (modalEl.dataset.openAdd) open('add');
    if (modalEl.dataset.openEdit) {
        window.setTimeout(() => {
            const certificate = window.sbCertificates?.find(modalEl.dataset.openEdit);
            if (certificate) open('edit', certificate.form, { action: modalEl.dataset.updateUrlTemplate.replace('__ID__', certificate.id) });
        }, 0);
    }
})();
