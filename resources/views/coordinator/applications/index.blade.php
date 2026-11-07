@extends('layouts.app')

@section('title', 'Applications')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/coordinator-applications.css') }}?v={{ @filemtime(public_path('css/coordinator-applications.css')) }}">
@endpush

@section('content')
@php($monitor = \App\Http\Controllers\Coordinator\ApplicationMonitorController::class)
<div class="ap-page">

<x-page-header title="Applications and placements" subtitle="Where your students are in the internship process.">
    <p class="ap-summary" data-live-region="application-summary" data-live-topics="applications placements">
        <span><strong>{{ $totalApplications }}</strong> application{{ $totalApplications === 1 ? '' : 's' }}</span>
        <span aria-hidden="true">·</span>
        <span><strong>{{ $counts['placed'] }}</strong> placed</span>
        <span aria-hidden="true">·</span>
        <span><strong>{{ $unplacedTotal }}</strong> not placed</span>
    </p>
    @if($pendingValidation)
        <a href="{{ route('coordinator.placements.index', ['status' => 'pending_validation']) }}" class="btn btn-warning"><i class="bi bi-shield-exclamation" aria-hidden="true"></i> {{ $pendingValidation }} placement{{ $pendingValidation === 1 ? '' : 's' }} to validate</a>
    @endif
</x-page-header>

<div class="card ap-card">
    <div class="ap-tabs" role="tablist" aria-label="Applications and placements" data-ap-tabs>
        <button type="button" role="tab" id="ap-tab-applications" class="ap-tab" data-tab="applications"
                aria-controls="ap-panel-applications" aria-selected="true" tabindex="0">
            Applications
            <span class="ap-tab-count" data-live-region="application-tab-count" data-live-topics="applications placements">{{ $applications->total() }}</span>
        </button>
        <button type="button" role="tab" id="ap-tab-not-placed" class="ap-tab" data-tab="not-placed"
                aria-controls="ap-panel-not-placed" aria-selected="false" tabindex="-1">
            <span class="ap-tab-label--long">Students not placed yet</span>
            <span class="ap-tab-label--short">Not placed</span>
            <span class="ap-tab-count">{{ $unplaced->total() }}</span>
        </button>
    </div>

    {{-- Tab 1: applications --}}
    <section id="ap-panel-applications" class="ap-panel" role="tabpanel" aria-labelledby="ap-tab-applications">
        <div class="ap-panel-tools">
            <form method="GET" action="{{ route('coordinator.applications.index') }}" class="ap-filter" role="search" aria-label="Filter applications">
                @if(! empty($filters['unplaced_q']))<input type="hidden" name="unplaced_q" value="{{ $filters['unplaced_q'] }}">@endif
                <div class="ap-filter-field ap-filter-field--wide">
                    <label class="form-label" for="monitorSearch">Student name</label>
                    <input id="monitorSearch" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" maxlength="100">
                </div>
                <div class="ap-filter-field">
                    <label class="form-label" for="monitorStage">Stage</label>
                    <select id="monitorStage" name="stage" class="form-select" onchange="this.form.requestSubmit()">
                        <option value="">All</option>
                        @foreach($stages as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['stage'] ?? null) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ap-filter-field">
                    <label class="form-label" for="monitorProgram">Program</label>
                    <select id="monitorProgram" name="program_id" class="form-select" onchange="this.form.requestSubmit()">
                        <option value="">All my programs</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}" @selected((string) ($filters['program_id'] ?? '') === (string) $program->id)>{{ $program->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ap-filter-action"><button type="submit" class="btn btn-primary">Filter</button></div>
            </form>
        </div>

        <div data-live-region="application-list" data-live-topics="applications placements">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Student</th><th>Internship</th><th>Company</th><th>Stage</th><th>Applied</th></tr></thead>
                    <tbody>
                        @forelse($applications as $application)
                            @php($stage = $monitor::stageOf($application))
                            <tr>
                                <td><a href="{{ route('coordinator.students.show', $application->student) }}">{{ $application->student?->user?->name }}</a></td>
                                <td>{{ $application->internship?->title }}</td>
                                <td>{{ $application->internship?->employer?->company_name }}</td>
                                <td>
                                    <span class="badge text-bg-light border">{{ $stages[$stage] ?? ucfirst($stage) }}</span>
                                    @if($application->placement?->isPendingValidation())<small class="d-block text-warning-emphasis">Waiting for your validation</small>@endif
                                </td>
                                <td>{{ $application->applied_at?->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No applications match.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($applications->hasPages())<div class="card-footer">{{ $applications->links() }}</div>@endif
        </div>
    </section>

    {{-- Tab 2: students not placed yet --}}
    <section id="ap-panel-not-placed" class="ap-panel" role="tabpanel" aria-labelledby="ap-tab-not-placed" hidden>
        <div class="ap-panel-tools">
            <form method="GET" action="{{ route('coordinator.applications.index') }}#not-placed" class="ap-filter" role="search" aria-label="Filter students not placed yet">
                @if(! empty($filters['q']))<input type="hidden" name="q" value="{{ $filters['q'] }}">@endif
                @if(! empty($filters['stage']))<input type="hidden" name="stage" value="{{ $filters['stage'] }}">@endif
                <div class="ap-filter-field ap-filter-field--wide">
                    <label class="form-label" for="unplacedSearch">Student name</label>
                    <input id="unplacedSearch" type="search" name="unplaced_q" value="{{ $filters['unplaced_q'] ?? '' }}" class="form-control" maxlength="100">
                </div>
                <div class="ap-filter-field">
                    <label class="form-label" for="unplacedProgram">Program</label>
                    <select id="unplacedProgram" name="program_id" class="form-select" onchange="this.form.requestSubmit()">
                        <option value="">All my programs</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}" @selected((string) ($filters['program_id'] ?? '') === (string) $program->id)>{{ $program->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ap-filter-action"><button type="submit" class="btn btn-primary">Filter</button></div>
            </form>
        </div>

        <div class="table-responsive" id="unplaced">
            <table class="table table-hover mb-0">
                <thead><tr><th>Student</th><th>Program · year</th><th>Eligible</th><th>Active applications</th></tr></thead>
                <tbody>
                    @forelse($unplaced as $student)
                        <tr data-unplaced="{{ $student->id }}">
                            <td><a href="{{ route('coordinator.students.show', $student) }}">{{ $student->user?->name }}</a></td>
                            <td>{{ collect([$student->program, $student->year_level])->filter()->implode(' · ') ?: 'Not set' }}</td>
                            <td>
                                @if(in_array($student->id, $eligibleIds, true))
                                    <span class="badge text-bg-success">Eligible</span>
                                @else
                                    <span class="badge text-bg-secondary">Not yet</span>
                                @endif
                            </td>
                            <td>{{ $student->active_applications_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">{{ empty($filters['unplaced_q']) ? 'Every student is placed.' : 'No students match.' }}</td></tr>
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
