@extends('layouts.app')

@section('title', 'Invitations')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/student-internships.css') }}?v={{ @filemtime(public_path('css/student-internships.css')) }}">
<link rel="stylesheet" href="{{ asset('css/sb-split-inbox.css') }}?v={{ @filemtime(public_path('css/sb-split-inbox.css')) }}">
<link rel="stylesheet" href="{{ asset('css/student-invitations.css') }}?v={{ @filemtime(public_path('css/student-invitations.css')) }}">
@endpush

@section('content')
@use('App\Enums\InvitationStatus')
@php
    // Tab counts cover every invitation, not only this page of the list.
    $counts = auth()->user()->student->invitations()->toBase()
        ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
    // The Outcome step needs each application's status; the list query loads only its id.
    $invitations->getCollection()->load('application:id,status');

    // Pending past its date (or the posting closed) shows as Expired until the hourly job marks it.
    $displayStatus = fn ($invitation) => $invitation->isPending() && $invitation->hasLapsed()
        ? InvitationStatus::Expired : $invitation->status;
    // Status pills reuse the application page's colours (student-internships.css).
    $pillClass = ['pending' => 'reviewed', 'accepted' => 'accepted', 'declined' => 'withdrawn', 'expired' => 'rejected'];

    $tabs = ['pending' => 'Pending', 'accepted' => 'Accepted', 'declined' => 'Declined'];
    if (($counts['expired'] ?? 0) > 0) {
        $tabs['expired'] = 'Expired';
    }
    $selected = $invitations->first(fn ($invitation) => (string) $invitation->id === (string) request('invitation'));
    $activeTab = $selected?->status->value ?? (array_key_exists((string) request('tab'), $tabs) ? request('tab') : 'pending');
    $selected ??= $invitations->first(fn ($invitation) => $invitation->status->value === $activeTab);
    $detailData = ['displayStatus' => $displayStatus, 'pillClass' => $pillClass, 'hasResume' => $hasResume];
@endphp

<div class="inv-page sbx ib-page ap-page" id="invitationsPage"
    data-active-tab="{{ $activeTab }}"
    data-selected-id="{{ $selected?->id }}"
    data-accepted-message="{{ \App\Services\InvitationService::ACCEPTED_MESSAGE }}"
    data-applications-url="{{ url('student/applications') }}/">
    <x-page-header title="Invitations" subtitle="Companies that invited you to apply. Accepting sends your profile and latest resume." />

    <div data-inv-flash></div>
    <div data-inv-swap="notice">
        @if(session('needs_resume'))
            <div class="alert alert-warning"><a href="{{ route('student.resume.index') }}" class="alert-link">Generate your resume</a>, then come back to accept.</div>
        @endif
    </div>

    <div class="sbx-split sb-layout-2">
        <section class="ib-card card sbx-list-card" aria-label="Your invitations">
            <div class="sbx-tabs" role="group" aria-label="Invitation status" data-inv-swap="tabs">
                @foreach($tabs as $key => $label)
                    <button type="button" class="sb-section-tab sbx-tab {{ $key === $activeTab ? 'active' : '' }}"
                            data-inv-tab="{{ $key }}" data-label="{{ $label }}" aria-pressed="{{ $key === $activeTab ? 'true' : 'false' }}">
                        {{ $label }} <span class="sbx-tab-count">{{ $counts[$key] ?? 0 }}</span>
                    </button>
                @endforeach
            </div>

            <div class="sbx-list-region" data-inv-list-region>
                <ul class="sbx-list" data-inv-swap="list">
                    @foreach($invitations as $invitation)
                        @php($display = $displayStatus($invitation))
                        <li @if($invitation->status->value !== $activeTab) hidden @endif>
                            <button type="button" class="sbx-row {{ $selected?->is($invitation) ? 'is-selected' : '' }}"
                                    data-inv-id="{{ $invitation->id }}" data-tab="{{ $invitation->status->value }}"
                                    aria-pressed="{{ $selected?->is($invitation) ? 'true' : 'false' }}" aria-controls="invDetailPanel">
                                <span class="sbx-row-text">
                                    <span class="sbx-row-title">{{ $invitation->internship->title }}</span>
                                    <span class="sbx-row-meta">{{ $invitation->internship->employer->company_name }} · {{ $invitation->created_at->timezone('Asia/Manila')->format('M j') }}</span>
                                </span>
                                <span class="ap-status ap-status--{{ $pillClass[$display->value] }}">{{ $display->label() }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
                <div class="sbx-empty" data-inv-empty @if($invitations->contains(fn ($invitation) => $invitation->status->value === $activeTab)) hidden @endif>
                    <p data-inv-empty-text>No {{ strtolower($tabs[$activeTab]) }} invitations yet.</p>
                    <a href="{{ route('student.internships.index') }}" class="btn btn-outline-secondary btn-sm">Browse internships</a>
                </div>
            </div>

            <div data-inv-swap="pages">
                @if($invitations->hasPages())<div class="sbx-pages">{{ $invitations->links() }}</div>@endif
            </div>
        </section>

        <section class="ib-card card sbx-detail" id="invDetailPanel" aria-label="Invitation details">
            <div class="sbx-detail-body" data-inv-detail-body>
                @if($selected)
                    @include('student.invitations.partials.detail', ['invitation' => $selected, ...$detailData])
                @else
                    <p class="sbx-placeholder">Select an invitation to see its details.</p>
                @endif
            </div>
        </section>
    </div>

    {{-- Detail for each row, cloned into the panel on select (same pattern as Browse internships). --}}
    <div data-inv-swap="templates" hidden>
        @foreach($invitations as $invitation)
            <template id="invDetail-{{ $invitation->id }}">@include('student.invitations.partials.detail', ['invitation' => $invitation, ...$detailData])</template>
        @endforeach
    </div>
    <template id="invDetailPlaceholder"><p class="sbx-placeholder">Select an invitation to see its details.</p></template>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const page = document.getElementById('invitationsPage');
    if (!page) return;

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const stacked = window.matchMedia('(max-width: 900px)');
    const panel = document.getElementById('invDetailPanel');
    const body = page.querySelector('[data-inv-detail-body]');
    let tab = page.dataset.activeTab || 'pending';
    let selectedId = page.dataset.selectedId || null;

    const rows = () => [...page.querySelectorAll('.sbx-row')];
    const visibleRows = () => rows().filter((row) => !row.closest('li').hidden);

    // Same as Browse internships: re-add the class so the "rise" keyframe plays on every change.
    const replay = (element) => {
        if (reduceMotion || !element) return;
        element.classList.remove('is-changing');
        void element.offsetWidth;
        element.classList.add('is-changing');
    };

    // "(14 days left)" stays current; once the date passes, the invitation shows Expired and Accept is disabled.
    const updateExpiry = () => {
        body.querySelectorAll('[data-expires]').forEach((expiry) => {
            const left = Date.parse(expiry.dataset.expires) - Date.now();
            const label = expiry.querySelector('[data-days-left]');
            if (left <= 0) {
                if (label) label.textContent = '· Expired';
                const accept = body.querySelector('[data-inv-accept]');
                if (accept) {
                    accept.disabled = true;
                    accept.title = 'This invitation has expired.';
                }
                return;
            }
            const days = Math.ceil(left / 86400000);
            const hours = Math.ceil(left / 3600000);
            if (label) {
                label.textContent = left >= 86400000
                    ? `(${days} ${days === 1 ? 'day' : 'days'} left)`
                    : `(${hours} ${hours === 1 ? 'hour' : 'hours'} left)`;
            }
        });
    };

    const select = (id, { animate = true, progressFrom = null } = {}) => {
        selectedId = id ? String(id) : null;
        rows().forEach((row) => {
            const on = row.dataset.invId === selectedId;
            row.classList.toggle('is-selected', on);
            row.setAttribute('aria-pressed', String(on));
        });

        const template = selectedId && document.getElementById(`invDetail-${selectedId}`);
        body.replaceChildren((template || document.getElementById('invDetailPlaceholder')).content.cloneNode(true));
        updateExpiry();

        const steps = body.querySelector('.ap-steps');
        if (progressFrom !== null && steps && !reduceMotion) {
            // After Accept/Decline: the line moves from the old step to the new one, and the new step rings.
            const target = steps.style.getPropertyValue('--ap-progress');
            steps.style.setProperty('--ap-progress', progressFrom);
            steps.querySelector('.ap-step.is-current')?.classList.add('is-new');
            requestAnimationFrame(() => requestAnimationFrame(() => steps.style.setProperty('--ap-progress', target)));
        } else if (animate) {
            replay(panel);
        }
    };

    const showTab = (next, { animate = true, keepSelection = false } = {}) => {
        tab = next;
        page.querySelectorAll('[data-inv-tab]').forEach((button) => {
            const on = button.dataset.invTab === tab;
            button.classList.toggle('active', on);
            button.setAttribute('aria-pressed', String(on));
        });
        rows().forEach((row) => { row.closest('li').hidden = row.dataset.tab !== tab; });

        const visible = visibleRows();
        const empty = page.querySelector('[data-inv-empty]');
        const label = page.querySelector(`[data-inv-tab="${tab}"]`)?.dataset.label || tab;
        empty.hidden = visible.length > 0;
        empty.querySelector('[data-inv-empty-text]').textContent = `No ${label.toLowerCase()} invitations yet.`;
        if (animate) replay(page.querySelector('[data-inv-list-region]'));

        if (!keepSelection || !visible.some((row) => row.dataset.invId === selectedId)) {
            select(visible[0]?.dataset.invId ?? null, { animate });
        }
    };

    page.addEventListener('click', (event) => {
        const tabButton = event.target.closest('[data-inv-tab]');
        if (tabButton) {
            if (tabButton.dataset.invTab !== tab) showTab(tabButton.dataset.invTab);
            return;
        }
        const row = event.target.closest('.sbx-row');
        if (!row) return;
        if (row.dataset.invId !== selectedId) select(row.dataset.invId);
        if (stacked.matches) panel.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
    });

    // Arrow Up/Down, Home and End move through the list.
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
        select(visible[next].dataset.invId);
    });

    const showFlash = (message, kind) => {
        const box = page.querySelector('[data-inv-flash]');
        if (!message) {
            box.replaceChildren();
            return;
        }
        // Same markup as the layout's flash messages.
        const alert = document.createElement('div');
        alert.className = `alert alert-${kind} alert-dismissible fade show mb-4`;
        alert.setAttribute('role', 'alert');
        const icon = document.createElement('i');
        icon.className = `bi ${kind === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'} me-2`;
        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'btn-close';
        close.dataset.bsDismiss = 'alert';
        close.setAttribute('aria-label', 'Close');
        alert.append(icon, document.createTextNode(message), close);
        box.replaceChildren(alert);
    };

    // Swaps the counts, list, notices and detail templates for the server's fresh copy.
    const applyFresh = (fresh) => {
        page.querySelectorAll('[data-inv-swap]').forEach((region) => {
            const replacement = fresh.querySelector(`[data-inv-swap="${region.dataset.invSwap}"]`);
            if (replacement) region.replaceWith(document.importNode(replacement, true));
        });
    };

    const fetchPage = async (url, options = {}) => {
        const response = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'text/html' }, ...options });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return { response, doc: new DOMParser().parseFromString(await response.text(), 'text/html') };
    };

    // Accept and Decline post to the same routes as before, after the app's confirm dialog
    // (skillbridge-ui.js re-submits only once confirmed). The page then updates in place.
    page.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-inv-action]');
        if (!form) return;
        event.preventDefault();

        const button = form.querySelector('button[type="submit"]');
        const original = button.innerHTML;
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.innerHTML = '<span class="sb-submit-spinner" aria-hidden="true"></span>Working…';
        const id = form.dataset.invId;
        const progressFrom = body.querySelector('.ap-steps')?.style.getPropertyValue('--ap-progress') ?? null;

        try {
            let { response, doc } = await fetchPage(form.action, { method: 'POST', body: new FormData(form) });
            let message;
            let kind = 'success';
            if (new URL(response.url).pathname.startsWith(new URL(page.dataset.applicationsUrl).pathname)) {
                // Accepted: the server sent us to the new application; reload this list for the new state.
                message = page.dataset.acceptedMessage;
                ({ doc } = await fetchPage(window.location.href));
            } else {
                const error = doc.querySelector('.alert-danger[role="alert"]');
                const success = doc.querySelector('.alert-success[role="alert"]');
                kind = error ? 'danger' : 'success';
                message = (error || success)?.textContent.replace(/\s+/g, ' ').trim();
            }

            const fresh = doc.getElementById('invitationsPage');
            if (!fresh) throw new Error('Unexpected response');
            applyFresh(fresh);

            const row = rows().find((candidate) => candidate.dataset.invId === id);
            selectedId = row ? id : null;
            showTab(row ? row.dataset.tab : tab, { animate: false, keepSelection: true });
            if (row) select(id, { progressFrom: kind === 'success' ? progressFrom : null });
            showFlash(message, kind);
        } catch (error) {
            button.disabled = false;
            button.removeAttribute('aria-busy');
            button.innerHTML = original;
            showFlash('Something went wrong. Please try again.', 'danger');
        }
    });

    updateExpiry();
    window.setInterval(updateExpiry, 60000);
})();
</script>
@endpush
