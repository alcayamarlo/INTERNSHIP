(() => {
    'use strict';

    const page = document.getElementById('coordinatorStudentsPage');
    const dataElement = document.getElementById('studentsInitialData');
    if (!page || !dataElement) return;

    const initial = JSON.parse(dataElement.textContent);
    const rows = document.getElementById('studentResults');
    const empty = document.getElementById('studentsEmpty');
    const pagination = document.getElementById('studentsPagination');
    const searchInput = document.getElementById('studentSearch');
    const programSelect = document.getElementById('studentProgram');
    const sortSelect = document.getElementById('studentSort');
    let currentPage = initial.pagination.current_page;
    let lastPage = initial.pagination.last_page;
    let requestController = null;
    let debounceTimer = null;

    const node = (tag, className, text) => {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (text !== undefined && text !== null) element.textContent = String(text);
        return element;
    };

    const initials = (name) => String(name ?? '')
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => Array.from(part)[0] ?? '')
        .join('')
        .toLocaleUpperCase();

    const competencySummary = (student) => {
        if (!student.total_submissions_count) return 'None yet';
        return `${student.verified_submissions_count} of ${student.total_submissions_count} verified`;
    };

    const renderStudent = (student) => {
        const row = node('article', 'student-tracking-row');
        const identity = node('div', 'student-identity');
        identity.append(node('span', 'student-avatar', initials(student.name)));

        const identityCopy = node('div', 'student-identity-copy');
        const studentId = node('span', 'student-school-id', student.student_id_number || 'School ID not provided');
        if (student.student_id_needs_correction) {
            const flag = node('span', 'student-id-correction-flag', 'ID needs correction');
            flag.setAttribute('aria-label', 'ID needs correction');
            studentId.append(document.createTextNode(' '), flag);
        }
        identityCopy.append(
            node('strong', 'student-full-name', student.name),
            studentId,
            node('span', 'student-email', student.email),
        );
        identity.append(identityCopy);

        const program = node('div', 'student-column');
        program.append(
            node('strong', '', student.program || 'Program not set'),
            node('span', 'student-secondary', student.year_level || 'Year level not set'),
        );

        const competencies = node('div', 'student-column');
        competencies.append(
            node('strong', 'student-competency-total', `${student.competencies_count} competencies`),
            node('span', 'student-secondary', competencySummary(student)),
        );

        const completionValue = Math.max(0, Math.min(100, Number(student.profile_completion) || 0));
        const completion = node('div', `student-completion${completionValue < 50 ? ' is-low' : ''}`);
        const track = node('div', 'student-completion-track');
        const fill = node('span');
        fill.style.width = `${completionValue}%`;
        track.append(fill);
        completion.append(track, node('span', 'student-completion-value', `${completionValue}%`));

        const view = node('a', 'student-view-button', 'View');
        view.href = student.view_url;
        view.setAttribute('aria-label', `View ${student.name}`);

        row.append(identity, program, competencies, completion, view);
        return row;
    };

    const makePageButton = (label, pageNumber, options = {}) => {
        const button = node('button', 'students-page-button', label);
        button.type = 'button';
        button.disabled = options.disabled ?? false;
        if (options.current) button.setAttribute('aria-current', 'page');
        button.addEventListener('click', () => loadPage(pageNumber));
        return button;
    };

    const renderPagination = (meta) => {
        pagination.replaceChildren();
        if (meta.last_page <= 1) return;

        pagination.append(makePageButton('Previous', Math.max(1, meta.current_page - 1), {
            disabled: meta.current_page <= 1,
        }));

        const start = Math.max(1, meta.current_page - 2);
        const end = Math.min(meta.last_page, meta.current_page + 2);
        for (let pageNumber = start; pageNumber <= end; pageNumber += 1) {
            pagination.append(makePageButton(String(pageNumber), pageNumber, {
                current: pageNumber === meta.current_page,
            }));
        }

        pagination.append(makePageButton('Next', Math.min(meta.last_page, meta.current_page + 1), {
            disabled: meta.current_page >= meta.last_page,
        }));
    };

    const render = (students, meta, summary) => {
        rows.replaceChildren(...students.map(renderStudent));
        rows.setAttribute('aria-busy', 'false');
        empty.hidden = students.length > 0;
        currentPage = meta.current_page;
        lastPage = meta.last_page;
        renderPagination(meta);
        document.getElementById('studentsTotal').textContent = Number(summary.total).toLocaleString();
        document.getElementById('studentsAverage').textContent = `${summary.average_completion}%`;
        document.getElementById('studentsBelow').textContent = Number(summary.below_50).toLocaleString();
    };

    const loadPage = async (pageNumber = 1) => {
        requestController?.abort();
        requestController = new AbortController();
        rows.setAttribute('aria-busy', 'true');

        const params = new URLSearchParams({
            search: searchInput.value.trim(),
            program: programSelect.value,
            sort: sortSelect.value,
            page: String(pageNumber),
        });

        try {
            const response = await fetch(`${window.location.pathname}?${params.toString()}`, {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: requestController.signal,
            });
            const result = await response.json().catch(() => ({ message: response.status === 413 ? 'The file is too large to upload.' : `Request failed (HTTP ${response.status}). Please try again.` }));
            if (!response.ok) {
                throw new Error(result.message || 'Unable to load students.');
            }
            render(result.students, result.pagination, result.summary);
        } catch (error) {
            if (error.name === 'AbortError') return;
            rows.setAttribute('aria-busy', 'false');
            rows.replaceChildren();
            pagination.replaceChildren();
            empty.hidden = false;
            empty.querySelector('strong').textContent = error.message;
        }
    };

    searchInput.addEventListener('input', () => {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(() => loadPage(1), 220);
    });
    programSelect.addEventListener('change', () => loadPage(1));
    sortSelect.addEventListener('change', () => loadPage(1));

    render(initial.students, initial.pagination, initial.summary);
})();
