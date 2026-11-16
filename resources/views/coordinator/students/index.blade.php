@extends('layouts.app')

@section('title', 'Students')

@section('content')
<div class="sb-dash" id="coordinatorStudentsPage">
    <x-page-header title="Students" subtitle="Monitor student profiles and progress" eyebrow="Student tracking" />

    <section class="sb-kpis" aria-label="Student summary">
        <div class="card sb-kpi">
            <i class="bi bi-people" aria-hidden="true"></i>
            <span class="sb-kpi-value" id="studentsTotal">{{ number_format($summary['total']) }}</span>
            <span class="sb-kpi-label">Total students</span>
        </div>
        <div class="card sb-kpi">
            <i class="bi bi-person-check" aria-hidden="true"></i>
            <span class="sb-kpi-value" id="studentsAverage">{{ $summary['average_completion'] }}%</span>
            <span class="sb-kpi-label">Average profile completion</span>
        </div>
        <div class="card sb-kpi">
            <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
            <span class="sb-kpi-value" id="studentsBelow">{{ number_format($summary['below_50']) }}</span>
            <span class="sb-kpi-label">Below 50% completion</span>
        </div>
    </section>

    <div class="card sb-filterbar" role="search" aria-label="Search and filter students">
        <div class="sb-filter-row">
            <div class="sb-filter-field">
                <label class="form-label" for="studentSearch">Search</label>
                <input
                    id="studentSearch"
                    class="form-control"
                    type="search"
                    value="{{ $filters['search'] }}"
                    placeholder="Search by name, school ID, program, or skill..."
                    aria-describedby="studentSearchHelp"
                    autocomplete="off"
                >
                <span id="studentSearchHelp" class="visually-hidden">Search students by name, school ID, program, email, or skill</span>
            </div>
            <div class="sb-filter-field">
                <label class="form-label" for="studentProgram">Program</label>
                <select id="studentProgram" class="form-select">
                    <option value="">All programs</option>
                    @foreach($programs as $program)
                        <option value="{{ $program }}" @selected($filters['program'] === $program)>{{ $program }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sb-filter-field sb-filter-field--sm">
                <label class="form-label" for="studentYear">Year level</label>
                <select id="studentYear" class="form-select">
                    <option value="">All year levels</option>
                    @foreach(['1st Year', '2nd Year', '3rd Year', '4th Year'] as $year)
                        <option value="{{ $year }}" @selected(($filters['year_level'] ?? '') === $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sb-filter-field sb-filter-field--sm">
                <label class="form-label" for="studentStatus">Status</label>
                <select id="studentStatus" class="form-select">
                    <option value="">Any status</option>
                    <option value="eligible" @selected(($filters['status'] ?? '') === 'eligible')>Eligible to apply</option>
                    <option value="not_eligible" @selected(($filters['status'] ?? '') === 'not_eligible')>Not eligible yet</option>
                    <option value="placed" @selected(($filters['status'] ?? '') === 'placed')>Placed</option>
                    <option value="unplaced" @selected(($filters['status'] ?? '') === 'unplaced')>Not placed</option>
                </select>
            </div>
            <div class="sb-filter-field sb-filter-field--sm">
                <label class="form-label" for="studentSort">Sort</label>
                <select id="studentSort" class="form-select">
                    <option value="name" @selected($filters['sort'] === 'name')>Name</option>
                    <option value="completion_desc" @selected($filters['sort'] === 'completion_desc')>Profile completion highest first</option>
                    <option value="completion_asc" @selected($filters['sort'] === 'completion_asc')>Profile completion lowest first</option>
                </select>
            </div>
        </div>
    </div>

    <section class="sb-results" aria-label="Students">
        <div id="studentResults" class="sb-card-grid" aria-live="polite" aria-busy="false"></div>
        <div id="studentsEmpty" class="card" hidden>
            <div class="sb-empty">
                <i class="bi bi-people" aria-hidden="true"></i>
                <strong class="sb-empty-title">No students match these filters</strong>
            </div>
        </div>
    </section>

    <div class="sb-list-footer">
        <p class="text-muted small mb-0" id="studentsShowing" aria-live="polite"></p>
        <nav aria-label="Student pages"><ul id="studentsPagination" class="pagination mb-0"></ul></nav>
    </div>
</div>

<script id="studentsInitialData" type="application/json">{!! json_encode([
    'students' => $initialStudents,
    'pagination' => $pagination,
    'summary' => $summary,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endsection

@push('scripts')
    <script src="{{ asset('js/coordinator-students.js') }}?v={{ @filemtime(public_path('js/coordinator-students.js')) }}" defer></script>
@endpush
