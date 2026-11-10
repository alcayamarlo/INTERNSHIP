@extends('layouts.app')

@section('title', 'Manage Internships')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/sb-split-inbox.css') }}?v={{ @filemtime(public_path('css/sb-split-inbox.css')) }}">
<link rel="stylesheet" href="{{ asset('css/employer-postings.css') }}?v={{ @filemtime(public_path('css/employer-postings.css')) }}">
@endpush

@section('content')
@php
    // Only verified companies can post (company.verified); others keep the plain link,
    // which explains why on the create page.
    $canPost = auth()->user()->employer?->isVerified();
    // Without JavaScript the links open the create page; with it, Bootstrap opens the pop-up instead.
    $postTrigger = $canPost ? 'data-bs-toggle="modal" data-bs-target="#newInternshipModal"' : '';

    $tabs = ['open' => 'Open', 'closed' => 'Closed'];
    // ?posting= and ?tab= keep the place after Close or Delete (they return to this page).
    $selected = $internships->first(fn ($internship) => (string) $internship->id === (string) request('posting'));
    $activeTab = $selected?->status
        ?? (array_key_exists((string) request('tab'), $tabs) ? request('tab')
            : ($internships->contains('status', 'closed') && ! $internships->contains('status', 'open') ? 'closed' : 'open'));
    $selected ??= $internships->first(fn ($internship) => $internship->status === $activeTab);
    // Green "Open", gray "Closed" (the shared badge would show Closed in red).
    $statusTone = fn ($internship) => $internship->status === 'open' ? 'success' : 'neutral';
@endphp

<x-page-header title="Internship Postings" subtitle="Manage your internship listings">
    <a href="{{ route('employer.internships.create') }}" class="btn btn-primary" {!! $postTrigger !!}>
        <i class="bi bi-plus-lg" aria-hidden="true"></i> New Posting
    </a>
</x-page-header>

<div class="pp-page sbx" id="postingsPage" data-active-tab="{{ $activeTab }}" data-selected-id="{{ $selected?->id }}">
    <div data-postings-flash></div>

    <div data-postings-main>
        @if($internships->total() > 0)
            <div class="sbx-split sb-layout-2">
                <section class="card sbx-list-card" aria-label="Your postings">
                    <div class="sbx-tabs" role="group" aria-label="Posting status">
                        @foreach($tabs as $key => $label)
                            <button type="button" class="sb-section-tab sbx-tab {{ $key === $activeTab ? 'active' : '' }}"
                                    data-posting-tab="{{ $key }}" data-label="{{ $label }}" aria-pressed="{{ $key === $activeTab ? 'true' : 'false' }}">
                                {{ $label }} <span class="sbx-tab-count">{{ $statusCounts[$key] ?? 0 }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="sbx-list-region" data-posting-list-region>
                        <ul class="sbx-list">
                            @foreach($internships as $internship)
                                <li @if($internship->status !== $activeTab) hidden @endif>
                                    <button type="button" class="sbx-row {{ $selected?->is($internship) ? 'is-selected' : '' }}"
                                            data-internship-id="{{ $internship->id }}" data-tab="{{ $internship->status }}"
                                            aria-pressed="{{ $selected?->is($internship) ? 'true' : 'false' }}" aria-controls="postingDetailPanel">
                                        <span class="sbx-row-text">
                                            <span class="sbx-row-title">{{ $internship->title }}</span>
                                            <span class="sbx-row-meta">{{ $internship->location ?: 'Location not set' }} · {{ $internship->work_setup->label() }}</span>
                                        </span>
                                        <x-status-badge :status="$internship->status" :label="ucfirst($internship->status)" :tone="$statusTone($internship)" />
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                        <div class="sbx-empty" data-posting-empty @if($internships->contains('status', $activeTab)) hidden @endif>
                            <p data-posting-empty-text>No {{ strtolower($tabs[$activeTab]) }} postings.</p>
                        </div>
                    </div>

                    @if($internships->hasPages())
                        <div class="sbx-pages">{{ $internships->links() }}</div>
                    @endif
                </section>

                <section class="card sbx-detail" id="postingDetailPanel" aria-label="Posting details">
                    <div class="sbx-detail-body" data-posting-detail-body>
                        @if($selected)
                            @include('employer.internships.partials.detail', ['internship' => $selected])
                        @else
                            <p class="sbx-placeholder">Select a posting to see its details.</p>
                        @endif
                    </div>
                </section>
            </div>

            {{-- Detail for each row, cloned into the panel on select (same pattern as Browse internships). --}}
            <div hidden>
                @foreach($internships as $internship)
                    <template id="postingDetail-{{ $internship->id }}">@include('employer.internships.partials.detail', ['internship' => $internship])</template>
                @endforeach
                <template id="postingDetailPlaceholder"><p class="sbx-placeholder">Select a posting to see its details.</p></template>
            </div>
        @else
            <div class="card">
                <x-empty-state icon="bi-briefcase" title="No internship postings yet" message="Create your first posting to start receiving applications from students.">
                    <a href="{{ route('employer.internships.create') }}" class="btn btn-primary" {!! $postTrigger !!}>Post an internship</a>
                </x-empty-state>
            </div>
        @endif
    </div>
</div>

@if($canPost)
    {{-- Same form-modal setup as the OJT log (sb-modals.js): Bootstrap supplies the dialog role, focus trap,
         Escape and backdrop close, scroll lock, focus return and the fade; sb-modals.js adds the
         unsaved-changes prompt and the reset. If a plain (non-script) submit fails validation, it reopens. --}}
    <div class="modal fade" id="newInternshipModal" tabindex="-1" role="dialog" aria-modal="true"
         aria-labelledby="newInternshipModalLabel" aria-describedby="newInternshipModalHint" aria-hidden="true"
         style="--bs-modal-width: 760px" data-sb-form-modal @if(old('_modal') === 'newInternship') data-sb-open-on-load @endif>
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header align-items-start">
                    <div>
                        <h2 class="modal-title h5" id="newInternshipModalLabel">Post New Internship</h2>
                        <p class="text-muted small mb-0" id="newInternshipModalHint">Describe the role and the competencies you are looking for.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger" role="alert" data-form-errors hidden>Please fix the highlighted fields.</div>
                    <form method="POST" action="{{ route('employer.internships.store') }}" id="newInternshipForm" novalidate>
                        @csrf
                        <input type="hidden" name="_modal" value="newInternship">
                        @include('employer.internships.partials.fields', ['internship' => new \App\Models\Internship])
                    </form>
                </div>
                <div class="modal-footer">
                    <span class="text-muted small me-auto"><span class="text-danger" aria-hidden="true">*</span> Required</span>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" form="newInternshipForm">Post Internship</button>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
// List + detail: tabs, row selection, keyboard. window.sbPostings lets the pop-up re-sync after posting.
(() => {
    const page = document.getElementById('postingsPage');
    if (!page) return;

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const stacked = window.matchMedia('(max-width: 900px)');
    let tab = page.dataset.activeTab || 'open';
    let selectedId = page.dataset.selectedId || null;

    const rows = () => [...page.querySelectorAll('.sbx-row')];
    const visibleRows = () => rows().filter((row) => !row.closest('li').hidden);
    const panel = () => document.getElementById('postingDetailPanel');

    // Same as Browse internships: re-add the class so the "rise" keyframe plays on every change.
    const replay = (element) => {
        if (reduceMotion || !element) return;
        element.classList.remove('is-changing');
        void element.offsetWidth;
        element.classList.add('is-changing');
    };

    // The address keeps the tab and posting, so Close and Delete (which return here) keep the place.
    const remember = () => {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        if (selectedId) {
            url.searchParams.set('posting', selectedId);
        } else {
            url.searchParams.delete('posting');
        }
        window.history.replaceState(window.history.state, '', url);
    };

    const select = (id, { animate = true } = {}) => {
        selectedId = id ? String(id) : null;
        rows().forEach((row) => {
            const on = row.dataset.internshipId === selectedId;
            row.classList.toggle('is-selected', on);
            row.setAttribute('aria-pressed', String(on));
        });
        const body = page.querySelector('[data-posting-detail-body]');
        if (!body) return;
        const template = (selectedId && document.getElementById(`postingDetail-${selectedId}`))
            || document.getElementById('postingDetailPlaceholder');
        body.replaceChildren(template.content.cloneNode(true));
        if (animate) replay(panel());
        remember();
    };

    const showTab = (next, { animate = true, keepSelection = false } = {}) => {
        tab = next;
        page.querySelectorAll('[data-posting-tab]').forEach((button) => {
            const on = button.dataset.postingTab === tab;
            button.classList.toggle('active', on);
            button.setAttribute('aria-pressed', String(on));
        });
        rows().forEach((row) => { row.closest('li').hidden = row.dataset.tab !== tab; });

        const visible = visibleRows();
        const empty = page.querySelector('[data-posting-empty]');
        if (empty) {
            const label = page.querySelector(`[data-posting-tab="${tab}"]`)?.dataset.label || tab;
            empty.hidden = visible.length > 0;
            empty.querySelector('[data-posting-empty-text]').textContent = `No ${label.toLowerCase()} postings.`;
        }
        if (animate) replay(page.querySelector('[data-posting-list-region]'));

        if (!keepSelection || !visible.some((row) => row.dataset.internshipId === selectedId)) {
            select(visible[0]?.dataset.internshipId ?? null, { animate });
        }
    };

    page.addEventListener('click', (event) => {
        const tabButton = event.target.closest('[data-posting-tab]');
        if (tabButton) {
            if (tabButton.dataset.postingTab !== tab) showTab(tabButton.dataset.postingTab);
            return;
        }
        const row = event.target.closest('.sbx-row');
        if (!row) return;
        if (row.dataset.internshipId !== selectedId) select(row.dataset.internshipId);
        if (stacked.matches) panel()?.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
    });

    // Enter selects (rows are buttons); Arrow Up/Down, Home and End move through the list.
    page.addEventListener('keydown', (event) => {
        const row = event.target.closest('.sbx-row');
        if (!row || !['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
        const visible = visibleRows();
        const index = visible.indexOf(row);
        const next = event.key === 'Home' ? 0
            : event.key === 'End' ? visible.length - 1
                : index + (event.key === 'ArrowDown' ? 1 : -1);
        if (!visible[next]) return;
        event.preventDefault();
        visible[next].focus();
        select(visible[next].dataset.internshipId);
    });

    window.sbPostings = {
        // After the list was replaced: select this posting (or the tab's first) in the fresh markup.
        resync(id) {
            const row = rows().find((candidate) => candidate.dataset.internshipId === String(id));
            selectedId = row ? String(id) : null;
            showTab(row ? row.dataset.tab : tab, { animate: false, keepSelection: true });
            if (row) select(id, { animate: false });
        },
    };
})();
</script>

@if($canPost)
<script src="{{ asset('js/sb-modals.js') }}?v={{ @filemtime(public_path('js/sb-modals.js')) }}"></script>
<script>
// "Post New Internship" pop-up: sends without a reload, shows errors under the fields,
// and on success shows the fresh list with the new posting selected.
(() => {
    const modal = document.getElementById('newInternshipModal');
    const form = document.getElementById('newInternshipForm');
    if (!modal || !form) return;

    const errorSummary = modal.querySelector('[data-form-errors]');
    const requirements = form.querySelector('[data-requirements]');
    const initialRequirements = requirements.innerHTML;

    const clearErrors = () => {
        errorSummary.hidden = true;
        form.querySelectorAll('[data-js-error]').forEach((message) => message.remove());
        form.querySelectorAll('.is-invalid').forEach((field) => {
            field.classList.remove('is-invalid');
            field.removeAttribute('aria-invalid');
        });
    };

    // "program_ids.0" -> the program checkboxes; "requirement_names.1" -> the second requirement row.
    const fieldFor = (key) => {
        const [name, index] = key.split('.');
        const fields = [...form.querySelectorAll(`[name="${name}"], [name="${name}[]"]`)].filter((field) => field.type !== 'hidden');
        return (index !== undefined && fields[Number(index)] && fields[0].type !== 'checkbox') ? fields[Number(index)] : fields[0];
    };

    const showErrors = (errors) => {
        let first = null;
        Object.entries(errors).forEach(([key, messages]) => {
            const field = fieldFor(key);
            if (!field) return;
            const message = document.createElement('div');
            message.dataset.jsError = '';
            message.textContent = [].concat(messages)[0];
            if (field.type === 'checkbox') {
                // A group: one message under the group.
                const group = field.closest('fieldset') || field.parentElement;
                if (group.querySelector('[data-js-error]')) return;
                message.className = 'text-danger small mt-1';
                group.append(message);
            } else {
                field.classList.add('is-invalid');
                field.setAttribute('aria-invalid', 'true');
                message.className = 'invalid-feedback';
                field.insertAdjacentElement('afterend', message);
            }
            first ??= field;
        });
        errorSummary.hidden = false;
        first?.focus();
    };

    const showFlash = (message) => {
        // Same markup as the layout's flash messages, placed under the page header.
        const alert = document.createElement('div');
        alert.className = 'alert alert-success alert-dismissible fade show mb-4';
        alert.setAttribute('role', 'alert');
        const icon = document.createElement('i');
        icon.className = 'bi bi-check-circle-fill me-2';
        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'btn-close';
        close.dataset.bsDismiss = 'alert';
        close.setAttribute('aria-label', 'Close');
        alert.append(icon, document.createTextNode(message), close);
        document.querySelector('[data-postings-flash]').replaceChildren(alert);
    };

    // Swap in the fresh list and select the new posting; its row glows like other live changes (sb-motion.css).
    const showFresh = (doc) => {
        const main = document.querySelector('[data-postings-main]');
        const fresh = doc.querySelector('[data-postings-main]');
        if (!main || !fresh) return false;
        const known = new Set([...main.querySelectorAll('[data-internship-id]')].map((row) => row.dataset.internshipId));
        main.replaceWith(document.importNode(fresh, true));
        const added = [...document.querySelectorAll('[data-postings-main] [data-internship-id]')]
            .find((row) => !known.has(row.dataset.internshipId));
        window.sbPostings?.resync(added?.dataset.internshipId);
        if (added) {
            added.classList.add('sb-live-changed');
            setTimeout(() => added.classList.remove('sb-live-changed'), 2000);
        }
        return true;
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors();
        const submit = modal.querySelector('button[type="submit"][form="newInternshipForm"]');
        const original = submit.innerHTML;
        submit.innerHTML = '<span class="sb-submit-spinner" aria-hidden="true"></span>Posting…';
        let response = null;

        try {
            response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (response.status === 422) {
                showErrors((await response.json()).errors || {});
                form.dispatchEvent(new CustomEvent('sb:form-failed'));
                return;
            }
            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            // Saved: the server redirected to this list; show it without a reload.
            const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
            const message = doc.querySelector('.alert-success[role="alert"]')?.textContent.replace(/\s+/g, ' ').trim();
            form.dispatchEvent(new CustomEvent('sb:form-saved'));
            if (!showFresh(doc)) {
                window.location.reload();
                return;
            }
            showFlash(message || 'Internship posted successfully.');
        } catch (error) {
            errorSummary.textContent = response?.status === 419
                ? 'Your session expired. Reload the page and try again.'
                : 'The internship could not be posted. Please try again.';
            errorSummary.hidden = false;
            form.dispatchEvent(new CustomEvent('sb:form-failed'));
        } finally {
            submit.innerHTML = original;
        }
    });

    // Bootstrap returns focus to the button that opened the modal. The empty-state button is gone
    // once the first posting is in the list, so focus then goes to "New Posting".
    let opener = null;
    modal.addEventListener('show.bs.modal', (event) => { opener = event.relatedTarget || null; });

    // Closing (any way) leaves a clean form for next time: one empty requirement row, no errors.
    modal.addEventListener('hidden.bs.modal', () => {
        requirements.innerHTML = initialRequirements;
        clearErrors();
        errorSummary.textContent = 'Please fix the highlighted fields.';
        if (opener && !opener.isConnected) {
            document.querySelector('.sb-page-header [data-bs-target="#newInternshipModal"]')?.focus();
        }
    });
})();
</script>
@endif
@endpush
