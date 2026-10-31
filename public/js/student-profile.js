(() => {
    'use strict';

    const page = document.getElementById('studentProfilePage');
    if (!page) return;
    page.classList.add('js-enhanced');

    const JSON_HEADERS = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
    const tabs = Array.from(page.querySelectorAll('[role="tab"]'));
    const panels = Array.from(page.querySelectorAll('[role="tabpanel"]'));
    const toast = document.getElementById('profileToast');
    let toastTimer;

    const readJson = async (response) => response.json().catch(() => ({
        message: response.status === 413 ? 'The file is too large to upload.' : `Request failed (HTTP ${response.status}). Please try again.`,
    }));
    const send = (url, body) => fetch(url, { method: 'POST', body, headers: JSON_HEADERS });
    const firstError = (payload) => (payload.errors ? Object.values(payload.errors).flat()[0] : null) || payload.message;

    const showToast = (message) => {
        toast.textContent = message;
        toast.classList.add('is-visible');
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => toast.classList.remove('is-visible'), 2600);
    };

    /* ---------- Tabs ---------- */
    const activateTab = (name, updateHash = false) => {
        if (!tabs.some((tab) => tab.dataset.tab === name)) name = 'personal';
        tabs.forEach((tab) => {
            const selected = tab.dataset.tab === name;
            tab.setAttribute('aria-selected', String(selected));
            tab.tabIndex = selected ? 0 : -1;
        });
        panels.forEach((panel) => { panel.hidden = panel.id !== `panel-${name}`; });
        if (updateHash && window.location.hash !== `#${name}`) {
            history.replaceState(null, '', `#${name}`);
        }
    };
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activateTab(tab.dataset.tab, true));
        tab.addEventListener('keydown', (event) => {
            const moves = { ArrowRight: index + 1, ArrowLeft: index - 1, Home: 0, End: tabs.length - 1 };
            if (!(event.key in moves)) return;
            event.preventDefault();
            const next = tabs[(moves[event.key] + tabs.length) % tabs.length];
            next.focus();
            activateTab(next.dataset.tab, true);
        });
    });
    const activateFromHash = () => activateTab(window.location.hash.slice(1) || 'personal');
    window.addEventListener('hashchange', activateFromHash);
    activateFromHash();
    const tabFor = (element) => element.closest('[role="tabpanel"]')?.id.replace('panel-', '');
    const markTabError = (name) => page.querySelector(`#tab-${name}`)?.classList.add('has-error');

    /* ---------- Completion bar ---------- */
    const updateCompletion = (payload) => {
        if (typeof payload.profile_completion !== 'number') return;
        document.getElementById('completionValue').textContent = payload.profile_completion;
        document.getElementById('completionFill').style.width = `${payload.profile_completion}%`;
        page.querySelector('.completion-track').setAttribute('aria-valuenow', payload.profile_completion);
        const missing = payload.missing_steps || [];
        document.getElementById('completionSteps').hidden = missing.length === 0;
        document.getElementById('completionStepsSummary').textContent = missing.length
            ? `${payload.remaining_steps} ${payload.remaining_steps === 1 ? 'step' : 'steps'} left`
            : 'Profile complete';
        document.getElementById('completionMissingSteps').replaceChildren(...missing.map((step) => {
            const item = document.createElement('li');
            item.textContent = step;
            return item;
        }));
        const completed = payload.completed_sections || [];
        tabs.forEach((tab) => tab.classList.toggle('is-complete', completed.includes(tab.dataset.tab)));
    };

    /* ---------- Inline errors ---------- */
    const clearErrors = (form) => {
        form.querySelectorAll('.is-invalid').forEach((field) => field.classList.remove('is-invalid'));
        form.querySelectorAll('[data-client-error]').forEach((node) => node.remove());
    };
    const showErrors = (form, errors) => {
        clearErrors(form);
        let shown = 0;
        Object.entries(errors).forEach(([name, messages]) => {
            if (name === 'privacy_consent') return;
            const field = Array.from(form.elements).find((element) => element.name === name && element.type !== 'hidden');
            if (!field) {
                showToast(messages[0]);
                return;
            }
            field.classList.add('is-invalid');
            const error = document.createElement('div');
            error.className = 'invalid-feedback';
            error.dataset.clientError = 'true';
            error.textContent = messages[0];
            (field.closest('.input-group') || field).insertAdjacentElement('afterend', error);
            shown += 1;
        });
        const name = tabFor(form);
        if (name && shown) markTabError(name);
        form.querySelector('.is-invalid')?.focus();
    };

    /* ---------- Privacy consent ---------- */
    const consentCard = document.getElementById('privacyConsentCard');
    const consentError = document.getElementById('privacyConsentError');
    const showConsentRequired = (message) => {
        if (!consentCard) return;
        consentError.textContent = message;
        consentCard.classList.add('needs-attention');
        consentCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
        document.getElementById('privacyConsentCheckbox').focus({ preventScroll: true });
    };
    // Returns true when the save was blocked for missing consent (and the user was told).
    const handleConsentError = (payload) => {
        const message = payload.errors?.privacy_consent?.[0];
        if (!message) return false;
        showConsentRequired(message);
        showToast(message);
        return true;
    };
    document.getElementById('privacyConsentForm')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        if (!form.elements.privacy_consent.checked) {
            showConsentRequired('Tick the box to give your consent.');
            return;
        }
        const response = await send(form.action, new FormData(form));
        const payload = await readJson(response);
        if (!response.ok) {
            showConsentRequired(firstError(payload) || 'Unable to record consent.');
            return;
        }
        consentCard.remove();
        showToast(payload.message || 'Privacy consent recorded.');
    });

    /* ---------- Section forms (Personal / Contact / Academic) ---------- */
    const sectionForms = Array.from(page.querySelectorAll('form[data-profile-section]'));
    const dirtyForms = new Set();
    const setDirty = (form, dirty) => {
        dirty ? dirtyForms.add(form) : dirtyForms.delete(form);
        const indicator = form.querySelector('[data-dirty-indicator]');
        if (indicator) indicator.hidden = !dirty;
    };
    const rememberDefaults = (form) => {
        Array.from(form.elements).forEach((field) => {
            if (field instanceof HTMLSelectElement) {
                Array.from(field.options).forEach((option) => { option.defaultSelected = option.selected; });
            } else if (field instanceof HTMLTextAreaElement) {
                field.defaultValue = field.value;
            } else if (field instanceof HTMLInputElement && ['checkbox', 'radio'].includes(field.type)) {
                field.defaultChecked = field.checked;
            } else if (field instanceof HTMLInputElement && !['hidden', 'submit', 'button', 'file'].includes(field.type)) {
                field.defaultValue = field.value;
            }
        });
    };
    window.addEventListener('beforeunload', (event) => {
        if (dirtyForms.size) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    const studentIdField = document.getElementById('student_number');
    const studentIdNotice = document.getElementById('studentIdCorrectionNotice');
    const updateStudentIdNotice = () => {
        if (!studentIdField || !studentIdNotice) return;
        const value = studentIdField.value.trim().toUpperCase();
        try {
            studentIdNotice.hidden = value === '' || new RegExp(studentIdField.dataset.studentIdPattern, 'u').test(value);
        } catch {
            studentIdNotice.hidden = true;
        }
    };
    studentIdField?.addEventListener('input', updateStudentIdNotice);
    updateStudentIdNotice();

    const programSelect = document.getElementById('program_id');
    const majorSelect = document.getElementById('major_id');
    const syncProgramFields = () => {
        if (!programSelect || !majorSelect) return;
        const selectedId = programSelect.value;
        const majorOptions = Array.from(majorSelect.options).filter((option) => option.dataset.programId);
        majorOptions.forEach((option) => {
            const belongs = option.dataset.programId === selectedId;
            option.hidden = !belongs;
            option.disabled = !belongs;
        });
        document.getElementById('majorField').hidden = !majorOptions.some((option) => option.dataset.programId === selectedId);
        if (majorSelect.selectedOptions[0]?.dataset.programId !== selectedId) majorSelect.value = '';
        document.getElementById('assigned_coordinator').value = programSelect.selectedOptions[0]?.dataset.coordinator || 'Not assigned';
    };
    programSelect?.addEventListener('change', syncProgramFields);
    syncProgramFields();

    sectionForms.forEach((form) => {
        ['input', 'change'].forEach((type) => form.addEventListener(type, (event) => {
            if (event.target.name) setDirty(form, true);
        }));
        form.querySelector('[data-cancel-section]')?.addEventListener('click', () => {
            form.reset();
            syncProgramFields();
            updateStudentIdNotice();
            clearErrors(form);
            setDirty(form, false);
        });
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const saveButton = form.querySelector('button[type="submit"]');
            const label = saveButton.textContent;
            saveButton.disabled = true;
            saveButton.setAttribute('aria-busy', 'true');
            saveButton.textContent = 'Saving…';
            clearErrors(form);
            page.querySelector(`#tab-${tabFor(form)}`)?.classList.remove('has-error');
            try {
                const response = await send(form.action, new FormData(form));
                const payload = await readJson(response);
                if (response.status === 422 && payload.errors) {
                    handleConsentError(payload);
                    showErrors(form, payload.errors);
                    return;
                }
                if (!response.ok) throw new Error(payload.message || 'Unable to save profile.');

                if (studentIdField && 'student_id_number' in payload) {
                    studentIdField.value = payload.student_id_number || '';
                    studentIdNotice.hidden = !payload.student_id_needs_correction;
                }
                rememberDefaults(form);
                setDirty(form, false);
                updateCompletion(payload);
                if (form.dataset.profileSection === 'personal') {
                    document.getElementById('profileDisplayName').textContent = ['first_name', 'middle_name', 'last_name', 'suffix']
                        .map((name) => form.elements.namedItem(name)?.value.trim())
                        .filter(Boolean).join(' ');
                }
                if (form.dataset.profileSection === 'academic' && programSelect?.value) {
                    document.getElementById('profileDisplayProgram').textContent = programSelect.selectedOptions[0].textContent.trim();
                }
                consentCard?.remove();
                showToast('Profile saved');
            } catch (error) {
                showToast(error.message || 'Unable to save profile.');
            } finally {
                saveButton.disabled = false;
                saveButton.removeAttribute('aria-busy');
                saveButton.textContent = label;
            }
        });
    });

    /* ---------- Change-request modal ---------- */
    const changeRequestModal = document.getElementById('studentChangeRequestModal');
    if (changeRequestModal) {
        const fieldInput = document.getElementById('changeRequestField');
        const inputs = {
            student_id_number: ['changeRequestStudentId', 'changeRequestStudentIdField'],
            program_id: ['changeRequestProgram', 'changeRequestProgramField'],
            year_level: ['changeRequestYear', 'changeRequestYearField'],
        };
        const setField = (field) => {
            fieldInput.value = field;
            Object.entries(inputs).forEach(([name, [inputId, groupId]]) => {
                document.getElementById(inputId).disabled = name !== field;
                document.getElementById(groupId).hidden = name !== field;
            });
        };
        page.querySelectorAll('[data-request-change-field]').forEach((button) => button.addEventListener('click', () => {
            setField(button.dataset.requestChangeField);
            changeRequestModal.showModal();
        }));
        ['closeStudentChangeRequest', 'cancelStudentChangeRequest'].forEach((id) => {
            document.getElementById(id).addEventListener('click', () => changeRequestModal.close());
        });
        if (fieldInput.value) setField(fieldInput.value);
    }

    /* ---------- Profile photo (header, Personal tab, top bar, sidebar card) ---------- */
    const PHOTO_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    const PHOTO_MAX_BYTES = 5 * 1024 * 1024;
    const PHOTO_TYPE_MESSAGE = 'Only JPG, PNG, or WebP images are allowed.';
    const PHOTO_SIZE_MESSAGE = 'The photo is too large. The limit is 5 MB.';
    const photoInput = document.getElementById('profilePictureInput');
    const photoError = page.querySelector('[data-photo-error]');
    const pickButtons = page.querySelectorAll('[data-photo-pick]');
    const removeButton = page.querySelector('[data-photo-remove]');

    // Every avatar on the page, including the top bar and sidebar outside the profile page.
    const setAvatars = (url) => {
        document.querySelectorAll('[data-avatar]').forEach((avatar) => {
            const initials = avatar.querySelector('[data-avatar-initials]');
            let image = avatar.querySelector('[data-avatar-image]');
            if (url) {
                if (!image) {
                    image = document.createElement('img');
                    image.dataset.avatarImage = '';
                    image.alt = '';
                    avatar.prepend(image);
                }
                image.hidden = false;
                image.src = url;
                if (initials) initials.hidden = true;
            } else {
                image?.remove();
                if (initials) initials.hidden = false;
            }
        });
        pickButtons.forEach((button) => { button.textContent = url ? 'Change photo' : 'Upload photo'; });
        if (removeButton) removeButton.hidden = !url;
    };
    const setPhotoError = (message) => {
        if (photoError) photoError.textContent = message || '';
        if (message) showToast(message);
    };
    const currentPhoto = () => document.querySelector('[data-avatar] [data-avatar-image]:not([hidden])')?.getAttribute('src') || null;
    // Local previews use data URLs (no object URLs on this page).
    const readAsDataUrl = (file) => new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = () => reject(new Error(PHOTO_TYPE_MESSAGE));
        reader.readAsDataURL(file);
    });

    pickButtons.forEach((button) => button.addEventListener('click', () => photoInput.click()));
    photoInput.addEventListener('change', async () => {
        const file = photoInput.files[0];
        if (!file) return;
        setPhotoError('');
        if (!PHOTO_TYPES.includes(file.type)) {
            setPhotoError(PHOTO_TYPE_MESSAGE);
            photoInput.value = '';
            return;
        }
        if (file.size > PHOTO_MAX_BYTES) {
            setPhotoError(PHOTO_SIZE_MESSAGE);
            photoInput.value = '';
            return;
        }

        // Live preview while the server crops and saves it.
        const previous = currentPhoto();
        pickButtons.forEach((button) => { button.disabled = true; });
        try {
            setAvatars(await readAsDataUrl(file));
            const photoForm = document.getElementById('profilePictureForm');
            const response = await send(photoForm.action, new FormData(photoForm));
            const payload = await readJson(response);
            if (!response.ok) throw new Error(firstError(payload) || 'Unable to upload profile photo.');
            setAvatars(payload.profile_picture_url);
            updateCompletion(payload);
            showToast(payload.message || 'Profile photo updated.');
        } catch (error) {
            setAvatars(previous);
            setPhotoError(error.message || 'Unable to upload profile photo.');
        } finally {
            pickButtons.forEach((button) => { button.disabled = false; });
            photoInput.value = '';
        }
    });

    removeButton?.addEventListener('click', async () => {
        removeButton.disabled = true;
        try {
            const removeForm = document.getElementById('profilePictureRemoveForm');
            const response = await send(removeForm.action, new FormData(removeForm));
            const payload = await readJson(response);
            if (!response.ok) throw new Error(firstError(payload) || 'Unable to remove the photo.');
            setAvatars(null);
            setPhotoError('');
            showToast(payload.message || 'Profile photo removed.');
        } catch (error) {
            setPhotoError(error.message);
        } finally {
            removeButton.disabled = false;
        }
    });

    /* ---------- 2x2 picture (Documents tab): crop preview, then upload ---------- */
    const idPhotoForm = page.querySelector('[data-id-photo-form]');
    if (idPhotoForm) {
        const input = idPhotoForm.querySelector('[data-id-photo-input]');
        const dialog = page.querySelector('[data-id-photo-dialog]');
        const frame = dialog.querySelector('[data-crop-frame]');
        const cropImage = dialog.querySelector('[data-crop-image]');
        const zoom = dialog.querySelector('[data-crop-zoom]');
        const saveButton = dialog.querySelector('[data-crop-save]');
        const cropError = dialog.querySelector('[data-crop-error]');
        const cardError = page.querySelector('[data-id-photo-error]');
        const pickButton = page.querySelector('[data-id-photo-pick]');
        const removeForm = page.querySelector('[data-id-photo-remove]');
        const preview = page.querySelector('[data-id-photo-preview]');
        // Image position inside the square frame: scale (px per image px) and top-left offset.
        const view = { width: 0, height: 0, baseScale: 1, scale: 1, x: 0, y: 0 };

        const frameSize = () => frame.clientWidth;
        const clamp = () => {
            const size = frameSize();
            view.x = Math.min(0, Math.max(size - view.width * view.scale, view.x));
            view.y = Math.min(0, Math.max(size - view.height * view.scale, view.y));
        };
        const draw = () => {
            clamp();
            cropImage.style.width = `${view.width * view.scale}px`;
            cropImage.style.height = `${view.height * view.scale}px`;
            cropImage.style.transform = `translate(${view.x}px, ${view.y}px)`;
        };
        const close = () => {
            dialog.close();
            input.value = '';
            cropImage.onerror = null;
            cropImage.removeAttribute('src');
        };

        pickButton.addEventListener('click', () => input.click());
        input.addEventListener('change', async () => {
            const file = input.files[0];
            if (!file) return;
            cardError.textContent = '';
            if (!PHOTO_TYPES.includes(file.type)) {
                cardError.textContent = PHOTO_TYPE_MESSAGE;
                input.value = '';
                return;
            }
            if (file.size > PHOTO_MAX_BYTES) {
                cardError.textContent = PHOTO_SIZE_MESSAGE;
                input.value = '';
                return;
            }
            cropError.textContent = '';
            zoom.value = '1';
            cropImage.onload = () => {
                // Start zoomed to cover the square, centred.
                view.width = cropImage.naturalWidth;
                view.height = cropImage.naturalHeight;
                view.baseScale = frameSize() / Math.min(view.width, view.height);
                view.scale = view.baseScale;
                view.x = (frameSize() - view.width * view.scale) / 2;
                view.y = (frameSize() - view.height * view.scale) / 2;
                draw();
            };
            cropImage.onerror = () => {
                cardError.textContent = PHOTO_TYPE_MESSAGE;
                close();
            };
            dialog.showModal();
            try {
                cropImage.src = await readAsDataUrl(file);
            } catch (error) {
                cardError.textContent = error.message;
                close();
            }
        });

        zoom.addEventListener('input', () => {
            // Zoom around the centre of the square.
            const centre = frameSize() / 2;
            const imageX = (centre - view.x) / view.scale;
            const imageY = (centre - view.y) / view.scale;
            view.scale = view.baseScale * Number(zoom.value);
            view.x = centre - imageX * view.scale;
            view.y = centre - imageY * view.scale;
            draw();
        });

        let dragStart = null;
        frame.addEventListener('pointerdown', (event) => {
            dragStart = { pointerX: event.clientX, pointerY: event.clientY, x: view.x, y: view.y };
            frame.setPointerCapture(event.pointerId);
        });
        frame.addEventListener('pointermove', (event) => {
            if (!dragStart) return;
            view.x = dragStart.x + event.clientX - dragStart.pointerX;
            view.y = dragStart.y + event.clientY - dragStart.pointerY;
            draw();
        });
        ['pointerup', 'pointercancel'].forEach((type) => frame.addEventListener(type, () => { dragStart = null; }));

        dialog.querySelector('[data-crop-cancel]').addEventListener('click', close);
        dialog.addEventListener('cancel', (event) => {
            event.preventDefault();
            close();
        });

        saveButton.addEventListener('click', async () => {
            // The visible square as fractions of the upright image (the server crops the same box).
            const side = frameSize() / view.scale;
            idPhotoForm.querySelector('[data-crop-field="x"]').value = (-view.x / view.scale / view.width).toFixed(5);
            idPhotoForm.querySelector('[data-crop-field="y"]').value = (-view.y / view.scale / view.height).toFixed(5);
            idPhotoForm.querySelector('[data-crop-field="size"]').value = Math.min(1, side / view.width).toFixed(5);

            saveButton.disabled = true;
            saveButton.setAttribute('aria-busy', 'true');
            cropError.textContent = '';
            try {
                const response = await send(idPhotoForm.action, new FormData(idPhotoForm));
                const payload = await readJson(response);
                if (!response.ok) throw new Error(firstError(payload) || 'Unable to save the 2x2 picture.');
                const image = document.createElement('img');
                image.src = payload.url;
                image.alt = 'Your 2x2 picture';
                preview.replaceChildren(image);
                pickButton.textContent = 'Replace';
                if (removeForm) removeForm.hidden = false;
                close();
                showToast(payload.message || '2x2 picture saved.');
            } catch (error) {
                cropError.textContent = error.message;
            } finally {
                saveButton.disabled = false;
                saveButton.removeAttribute('aria-busy');
            }
        });
    }

    /* ---------- Skills (tag input) ---------- */
    const skillForm = page.querySelector('form[data-skill-form]');
    if (skillForm) {
        const input = skillForm.elements.name;
        const addButton = skillForm.querySelector('[data-skill-add]');
        const errorBox = document.getElementById('skillError');
        const suggestionList = document.getElementById('skillSuggestions');
        const chipList = page.querySelector('[data-skill-list]');
        const emptyState = page.querySelector('[data-skill-empty]');
        const counter = skillForm.querySelector('[data-skill-count]');
        const limitNote = skillForm.querySelector('[data-skill-limit]');
        const template = document.getElementById('skillChipTemplate');
        const max = Number(skillForm.dataset.max);
        let suggestions = [];
        let activeIndex = -1;
        let suggestTimer;
        let suggestRequest = 0;

        const setSkillError = (message) => {
            errorBox.textContent = message || '';
            input.classList.toggle('is-invalid', Boolean(message));
            if (message) markTabError('skills');
        };
        const updateCount = (count) => {
            counter.textContent = count;
            const atLimit = count >= max;
            input.disabled = atLimit;
            addButton.disabled = atLimit;
            limitNote.hidden = !atLimit;
            emptyState.hidden = count > 0;
        };
        const closeSuggestions = () => {
            suggestions = [];
            activeIndex = -1;
            suggestionList.hidden = true;
            suggestionList.replaceChildren();
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
        };
        const highlight = (index) => {
            activeIndex = index;
            Array.from(suggestionList.children).forEach((option, optionIndex) => {
                option.setAttribute('aria-selected', String(optionIndex === index));
            });
            if (index >= 0) input.setAttribute('aria-activedescendant', `skillSuggestion-${index}`);
        };
        const renderSuggestions = (names) => {
            suggestions = names;
            activeIndex = -1;
            suggestionList.replaceChildren(...names.map((name, index) => {
                const option = document.createElement('li');
                option.id = `skillSuggestion-${index}`;
                option.setAttribute('role', 'option');
                option.setAttribute('aria-selected', 'false');
                option.textContent = name;
                option.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                    input.value = name;
                    closeSuggestions();
                    skillForm.requestSubmit();
                });
                return option;
            }));
            suggestionList.hidden = names.length === 0;
            input.setAttribute('aria-expanded', String(names.length > 0));
        };

        input.addEventListener('input', () => {
            setSkillError('');
            window.clearTimeout(suggestTimer);
            const term = input.value.trim();
            if (!term) {
                closeSuggestions();
                return;
            }
            suggestTimer = window.setTimeout(async () => {
                const requestId = ++suggestRequest;
                try {
                    const response = await fetch(`${skillForm.dataset.suggestUrl}?q=${encodeURIComponent(term)}`, { headers: JSON_HEADERS });
                    const payload = await response.json();
                    if (requestId === suggestRequest && input.value.trim() === term) renderSuggestions(payload.suggestions || []);
                } catch {
                    closeSuggestions();
                }
            }, 150);
        });
        input.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown' && suggestions.length) {
                event.preventDefault();
                highlight((activeIndex + 1) % suggestions.length);
            } else if (event.key === 'ArrowUp' && suggestions.length) {
                event.preventDefault();
                highlight((activeIndex - 1 + suggestions.length) % suggestions.length);
            } else if (event.key === 'Enter' && activeIndex >= 0) {
                input.value = suggestions[activeIndex];
                closeSuggestions();
            } else if (event.key === 'Escape') {
                closeSuggestions();
            }
        });
        input.addEventListener('blur', () => window.setTimeout(closeSuggestions, 100));

        const buildChip = (chip) => {
            const node = template.content.firstElementChild.cloneNode(true);
            node.dataset.skillId = chip.id;
            node.dataset.level = chip.level || '';
            node.querySelectorAll('form').forEach((form) => { form.action = form.action.replace('__ID__', chip.id); });
            node.querySelector('[data-skill-name]').textContent = chip.name;
            node.querySelector('[data-skill-remove-button]').setAttribute('aria-label', `Remove ${chip.name}`);
            node.querySelector('[data-skill-certificate]').hidden = !chip.has_certificate;
            setChipLevel(node, chip);
            return node;
        };
        const setChipLevel = (node, chip) => {
            node.dataset.level = chip.level || '';
            const label = node.querySelector('[data-skill-level-label]');
            label.textContent = chip.level_label || '';
            label.hidden = !chip.level;
            node.querySelectorAll('input[name="level"]').forEach((radio) => { radio.checked = radio.value === (chip.level || ''); });
        };
        const closeLevelMenus = (except) => {
            chipList.querySelectorAll('[data-skill-level-form]').forEach((menu) => {
                if (menu === except) return;
                menu.hidden = true;
                menu.closest('.skill-chip').querySelector('[data-skill-level-toggle]').setAttribute('aria-expanded', 'false');
            });
        };

        skillForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            closeSuggestions();
            const name = input.value.trim();
            if (!name) {
                setSkillError('Type a skill to add.');
                input.focus();
                return;
            }
            const duplicate = Array.from(chipList.querySelectorAll('[data-skill-name]'))
                .some((node) => node.textContent.trim().toLocaleLowerCase() === name.replace(/\s+/g, ' ').toLocaleLowerCase());
            if (duplicate) {
                setSkillError(`"${name}" is already in your skills.`);
                return;
            }
            addButton.disabled = true;
            try {
                const response = await send(skillForm.action, new FormData(skillForm));
                const payload = await readJson(response);
                if (!response.ok) {
                    if (!handleConsentError(payload)) setSkillError(firstError(payload) || 'Unable to add this skill.');
                    return;
                }
                chipList.append(buildChip(payload.skill));
                input.value = '';
                setSkillError('');
                updateCount(payload.count);
                updateCompletion(payload);
                consentCard?.remove();
                showToast(payload.message);
            } catch {
                setSkillError('Unable to add this skill. Please try again.');
            } finally {
                updateCount(chipList.children.length);
                if (!input.disabled) input.focus();
            }
        });

        chipList.addEventListener('click', (event) => {
            const toggle = event.target.closest('[data-skill-level-toggle]');
            if (!toggle) return;
            const menu = toggle.closest('.skill-chip').querySelector('[data-skill-level-form]');
            const opening = menu.hidden;
            closeLevelMenus(menu);
            menu.hidden = !opening;
            toggle.setAttribute('aria-expanded', String(opening));
            if (opening) menu.querySelector('input:checked, input')?.focus();
        });
        chipList.addEventListener('change', async (event) => {
            const menu = event.target.closest('[data-skill-level-form]');
            if (!menu || event.target.name !== 'level') return;
            const chipNode = menu.closest('.skill-chip');
            const response = await send(menu.action, new FormData(menu));
            const payload = await readJson(response);
            if (!response.ok) {
                if (!handleConsentError(payload)) showToast(firstError(payload) || 'Unable to update the level.');
                return;
            }
            setChipLevel(chipNode, payload.skill);
            closeLevelMenus();
            chipNode.querySelector('[data-skill-level-toggle]').focus();
            showToast(payload.message);
        });
        chipList.addEventListener('submit', async (event) => {
            const form = event.target;
            event.preventDefault();
            if (form.matches('[data-skill-level-form]')) return;
            if (!form.matches('[data-skill-remove]')) return;
            const chipNode = form.closest('.skill-chip');
            const response = await send(form.action, new FormData(form));
            const payload = await readJson(response);
            if (!response.ok) {
                showToast(firstError(payload) || 'Unable to remove this skill.');
                return;
            }
            chipNode.remove();
            updateCount(payload.count);
            updateCompletion(payload);
            setSkillError('');
            showToast(payload.message);
            input.focus();
        });
        document.addEventListener('click', (event) => {
            if (!event.target.closest('.skill-chip')) closeLevelMenus();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeLevelMenus();
        });
    }

    /* ---------- Experience / references / projects ---------- */
    const collectionForms = new Map(Array.from(page.querySelectorAll('form[data-profile-collection]'))
        .map((form) => [form.dataset.profileCollection, form]));
    const resetCollectionForm = (form) => {
        form.reset();
        clearErrors(form);
        if (form.dataset.storeUrl) form.action = form.dataset.storeUrl;
        const method = form.querySelector('[data-method-field]');
        if (method) method.value = 'POST';
        const title = form.querySelector('[data-form-title]');
        if (title) title.textContent = title.dataset.addTitle;
        if (form.dataset.max) {
            const listed = page.querySelectorAll(`[data-collection-list="${form.dataset.profileCollection}"] .collection-item`).length;
            form.hidden = listed >= Number(form.dataset.max);
        }
        // Forms opened by a button (Add award) close again after a save or cancel.
        if (form.hasAttribute('data-hide-when-idle')) form.hidden = true;
        syncCurrentJob(form);
    };
    const syncCurrentJob = (form) => {
        const current = form.querySelector('[data-is-current]');
        const endDate = form.querySelector('[data-end-date]');
        if (!current || !endDate) return;
        endDate.disabled = current.checked;
        if (current.checked) endDate.value = '';
    };
    const applyCollectionPayload = (key, payload) => {
        if (payload.html !== undefined) page.querySelector(`[data-collection-list="${key}"]`).innerHTML = payload.html;
        updateCompletion(payload);
        if (key === 'references' && typeof payload.count === 'number') {
            const atLimit = payload.count >= payload.max;
            collectionForms.get('references').hidden = atLimit;
            page.querySelector('[data-reference-limit]').hidden = !atLimit;
            page.querySelector('[data-reference-prompt]').hidden = payload.count > 0;
        }
    };

    collectionForms.forEach((form, key) => {
        form.querySelector('[data-is-current]')?.addEventListener('change', () => syncCurrentJob(form));
        syncCurrentJob(form);
        form.querySelector('[data-collection-cancel]')?.addEventListener('click', () => resetCollectionForm(form));
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const saveButton = form.querySelector('button[type="submit"]');
            saveButton.disabled = true;
            saveButton.setAttribute('aria-busy', 'true');
            clearErrors(form);
            try {
                const response = await send(form.action, new FormData(form));
                const payload = await readJson(response);
                if (response.status === 422 && payload.errors) {
                    if (!handleConsentError(payload)) showErrors(form, payload.errors);
                    return;
                }
                if (!response.ok) throw new Error(payload.message || 'Unable to save.');
                applyCollectionPayload(key, payload);
                resetCollectionForm(form);
                consentCard?.remove();
                showToast(payload.message || 'Saved.');
            } catch (error) {
                showToast(error.message || 'Unable to save.');
            } finally {
                saveButton.disabled = false;
                saveButton.removeAttribute('aria-busy');
            }
        });
    });

    page.addEventListener('click', (event) => {
        const button = event.target.closest('[data-collection-open]');
        if (!button) return;
        const form = collectionForms.get(button.dataset.collectionOpen);
        resetCollectionForm(form);
        form.hidden = false;
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        form.querySelector('select, input:not([type="hidden"])')?.focus({ preventScroll: true });
    });

    page.addEventListener('click', (event) => {
        const button = event.target.closest('[data-collection-edit]');
        if (!button) return;
        const form = collectionForms.get(button.dataset.collectionEdit);
        resetCollectionForm(form);
        form.hidden = false;
        form.action = button.dataset.action;
        form.querySelector('[data-method-field]').value = 'PUT';
        const title = form.querySelector('[data-form-title]');
        title.textContent = title.dataset.editTitle;
        Object.entries(JSON.parse(button.dataset.values || '{}')).forEach(([name, value]) => {
            const fields = Array.from(form.elements).filter((element) => element.name === name && element.type !== 'hidden');
            fields.forEach((field) => {
                if (field.type === 'checkbox') field.checked = Boolean(value);
                else field.value = value ?? '';
            });
        });
        syncCurrentJob(form);
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        form.querySelector('input:not([type="hidden"]), textarea')?.focus({ preventScroll: true });
    });

    // Runs after the shared confirm dialog has approved the delete.
    page.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches('[data-collection-delete]')) return;
        event.preventDefault();
        const key = form.dataset.collectionDelete;
        const response = await send(form.action, new FormData(form));
        const payload = await readJson(response);
        if (!response.ok) {
            showToast(firstError(payload) || 'Unable to remove this item.');
            return;
        }
        applyCollectionPayload(key, payload);
        resetCollectionForm(collectionForms.get(key));
        showToast(payload.message || 'Removed.');
    });

    /* ---------- File size guard for 5 MB document uploads ---------- */
    page.addEventListener('change', (event) => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement) || input.type !== 'file' || !input.dataset.maxBytes) return;
        const file = input.files[0];
        if (file && file.size > Number(input.dataset.maxBytes)) {
            input.value = '';
            showToast('The file must be 5 MB or smaller.');
        }
    });

    /* ---------- File preview ---------- */
    const previewModal = document.getElementById('portfolioPreviewModal');
    const previewPane = document.getElementById('portfolioPreviewPane');
    const showPreviewMessage = (message) => {
        const notice = document.createElement('p');
        notice.className = 'portfolio-description';
        notice.textContent = message;
        previewPane.replaceChildren(notice);
    };
    page.addEventListener('click', (event) => {
        const button = event.target.closest('[data-file-preview]');
        if (!button) return;
        const data = button.dataset;
        previewPane.replaceChildren();
        document.getElementById('portfolioPreviewTitle').textContent = data.fileName;
        const meta = {
            previewCategory: data.fileCategory,
            previewIssuer: data.fileIssuer,
            previewUploaded: data.fileUploaded,
            previewStatus: data.fileStatus,
            previewReviewer: data.fileReviewer,
            previewReviewedAt: data.fileReviewedAt,
            previewSize: data.fileSize,
        };
        Object.entries(meta).forEach(([id, value]) => {
            const dd = document.getElementById(id);
            dd.textContent = value || '';
            dd.hidden = !value;
            dd.previousElementSibling.hidden = !value;
        });
        document.getElementById('previewDownload').href = data.downloadUrl;
        previewModal.showModal();

        if (data.fileExtension === 'pdf' || ['jpg', 'jpeg', 'png'].includes(data.fileExtension)) {
            const viewer = document.createElement(data.fileExtension === 'pdf' ? 'iframe' : 'img');
            viewer.className = data.fileExtension === 'pdf' ? 'preview-frame' : 'preview-image preview-frame';
            if (viewer instanceof HTMLIFrameElement) viewer.title = `Preview of ${data.fileName}`;
            else viewer.alt = data.fileName;
            viewer.addEventListener('error', () => showPreviewMessage('Preview not available. The file could not be loaded.'), { once: true });
            viewer.src = data.previewUrl;
            previewPane.append(viewer);
        } else {
            showPreviewMessage(`Preview not available for this file type (${data.fileExtension ? `.${data.fileExtension}` : 'unknown'}). Use Download instead.`);
        }
    });
    document.getElementById('closePortfolioPreview').addEventListener('click', () => previewModal.close());
    previewModal.addEventListener('close', () => previewPane.replaceChildren());
    previewModal.addEventListener('click', (event) => {
        if (event.target === previewModal) previewModal.close();
    });

    /* ---------- Server-rendered errors and flash messages ---------- */
    const errorTab = page.dataset.errorTab;
    if (errorTab) {
        const tabErrorFields = JSON.parse(page.dataset.tabErrorFields || '{}');
        JSON.parse(page.dataset.errorFields || '[]').forEach((field) => {
            const name = Object.keys(tabErrorFields).find((key) => tabErrorFields[key].includes(field));
            if (name) markTabError(name);
        });
        markTabError(errorTab);
        activateTab(errorTab);
    }
    if (page.dataset.flashSuccess) showToast(page.dataset.flashSuccess);
})();
