@extends('layouts.app')

@section('title', 'My Applications')


@php
    use App\Enums\ApplicationStatus as Status;

    $tabs = [
        'all' => 'All',
        'applied' => 'Applied',
        'reviewed' => 'Reviewed',
        'interview' => 'Interview',
        'accepted' => 'Accepted',
        'rejected' => 'Rejected',
    ];
    $tabUrl = fn (string $tab) => route('student.applications.index', array_filter(
        array_merge(request()->except(['status', 'page']), ['status' => $tab === 'all' ? null : $tab]),
        fn ($value) => $value !== null && $value !== ''
    ));
    $sortOptions = [
        'status' => 'Status (high to low)',
        'applied' => 'Applied date (newest first)',
        'match' => 'Match (high to low)',
    ];
    $sortNote = match ($sort) {
        'applied' => 'Sorted by applied date: newest first',
        'match' => 'Sorted by match: highest first',
        default => 'Sorted by status: Accepted → Interview → Reviewed → Applied → Rejected',
    };
    // Same pill colours as the Application Details page.
    $pill = fn (Status $status) => match ($status) {
        Status::Applied, Status::Submitted => 'applied',
        Status::Accepted, Status::Completed => 'accepted',
        default => $status->value,
    };
    $hasFilters = collect(['job_title', 'company', 'location', 'applied_from', 'applied_to'])->contains(fn ($field) => request()->filled($field)) || $activeStatus !== 'all';
    $sortArrow = '<i class="bi bi-arrow-down" aria-hidden="true"></i>';
@endphp

@section('content')
<div class="sb-dash">

<x-breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('student.dashboard')], ['label' => 'Internships', 'url' => route('student.internships.index')], ['label' => 'My Applications']]" />

<x-page-header title="My Applications" subtitle="Track your internship application status.">
    <a href="{{ route('student.internships.index') }}" class="btn btn-outline-secondary"><i class="bi bi-search" aria-hidden="true"></i> Browse internships</a>
</x-page-header>

{{-- =========================================================
     SUMMARY (all applications, whatever the filters)
========================================================== --}}
<section class="sb-kpis" aria-label="Application summary" data-live-region="application-summary" data-live-topics="applications">
    @foreach([
        ['total', 'Total applications', 'bi-send'],
        ['applied', 'Applied', 'bi-hourglass-split'],
        ['reviewed', 'Reviewed', 'bi-eye'],
        ['accepted', 'Accepted', 'bi-trophy'],
        ['interviews', 'Interviews scheduled', 'bi-calendar-event'],
    ] as [$key, $label, $icon])
        <div class="card sb-kpi">
            <i class="bi {{ $icon }}" aria-hidden="true"></i>
            {{-- data-count-up: the app's existing number count-up (resources/js/app.js) --}}
            <span class="sb-kpi-value" data-count-up="{{ $summary[$key] }}">{{ $summary[$key] }}</span>
            <span class="sb-kpi-label">{{ $label }}</span>
        </div>
    @endforeach
</section>

{{-- =========================================================
     FILTERS (one row). Status, sort and rows per page join this form through form="maFilters".
========================================================== --}}
<form method="GET" action="{{ route('student.applications.index') }}" id="maFilters" class="card sb-filterbar" aria-label="Filter applications">
    <input type="hidden" name="status" value="{{ $activeStatus === 'all' ? '' : $activeStatus }}" data-status-input>
    <div class="sb-filter-row">
        <div class="sb-filter-field"><label for="maTitle">Job title</label><input id="maTitle" name="job_title" class="form-control" value="{{ request('job_title') }}" placeholder="e.g. Web developer"></div>
        <div class="sb-filter-field"><label for="maCompany">Company</label><input id="maCompany" name="company" class="form-control" value="{{ request('company') }}" placeholder="Company name"></div>
        <div class="sb-filter-field"><label for="maLocation">Location</label><input id="maLocation" name="location" class="form-control" value="{{ request('location') }}" placeholder="e.g. Cebu City"></div>
        <div class="sb-filter-field"><label for="maFrom">Applied from</label><input id="maFrom" type="date" name="applied_from" class="form-control" value="{{ request('applied_from') }}"></div>
        <div class="sb-filter-field"><label for="maTo">Applied to</label><input id="maTo" type="date" name="applied_to" class="form-control" value="{{ request('applied_to') }}"></div>
        <div class="sb-filter-actions">
            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel" aria-hidden="true"></i> Apply filters</button>
            <a href="{{ route('student.applications.index') }}" class="btn btn-outline-secondary" data-filter-clear>Clear</a>
        </div>
    </div>
</form>

<p class="visually-hidden" role="status" aria-live="polite" data-filter-status></p>

{{-- =========================================================
     RESULTS: tabs, table, upcoming interviews. Swapped in place on tab/filter/sort changes,
     and refreshed live when an application changes (data-live-region).
========================================================== --}}
<div id="maResults" class="sb-results" data-live-region="my-applications" data-live-topics="applications" data-total="{{ $applications->total() }}" aria-busy="false">

    <nav class="sb-section-tabs" aria-label="Filter by status">
        @foreach($tabs as $tab => $label)
            <a href="{{ $tabUrl($tab) }}" class="sb-section-tab {{ $activeStatus === $tab ? 'active' : '' }}" data-status-tab="{{ $tab === 'all' ? '' : $tab }}" @if($activeStatus === $tab) aria-current="page" @endif>
                {{ $label }} <span class="sb-tab-count">{{ $tabCounts[$tab] ?? 0 }}</span>
            </a>
        @endforeach
    </nav>

    <div class="sb-layout-2">
        <section class="card" aria-labelledby="maListTitle">
            <h2 id="maListTitle" class="visually-hidden">Applications</h2>

            <div class="sb-toolbar-row">
                <p class="sb-note"><i class="bi bi-sort-down" aria-hidden="true"></i> {{ $sortNote }}</p>
                <label class="sb-inline-field">
                    <span>Sort:</span>
                    <select name="sort" form="maFilters" class="form-select form-select-sm" aria-label="Sort applications">
                        @foreach($sortOptions as $value => $label)
                            <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            @if($applications->count())
                <div class="table-responsive">
                    <table class="table align-middle sb-table-stack">
                        <thead>
                            <tr>
                                <th scope="col">Internship</th>
                                <th scope="col">Location</th>
                                <th scope="col" @if($sort === 'match') aria-sort="descending" @endif>Match {!! $sort === 'match' ? $sortArrow : '' !!}</th>
                                <th scope="col" @if($sort === 'status') aria-sort="descending" @endif>Status {!! $sort === 'status' ? $sortArrow : '' !!}</th>
                                <th scope="col" @if($sort === 'applied') aria-sort="descending" @endif>Applied {!! $sort === 'applied' ? $sortArrow : '' !!}</th>
                                <th scope="col">Interview</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($applications as $app)
                                @php $editable = in_array($app->status, [Status::Applied, Status::Submitted], true); @endphp
                                <tr>
                                    <td class="sb-stack-block">
                                        <a href="{{ route('student.applications.show', $app) }}" class="sb-row-title">{{ $app->internship->title }}</a>
                                        <span class="sb-row-sub">{{ $app->internship->employer->company_name }}</span>
                                    </td>
                                    <td data-label="Location">{{ $app->internship->location ?: '—' }}</td>
                                    <td data-label="Match"><span class="sb-tag">{{ $app->match_percentage ?? 0 }}%</span></td>
                                    <td data-label="Status"><x-status-badge :status="$app->status->value" :label="$app->status->label()" /></td>
                                    <td data-label="Applied">{{ $app->applied_at?->format('M j, Y') ?? '—' }}</td>
                                    <td data-label="Interview">{{ $app->interview_at?->format('M j, Y · g:i A') ?? '—' }}</td>
                                    <td data-label="Actions" class="text-end">
                                        <span class="sb-icon-actions">
                                            <a href="{{ route('student.applications.show', $app) }}" class="sb-icon-btn" title="View" aria-label="View application for {{ $app->internship->title }}"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                            @if($editable)
                                                <a href="{{ route('student.applications.edit', $app) }}" class="sb-icon-btn" title="Edit" aria-label="Edit application for {{ $app->internship->title }}"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                                {{-- Uses the app's shared confirmation dialog before withdrawing --}}
                                                <form method="POST" action="{{ route('student.applications.destroy', $app) }}" class="d-inline"
                                                      data-confirm="Withdraw your application to {{ $app->internship->title }} at {{ $app->internship->employer->company_name }}? The company will no longer see it."
                                                      data-confirm-title="Withdraw application" data-confirm-label="Withdraw">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="sb-icon-btn sb-icon-btn--danger" title="Withdraw" aria-label="Withdraw application for {{ $app->internship->title }}"><i class="bi bi-x-circle" aria-hidden="true"></i></button>
                                                </form>
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Footer: count, page links, rows per page --}}
                <div class="card-footer sb-list-footer">
                    <p class="sb-note">Showing {{ $applications->firstItem() }}–{{ $applications->lastItem() }} of {{ $applications->total() }} {{ \Illuminate\Support\Str::plural('application', $applications->total()) }}</p>
                    <div class="sb-footer-tools">
                        @if($applications->hasPages())
                            <nav aria-label="Application pages">
                                <ul class="pagination mb-0">
                                    <li class="page-item {{ $applications->onFirstPage() ? 'disabled' : '' }}">
                                        @if($applications->onFirstPage())
                                            <span class="page-link" aria-label="Previous">&lsaquo;</span>
                                        @else
                                            <a class="page-link" href="{{ $applications->previousPageUrl() }}" rel="prev" aria-label="Previous" data-results-link>&lsaquo;</a>
                                        @endif
                                    </li>
                                    @foreach($applications->getUrlRange(1, $applications->lastPage()) as $page => $url)
                                        @if($page === $applications->currentPage())
                                            <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                                        @else
                                            <li class="page-item"><a class="page-link" href="{{ $url }}" aria-label="Go to page {{ $page }}" data-results-link>{{ $page }}</a></li>
                                        @endif
                                    @endforeach
                                    <li class="page-item {{ $applications->hasMorePages() ? '' : 'disabled' }}">
                                        @if($applications->hasMorePages())
                                            <a class="page-link" href="{{ $applications->nextPageUrl() }}" rel="next" aria-label="Next" data-results-link>&rsaquo;</a>
                                        @else
                                            <span class="page-link" aria-label="Next">&rsaquo;</span>
                                        @endif
                                    </li>
                                </ul>
                            </nav>
                        @endif
                        <label class="sb-inline-field">
                            <span>Rows per page</span>
                            <select name="per_page" form="maFilters" class="form-select form-select-sm" aria-label="Rows per page">
                                @foreach([10, 25, 50] as $size)
                                    <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </div>
            @else
                <x-empty-state icon="bi-send" title="{{ $hasFilters ? 'No applications match your filters' : 'No applications yet' }}" message="Apply to an internship and it will show up here, or clear the filters to see everything.">
                    <a href="{{ route('student.internships.index') }}" class="btn btn-primary">Browse internships</a>
                </x-empty-state>
            @endif
        </section>

        {{-- Upcoming interviews --}}
        <aside class="card" aria-labelledby="maInterviewsTitle">
            <h2 class="sb-card-title" id="maInterviewsTitle"><i class="bi bi-calendar-event" aria-hidden="true"></i> Upcoming interviews</h2>
            @forelse($upcomingInterviews as $interview)
                <a href="{{ route('student.applications.show', $interview) }}" class="sb-agenda-item">
                    <span class="sb-date-tile" aria-hidden="true">
                        <span class="sb-date-month">{{ $interview->interview_at->format('M') }}</span>
                        <span class="sb-date-day">{{ $interview->interview_at->format('j') }}</span>
                    </span>
                    <span class="sb-agenda-main">
                        <span class="sb-row-title">{{ $interview->internship->title }}</span>
                        <span class="sb-row-sub">{{ $interview->internship->employer->company_name }}</span>
                        <span class="sb-row-sub">
                            <i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $interview->internship->location ?: 'Location not provided' }}
                            · <i class="bi bi-clock" aria-hidden="true"></i> {{ $interview->interview_at->format('D, M j · g:i A') }}
                        </span>
                    </span>
                </a>
            @empty
                <div class="sb-empty">
                    <i class="bi bi-calendar-x" aria-hidden="true"></i>
                    <p class="mb-1"><strong>No upcoming interviews</strong></p>
                    <p class="mb-0">When a company schedules an interview, it will appear here.</p>
                </div>
            @endforelse
        </aside>
    </div>
</div>

</div>
@endsection

@push('scripts')
<script>
    (function () {
        // Tabs, filters, sort, rows per page and page links update the results in place (no page reload).
        const form = document.getElementById('maFilters');
        if (!form || !window.fetch || !window.DOMParser) return;
        const statusInput = form.querySelector('[data-status-input]');
        const announcer = document.querySelector('[data-filter-status]');
        let inFlight = null;

        const urlFromForm = () => {
            const params = new URLSearchParams();
            // Includes the sort and rows-per-page selects that sit in the table (form="maFilters").
            for (const [name, value] of new FormData(form)) {
                if (String(value).trim() !== '') params.append(name, value);
            }
            const query = params.toString();
            return form.action + (query ? '?' + query : '');
        };

        async function load(url) {
            inFlight?.abort();
            inFlight = new AbortController();
            const region = document.getElementById('maResults');
            region?.setAttribute('aria-busy', 'true');
            region?.classList.add('is-loading');
            try {
                const response = await fetch(url, { headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' }, signal: inFlight.signal });
                if (!response.ok) throw new Error('HTTP ' + response.status);
                const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                const fresh = page.getElementById('maResults');
                if (!fresh) throw new Error('No results area');
                document.getElementById('maResults')?.replaceWith(fresh);
                fresh.classList.add('is-changing');
                history.replaceState(null, '', url);
                const total = Number(fresh.dataset.total || 0);
                if (announcer) announcer.textContent = total === 1 ? '1 application found.' : total + ' applications found.';
            } catch (error) {
                if (error.name !== 'AbortError') window.location.href = url; // fall back to a normal page load
            } finally {
                const current = document.getElementById('maResults');
                current?.setAttribute('aria-busy', 'false');
                current?.classList.remove('is-loading');
            }
        }

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            load(urlFromForm());
        });

        // Sort, rows per page and the date range: apply as soon as they change.
        document.addEventListener('change', (event) => {
            if (event.target.matches('select[form="maFilters"]')) load(urlFromForm());
            // Dates apply once complete (year 1900+, not mid-typing) or when cleared.
            if (event.target.matches('#maFilters input[type="date"]')) {
                const value = event.target.value;
                if (value === '' || value.slice(0, 4) >= '1900') load(urlFromForm());
            }
        });

        document.addEventListener('click', (event) => {
            // Status tabs: remember the tab in the form so filters and sort keep it.
            const tab = event.target.closest('[data-status-tab]');
            if (tab) {
                event.preventDefault();
                statusInput.value = tab.dataset.statusTab;
                load(urlFromForm());
                return;
            }
            const pageLink = event.target.closest('[data-results-link]');
            if (pageLink) {
                event.preventDefault();
                load(pageLink.href);
                return;
            }
            const clear = event.target.closest('[data-filter-clear]');
            if (clear) {
                event.preventDefault();
                form.querySelectorAll('input:not([type="hidden"])').forEach((input) => { input.value = ''; });
                statusInput.value = '';
                load(clear.href);
            }
        });
    })();
</script>
@endpush
