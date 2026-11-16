@extends('layouts.app')

@section('title', 'Applications')

@section('content')
@php($monitor = \App\Http\Controllers\Coordinator\ApplicationMonitorController::class)
<div class="sb-dash">

<x-page-header title="Applications and placements" subtitle="Where your students are in the internship process.">
    @if($pendingValidation)
        <a href="{{ route('coordinator.placements.index', ['status' => 'pending_validation']) }}" class="btn btn-primary"><i class="bi bi-shield-exclamation" aria-hidden="true"></i> {{ $pendingValidation }} placement{{ $pendingValidation === 1 ? '' : 's' }} to validate</a>
    @endif
</x-page-header>

<section class="sb-kpis" aria-label="Application summary" data-live-region="application-summary" data-live-topics="applications placements">
    <div class="card sb-kpi">
        <i class="bi bi-send" aria-hidden="true"></i>
        <span class="sb-kpi-value">{{ $totalApplications }}</span>
        <span class="sb-kpi-label">Application{{ $totalApplications === 1 ? '' : 's' }}</span>
    </div>
    <div class="card sb-kpi">
        <i class="bi bi-briefcase" aria-hidden="true"></i>
        <span class="sb-kpi-value">{{ $counts['placed'] }}</span>
        <span class="sb-kpi-label">Placed</span>
    </div>
    <div class="card sb-kpi">
        <i class="bi bi-person-dash" aria-hidden="true"></i>
        <span class="sb-kpi-value">{{ $unplacedTotal }}</span>
        <span class="sb-kpi-label">Not placed</span>
    </div>
</section>

<div>
    <div class="sb-section-tabs" role="tablist" aria-label="Applications and placements" data-ap-tabs>
        <button type="button" role="tab" id="ap-tab-applications" class="sb-section-tab" data-tab="applications"
                aria-controls="ap-panel-applications" aria-selected="true" tabindex="0">
            Applications
            <span class="sb-tab-count" data-live-region="application-tab-count" data-live-topics="applications placements">{{ $applications->total() }}</span>
        </button>
        <button type="button" role="tab" id="ap-tab-not-placed" class="sb-section-tab" data-tab="not-placed"
                aria-controls="ap-panel-not-placed" aria-selected="false" tabindex="-1">
            <span class="d-none d-sm-inline">Students not placed yet</span>
            <span class="d-sm-none">Not placed</span>
            <span class="sb-tab-count">{{ $unplaced->total() }}</span>
        </button>
    </div>

    {{-- Tab 1: applications --}}
    <section id="ap-panel-applications" class="card" role="tabpanel" aria-labelledby="ap-tab-applications">
        <form method="GET" action="{{ route('coordinator.applications.index') }}" class="sb-filterbar sb-toolbar-row d-block" role="search" aria-label="Filter applications">
            @if(! empty($filters['unplaced_q']))<input type="hidden" name="unplaced_q" value="{{ $filters['unplaced_q'] }}">@endif
            <div class="sb-filter-row">
                <div class="sb-filter-field">
                    <label class="form-label" for="monitorSearch">Student name</label>
                    <input id="monitorSearch" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" maxlength="100">
                </div>
                <div class="sb-filter-field sb-filter-field--sm">
                    <label class="form-label" for="monitorStage">Stage</label>
                    <select id="monitorStage" name="stage" class="form-select" onchange="this.form.requestSubmit()">
                        <option value="">All</option>
                        @foreach($stages as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['stage'] ?? null) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sb-filter-field sb-filter-field--sm">
                    <label class="form-label" for="monitorProgram">Program</label>
                    <select id="monitorProgram" name="program_id" class="form-select" onchange="this.form.requestSubmit()">
                        <option value="">All my programs</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}" @selected((string) ($filters['program_id'] ?? '') === (string) $program->id)>{{ $program->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sb-filter-actions"><button type="submit" class="btn btn-primary">Filter</button></div>
            </div>
        </form>

        <div data-live-region="application-list" data-live-topics="applications placements">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Student</th><th>Internship</th><th>Company</th><th>Stage</th><th>Applied</th></tr></thead>
                    <tbody>
                        @forelse($applications as $application)
                            @php($stage = $monitor::stageOf($application))
                            <tr>
                                <td><a href="{{ route('coordinator.students.show', $application->student) }}" class="sb-row-title">{{ $application->student?->user?->name }}</a></td>
                                <td class="text-break">{{ $application->internship?->title }}</td>
                                <td class="text-break">{{ $application->internship?->employer?->company_name }}</td>
                                <td>
                                    <x-status-badge :status="$stage" :label="$stages[$stage] ?? ucfirst($stage)" />
                                    @if($application->placement?->isPendingValidation())<span class="sb-row-sub">Waiting for your validation</span>@endif
                                </td>
                                <td class="text-nowrap">{{ $application->applied_at?->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="sb-table-empty">No applications match.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($applications->hasPages())<div class="card-footer">{{ $applications->links() }}</div>@endif
        </div>
    </section>

    {{-- Tab 2: students not placed yet --}}
    <section id="ap-panel-not-placed" class="card" role="tabpanel" aria-labelledby="ap-tab-not-placed" hidden>
        <form method="GET" action="{{ route('coordinator.applications.index') }}#not-placed" class="sb-filterbar sb-toolbar-row d-block" role="search" aria-label="Filter students not placed yet">
            @if(! empty($filters['q']))<input type="hidden" name="q" value="{{ $filters['q'] }}">@endif
            @if(! empty($filters['stage']))<input type="hidden" name="stage" value="{{ $filters['stage'] }}">@endif
            <div class="sb-filter-row">
                <div class="sb-filter-field">
                    <label class="form-label" for="unplacedSearch">Student name</label>
                    <input id="unplacedSearch" type="search" name="unplaced_q" value="{{ $filters['unplaced_q'] ?? '' }}" class="form-control" maxlength="100">
                </div>
                <div class="sb-filter-field sb-filter-field--sm">
                    <label class="form-label" for="unplacedProgram">Program</label>
                    <select id="unplacedProgram" name="program_id" class="form-select" onchange="this.form.requestSubmit()">
                        <option value="">All my programs</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}" @selected((string) ($filters['program_id'] ?? '') === (string) $program->id)>{{ $program->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sb-filter-actions"><button type="submit" class="btn btn-primary">Filter</button></div>
            </div>
        </form>

        <div class="table-responsive" id="unplaced">
            <table class="table table-hover mb-0">
                <thead><tr><th>Student</th><th>Program · year</th><th>Eligible</th><th>Active applications</th></tr></thead>
                <tbody>
                    @forelse($unplaced as $student)
                        <tr data-unplaced="{{ $student->id }}">
                            <td><a href="{{ route('coordinator.students.show', $student) }}" class="sb-row-title">{{ $student->user?->name }}</a></td>
                            <td>{{ collect([$student->program, $student->year_level])->filter()->implode(' · ') ?: 'Not set' }}</td>
                            <td>
                                @if(in_array($student->id, $eligibleIds, true))
                                    <x-status-badge status="success" label="Eligible" />
                                @else
                                    <x-status-badge status="neutral" label="Not yet" />
                                @endif
                            </td>
                            <td>{{ $student->active_applications_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="sb-table-empty">{{ empty($filters['unplaced_q']) ? 'Every student is placed.' : 'No students match.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- The pagination view prints "Showing X to Y of Z results" above the page links. --}}
        @if($unplaced->hasPages())<div class="card-footer">{{ $unplaced->fragment('not-placed')->links() }}</div>@endif
    </section>
</div>

{{-- Runs right after the tabs so a #not-placed link opens that tab before the first paint. --}}
<script>
    (function () {
        var bar = document.querySelector('[data-ap-tabs]');
        if (!bar) return;
        var tabs = [].slice.call(bar.querySelectorAll('[role="tab"]'));
        var hashes = { 'not-placed': 'not-placed', 'unplaced': 'not-placed' };

        function select(key, updateHash) {
            tabs.forEach(function (tab) {
                var on = tab.dataset.tab === key;
                tab.setAttribute('aria-selected', on ? 'true' : 'false');
                tab.tabIndex = on ? 0 : -1;
                document.getElementById(tab.getAttribute('aria-controls')).hidden = !on;
            });
            if (!updateHash) return;
            var url = new URL(location.href);
            url.hash = key === 'applications' ? '' : key;
            try { history.replaceState(history.state, '', url); } catch (_) {}
        }

        tabs.forEach(function (tab, i) {
            // Enter and Space reach this as a click (native button behavior).
            tab.addEventListener('click', function () { select(tab.dataset.tab, true); });
            // Arrow keys, Home and End move focus between tabs; Enter or Space selects.
            tab.addEventListener('keydown', function (e) {
                var next = e.key === 'ArrowRight' ? i + 1 : e.key === 'ArrowLeft' ? i - 1 : e.key === 'Home' ? 0 : e.key === 'End' ? tabs.length - 1 : null;
                if (next === null) return;
                e.preventDefault();
                tabs[(next + tabs.length) % tabs.length].focus();
            });
        });

        function fromHash() {
            select(hashes[location.hash.slice(1)] || 'applications', false);
        }
        window.addEventListener('hashchange', fromHash);
        fromHash();
    })();
</script>

</div>
@endsection
