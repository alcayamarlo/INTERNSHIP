@extends('layouts.app')

@section('title', 'Internships')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/student-internships.css') }}?v={{ @filemtime(public_path('css/student-internships.css')) }}">
@endpush

@php
    $selectedMode = collect((array) request('work_setup', []))->map(fn ($mode) => (string) $mode)->first();
    $hasFilters = collect(['search', 'location', 'company', 'skill', 'work_setup', 'scope'])->contains(fn ($field) => request()->filled($field));
    // "Show all programs": leaving it untouched keeps the default (browsing = your program, searching = every program).
    $scopeLocked = ! $programName;
    $explicitProgramScope = request()->filled('scope') && $scope === 'program';
    $first = $internships->first();
@endphp

@section('content')
<div class="ib-page">

<x-breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('student.dashboard')], ['label' => 'Internships']]" />

{{-- =========================================================
     HEADER
========================================================== --}}
<div class="sb-page-header ib-head">
    <div>
        <h1>Browse internships</h1>
        <p data-internship-scope="{{ $scope }}">
            @if($scope === 'program')
                Showing internships for your program ({{ $programName }}), best competency match first. Select a row to preview.
            @else
                Showing internships from all programs, best competency match first. Select a row to preview.
            @endif
        </p>
    </div>
    <div class="sb-page-actions">
        <a href="{{ route('student.applications.index') }}" class="btn btn-primary"><i class="bi bi-send" aria-hidden="true"></i> My Applications</a>
    </div>
</div>

{{-- =========================================================
     FILTERS (one row)
========================================================== --}}
<form method="GET" action="{{ route('student.internships.index') }}" class="ib-card ib-filterbar" role="search" aria-label="Filter internships">
    <div class="ib-filter-fields">
        <div class="ib-field">
            <label for="ibSearch">Title</label>
            <input id="ibSearch" type="text" class="form-control" name="search" placeholder="e.g. Web developer" value="{{ request('search') }}">
        </div>
        <div class="ib-field">
            <label for="ibLocation">Location</label>
            <input id="ibLocation" type="text" class="form-control" name="location" placeholder="e.g. Cebu City" value="{{ request('location') }}">
        </div>
        <div class="ib-field">
            <label for="ibCompany">Company</label>
            <input id="ibCompany" type="text" class="form-control" name="company" placeholder="Company name" value="{{ request('company') }}">
        </div>
        <div class="ib-field">
            <label for="ibSkill">Skill</label>
            <input id="ibSkill" type="text" class="form-control" name="skill" placeholder="e.g. Python" value="{{ request('skill') }}">
        </div>
        <div class="ib-field ib-field--mode">
            <label for="ibMode">Mode</label>
            <select id="ibMode" class="form-select" name="work_setup[]">
                <option value="">Any</option>
                @foreach(\App\Enums\WorkSetup::cases() as $mode)
                    <option value="{{ $mode->value }}" @selected($selectedMode === $mode->value)>{{ $mode->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="ib-filter-actions">
            {{-- Filters apply as you type or choose; this button stays for browsers without JavaScript --}}
            <button type="submit" class="btn btn-primary" data-filter-submit><i class="bi bi-search" aria-hidden="true"></i> Filter</button>
            <a href="{{ route('student.internships.index') }}" class="btn btn-outline-secondary" data-filter-clear>Clear</a>
        </div>
    </div>
    <div class="ib-filter-extra">
        {{-- Unticking sends scope=program; leaving it untouched keeps the controller's default. --}}
        <input type="hidden" name="scope" value="program" data-scope-program @disabled(! $explicitProgramScope)>
        <div class="form-check mb-0">
            <input class="form-check-input" type="checkbox" id="ibScopeAll" name="scope" value="all" data-scope-all @checked($scope === 'all') @disabled($scopeLocked)>
            <label class="form-check-label" for="ibScopeAll">Show all programs</label>
        </div>
        @if($scopeLocked)
            <span class="ib-hint">No program on your profile, so every program is shown.</span>
        @endif
    </div>
</form>

{{-- Announces the new result count to screen readers after a filter change --}}
<p class="visually-hidden" role="status" aria-live="polite" data-filter-status></p>

{{-- Results: replaced in place when a filter changes (no page reload) --}}
<div id="ibResults" class="ib-results-region" data-results data-total="{{ $internships->total() }}" aria-busy="false">
@if($internships->isEmpty())
    <div class="ib-card">
        <x-empty-state icon="bi-search" title="No internships found" message="Try different keywords or clear the filters to see everything that is open.">
            @if($hasFilters)
                <a href="{{ route('student.internships.index') }}" class="btn btn-outline-primary">Clear filters</a>
            @endif
        </x-empty-state>
    </div>
@else
{{-- =========================================================
     SPLIT VIEW: table (left) + preview (right)
========================================================== --}}
<div class="ib-split">

    <section class="ib-card ib-list" aria-labelledby="ibListTitle">
        <h2 id="ibListTitle" class="visually-hidden">Internships</h2>
        <div class="table-responsive">
            <table class="table align-middle ib-table">
                <thead>
                    <tr>
                        <th scope="col">Internship</th>
                        <th scope="col">Company</th>
                        <th scope="col">Location</th>
                        <th scope="col">Mode</th>
                        <th scope="col">Match</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($internships as $internship)
                        @php $applied = $appliedInternshipIds->contains($internship->id); @endphp
                        <tr class="ib-row {{ $loop->first ? 'is-selected' : '' }}" tabindex="0"
                            data-internship-id="{{ $internship->id }}"
                            aria-controls="ibPreview"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                            aria-label="{{ $internship->title }} at {{ $internship->employer->company_name }}, {{ $internship->match_percentage }}% match. Press Enter to preview.">
                            <td>
                                <span class="ib-row-title">{{ $internship->title }}</span>
                                @if($internship->requirementsList->count())
                                    <span class="ib-row-skills">{{ $internship->requirementsList->take(2)->pluck('requirement_name')->implode(' · ') }}</span>
                                @endif
                            </td>
                            <td>{{ $internship->employer->company_name }}</td>
                            <td>{{ $internship->location ?: '—' }}</td>
                            <td><span class="ib-mode">{{ $internship->work_setup->label() }}</span></td>
                            <td><span class="ib-match"><span class="ib-match-dot" aria-hidden="true"></span>{{ $internship->match_percentage }}% match</span></td>
                            <td data-status-cell>
                                @if($applied)
                                    <span class="ib-status ib-status--applied"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Applied</span>
                                @else
                                    <span class="ib-status">Not applied</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination: "Showing 1–10 of N internships" + page links --}}
        <div class="ib-list-footer">
            <p class="ib-showing">Showing {{ $internships->firstItem() }}–{{ $internships->lastItem() }} of {{ $internships->total() }} {{ \Illuminate\Support\Str::plural('internship', $internships->total()) }}</p>
            @if($internships->hasPages())
                <nav aria-label="Internship pages">
                    <ul class="pagination mb-0">
                        <li class="page-item {{ $internships->onFirstPage() ? 'disabled' : '' }}">
                            @if($internships->onFirstPage())
                                <span class="page-link" aria-label="Previous">&lsaquo;</span>
                            @else
                                <a class="page-link" href="{{ $internships->previousPageUrl() }}" rel="prev" aria-label="Previous">&lsaquo;</a>
                            @endif
                        </li>
                        @foreach($internships->getUrlRange(1, $internships->lastPage()) as $page => $url)
                            @if($page === $internships->currentPage())
                                <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                            @else
                                <li class="page-item"><a class="page-link" href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a></li>
                            @endif
                        @endforeach
                        <li class="page-item {{ $internships->hasMorePages() ? '' : 'disabled' }}">
                            @if($internships->hasMorePages())
                                <a class="page-link" href="{{ $internships->nextPageUrl() }}" rel="next" aria-label="Next">&rsaquo;</a>
                            @else
                                <span class="page-link" aria-label="Next">&rsaquo;</span>
                            @endif
                        </li>
                    </ul>
                </nav>
            @endif
        </div>
    </section>

    {{-- Preview panel: shows the selected row (first row by default) --}}
    <aside class="ib-card ib-preview" id="ibPreview" aria-label="Internship preview" aria-live="polite">
        @include('student.internships.partials.preview', ['internship' => $first, 'applied' => $appliedInternshipIds->contains($first->id)])
    </aside>
</div>

{{-- One preview per row; the script copies the selected one into the panel (no page reload) --}}
<div hidden>
    @foreach($internships as $internship)
        <template id="ibPreview-{{ $internship->id }}">
            @include('student.internships.partials.preview', ['internship' => $internship, 'applied' => $appliedInternshipIds->contains($internship->id)])
        </template>
    @endforeach
</div>

{{-- Phones: the preview opens as a dialog --}}
<div class="modal fade" id="ibPreviewModal" tabindex="-1" aria-label="Internship preview" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content ib-preview ib-preview--modal">
            <div class="modal-header">
                <h2 class="modal-title h6">Internship preview</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" data-preview-modal-body></div>
        </div>
    </div>
</div>
@endif
</div>{{-- /#ibResults --}}

{{-- =========================================================
     APPLY: pick a resume, then confirm (shared AJAX handler in resources/js/app.js)
========================================================== --}}
@if($resumes->isNotEmpty())
    <div class="modal fade" id="biApplyModal" tabindex="-1" aria-labelledby="ibApplyTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="" class="modal-content" data-ajax-application>
                @csrf
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title h5" id="ibApplyTitle">Apply to <span data-apply-title></span></h2>
                        <p class="ib-muted mb-0" data-apply-company></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="ibApplyResume">Resume to send <span class="text-danger" aria-hidden="true">*</span></label>
                    <select id="ibApplyResume" name="resume_id" class="form-select" required>
                        <option value="">Choose a resume</option>
                        @foreach($resumes as $resume)
                            <option value="{{ $resume->id }}">{{ $resume->category?->label() ?? 'Resume' }}{{ $resume->generated_at ? ' · '.$resume->generated_at->format('M Y') : '' }}</option>
                        @endforeach
                    </select>
                    <p class="ib-hint mt-2 mb-0">Need a different one? <a href="{{ route('student.resume.index') }}">Manage your resumes</a>.</p>
                    <div data-application-feedback class="small mt-2" role="status" aria-live="polite"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" data-apply-submit>Submit application</button>
                </div>
            </form>
        </div>
    </div>
@endif

</div>
@endsection

@push('scripts')
<script>
    (function () {
        const filterForm = document.querySelector('.ib-filterbar');
        const scopeAll = document.querySelector('[data-scope-all]');
        const scopeProgram = document.querySelector('[data-scope-program]');
        const phone = window.matchMedia('(max-width: 767.98px)');
        const stacked = window.matchMedia('(max-width: 1199.98px)');

        // "Show all programs": unticking sends scope=program; leaving it untouched keeps the default.
        scopeAll?.addEventListener('change', () => { if (scopeProgram) scopeProgram.disabled = scopeAll.checked; });

        // ---------- split view: select a row, show its preview ----------
        // Looked up on every use, because filtering replaces the results area.
        const rows = () => Array.from(document.querySelectorAll('.ib-row'));
        const previewFor = (id) => document.getElementById('ibPreview-' + id)?.content.cloneNode(true);

        function select(row, { fromPointer = false } = {}) {
            rows().forEach((other) => {
                const selected = other === row;
                other.classList.toggle('is-selected', selected);
                other.setAttribute('aria-selected', selected ? 'true' : 'false');
            });
            const content = previewFor(row.dataset.internshipId);
            if (!content) return;

            const previewModal = document.getElementById('ibPreviewModal');
            if (phone.matches && previewModal && window.bootstrap) {
                // Phones: open the preview as a dialog.
                previewModal.querySelector('[data-preview-modal-body]').replaceChildren(content);
                bootstrap.Modal.getOrCreateInstance(previewModal).show();
                return;
            }

            const panel = document.getElementById('ibPreview');
            if (!panel) return;
            panel.replaceChildren(content);
            // Same fade/slide as elsewhere (the "rise" keyframe); re-added so it plays on every change.
            panel.classList.remove('is-changing');
            void panel.offsetWidth;
            panel.classList.add('is-changing');
            if (stacked.matches && fromPointer) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        document.addEventListener('click', (event) => {
            const row = event.target.closest('.ib-row');
            if (row) select(row, { fromPointer: true });
        });
        document.addEventListener('keydown', (event) => {
            const row = event.target.closest?.('.ib-row');
            if (!row) return;
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                select(row);
            }
            // Arrow keys move between rows.
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                const all = rows();
                const next = all[all.indexOf(row) + (event.key === 'ArrowDown' ? 1 : -1)];
                if (next) { next.focus(); select(next); }
            }
        });

        // ---------- live filtering: results update as you type or choose, no Filter click ----------
        if (filterForm && window.fetch && window.DOMParser) {
            const status = document.querySelector('[data-filter-status]');
            filterForm.querySelector('[data-filter-submit]')?.setAttribute('hidden', '');
            let typingTimer = null;
            let inFlight = null;

            // Only filled fields go in the URL, so it stays short and shareable.
            const filterUrl = () => {
                const params = new URLSearchParams();
                for (const [name, value] of new FormData(filterForm)) {
                    if (String(value).trim() !== '') params.append(name, value);
                }
                const query = params.toString();
                return filterForm.action + (query ? '?' + query : '');
            };

            async function applyFilters() {
                clearTimeout(typingTimer);
                inFlight?.abort();
                inFlight = new AbortController();
                const url = filterUrl();
                const region = document.getElementById('ibResults');
                region?.setAttribute('aria-busy', 'true');
                region?.classList.add('is-loading');

                try {
                    const response = await fetch(url, { headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' }, signal: inFlight.signal });
                    if (!response.ok) throw new Error('HTTP ' + response.status);
                    const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const fresh = page.getElementById('ibResults');
                    if (!fresh) throw new Error('No results area');

                    document.getElementById('ibResults')?.replaceWith(fresh);
                    fresh.classList.add('is-changing');

                    // Keep the subtitle and "Show all programs" in step with what the server applied.
                    const freshScope = page.querySelector('[data-internship-scope]');
                    if (freshScope) document.querySelector('[data-internship-scope]')?.replaceWith(freshScope);
                    const freshAll = page.querySelector('[data-scope-all]');
                    if (freshAll && scopeAll) scopeAll.checked = freshAll.checked;

                    history.replaceState(null, '', url);
                    const total = Number(fresh.dataset.total || 0);
                    if (status) status.textContent = total === 1 ? '1 internship found.' : total + ' internships found.';
                } catch (error) {
                    if (error.name === 'AbortError') return;
                    filterForm.submit(); // fall back to a normal page load
                } finally {
                    const current = document.getElementById('ibResults');
                    current?.setAttribute('aria-busy', 'false');
                    current?.classList.remove('is-loading');
                }
            }

            // Text boxes: wait until typing pauses. Mode and "Show all programs": right away.
            filterForm.addEventListener('input', (event) => {
                if (!event.target.matches('input[type="text"]')) return;
                clearTimeout(typingTimer);
                typingTimer = setTimeout(applyFilters, 400);
            });
            filterForm.addEventListener('change', (event) => {
                if (event.target.matches('select, input[type="checkbox"]')) applyFilters();
            });
            filterForm.addEventListener('submit', (event) => {
                event.preventDefault();
                applyFilters();
            });

            // Clear: empty every field and show everything again.
            filterForm.querySelector('[data-filter-clear]')?.addEventListener('click', (event) => {
                event.preventDefault();
                filterForm.querySelectorAll('input[type="text"]').forEach((input) => { input.value = ''; });
                filterForm.querySelectorAll('select').forEach((field) => { field.value = ''; });
                if (scopeAll && !scopeAll.disabled) scopeAll.checked = false;
                if (scopeProgram) scopeProgram.disabled = true;
                applyFilters();
            });
        }

        // ---------- apply dialog ----------
        const modal = document.getElementById('biApplyModal');
        if (!modal) return;
        const form = modal.querySelector('form');
        const submit = form.querySelector('[data-apply-submit]');
        const feedback = form.querySelector('[data-application-feedback]');
        const resumeSelect = document.getElementById('ibApplyResume');
        let applyingId = null;

        // Opening the dialog: point the form at this internship and reset any earlier result.
        modal.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            if (!button) return;
            applyingId = button.dataset.applyId;
            form.action = button.dataset.applyUrl;
            modal.querySelector('[data-apply-title]').textContent = button.dataset.applyTitle;
            modal.querySelector('[data-apply-company]').textContent = button.dataset.applyCompany;
            submit.disabled = false;
            submit.textContent = 'Submit application';
            submit.classList.add('btn-primary');
            submit.classList.remove('btn-success');
            resumeSelect.disabled = false;
            feedback.className = 'small mt-2';
            feedback.textContent = '';
        });
        modal.addEventListener('shown.bs.modal', () => resumeSelect.focus());

        // Applied: update the row's status, the saved preview, and whichever panel shows it.
        const appliedLink = (url) => {
            const link = document.createElement('a');
            link.href = url;
            link.className = 'btn btn-outline-secondary ib-applied';
            link.setAttribute('aria-label', 'Already applied; view My Applications');
            link.innerHTML = '<i class="bi bi-check-circle-fill" aria-hidden="true"></i> Applied · View application';
            return link;
        };
        form.addEventListener('sb:application-submitted', (event) => {
            if (!applyingId) return;
            const url = event.detail?.applications_url || @json(route('student.applications.index'));
            const cell = document.querySelector('.ib-row[data-internship-id="' + applyingId + '"] [data-status-cell]');
            if (cell) cell.innerHTML = '<span class="ib-status ib-status--applied"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Applied</span>';
            const template = document.getElementById('ibPreview-' + applyingId);
            [template?.content, document.getElementById('ibPreview'), document.getElementById('ibPreviewModal')].forEach((scope) => {
                scope?.querySelectorAll('[data-apply-id="' + applyingId + '"]').forEach((button) => button.replaceWith(appliedLink(url)));
            });
        });
    })();
</script>
@endpush
