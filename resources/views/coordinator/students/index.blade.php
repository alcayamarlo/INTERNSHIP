@extends('layouts.app')

@section('title', 'Students')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/coordinator-students.css') }}?v={{ @filemtime(public_path('css/coordinator-students.css')) }}">
@endpush

@section('content')
<main class="coordinator-students-page" id="coordinatorStudentsPage">
    <x-page-header title="Students" subtitle="Monitor student profiles and progress" eyebrow="Student tracking">
        <dl class="students-summary" aria-label="Student summary">
            <div class="students-summary-cell">
                <dt>Total students</dt>
                <dd id="studentsTotal">{{ number_format($summary['total']) }}</dd>
            </div>
            <div class="students-summary-cell">
                <dt>Average profile completion</dt>
                <dd id="studentsAverage">{{ $summary['average_completion'] }}%</dd>
            </div>
            <div class="students-summary-cell">
                <dt>Below 50% completion</dt>
                <dd id="studentsBelow">{{ number_format($summary['below_50']) }}</dd>
            </div>
        </dl>
    </x-page-header>

    <section class="students-panel" aria-label="Students">
    <div class="students-tools" role="search" aria-label="Search and filter students">
        <label class="students-search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <span class="visually-hidden">Search students by name, school ID, program, email, or skill</span>
            <input
                id="studentSearch"
                type="search"
                value="{{ $filters['search'] }}"
                placeholder="Search by name, school ID, program, or skill..."
                autocomplete="off"
            >
        </label>
        <label class="students-select">
            <span class="visually-hidden">Filter students by program</span>
            <select id="studentProgram">
                <option value="">All programs</option>
                @foreach($programs as $program)
                    <option value="{{ $program }}" @selected($filters['program'] === $program)>{{ $program }}</option>
                @endforeach
            </select>
        </label>
        <label class="students-select">
            <span class="visually-hidden">Filter students by year level</span>
            <select id="studentYear">
                <option value="">All year levels</option>
                @foreach(['1st Year', '2nd Year', '3rd Year', '4th Year'] as $year)
                    <option value="{{ $year }}" @selected(($filters['year_level'] ?? '') === $year)>{{ $year }}</option>
                @endforeach
            </select>
        </label>
        <label class="students-select">
            <span class="visually-hidden">Filter students by status</span>
            <select id="studentStatus">
                <option value="">Any status</option>
                <option value="eligible" @selected(($filters['status'] ?? '') === 'eligible')>Eligible to apply</option>
                <option value="not_eligible" @selected(($filters['status'] ?? '') === 'not_eligible')>Not eligible yet</option>
                <option value="placed" @selected(($filters['status'] ?? '') === 'placed')>Placed</option>
                <option value="unplaced" @selected(($filters['status'] ?? '') === 'unplaced')>Not placed</option>
            </select>
        </label>
        <label class="students-select">
            <span class="visually-hidden">Sort students</span>
            <select id="studentSort">
                <option value="name" @selected($filters['sort'] === 'name')>Name</option>
                <option value="completion_desc" @selected($filters['sort'] === 'completion_desc')>Profile completion highest first</option>
                <option value="completion_asc" @selected($filters['sort'] === 'completion_asc')>Profile completion lowest first</option>
            </select>
        </label>
    </div>

    <div id="studentResults" class="student-tracking-list" aria-live="polite" aria-busy="false"></div>
    <div id="studentsEmpty" class="students-empty" hidden>
        <span class="students-empty-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
        <strong>No students match these filters</strong>
    </div>
    <div class="students-footer">
        <p class="students-showing" id="studentsShowing" aria-live="polite"></p>
        <nav id="studentsPagination" class="students-pagination" aria-label="Student pages"></nav>
    </div>
    </section>
</main>

<script id="studentsInitialData" type="application/json">{!! json_encode([
    'students' => $initialStudents,
    'pagination' => $pagination,
    'summary' => $summary,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endsection

@push('scripts')
    <script src="{{ asset('js/coordinator-students.js') }}?v={{ @filemtime(public_path('js/coordinator-students.js')) }}" defer></script>
@endpush
