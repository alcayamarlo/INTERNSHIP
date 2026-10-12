@extends('layouts.app')

@section('title', 'Students')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/coordinator-students.css') }}">
@endpush

@section('content')
<main class="coordinator-students-page" id="coordinatorStudentsPage">
    <header class="coordinator-students-heading">
        <div>
            <p class="students-eyebrow">Student tracking</p>
            <h1>Students</h1>
            <p>Monitor student profiles and progress</p>
        </div>
    </header>

    <section class="students-summary" aria-label="Student summary">
        <article class="students-summary-card">
            <span>Total students</span>
            <strong id="studentsTotal">{{ number_format($summary['total']) }}</strong>
        </article>
        <article class="students-summary-card completion-average-card">
            <span>Average profile completion</span>
            <strong id="studentsAverage">{{ $summary['average_completion'] }}%</strong>
        </article>
        <article class="students-summary-card completion-low-card">
            <span>Below 50% completion</span>
            <strong id="studentsBelow">{{ number_format($summary['below_50']) }}</strong>
        </article>
    </section>

    <section class="students-tools" aria-label="Search and filter students">
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
            <span class="visually-hidden">Sort students</span>
            <select id="studentSort">
                <option value="name" @selected($filters['sort'] === 'name')>Name</option>
                <option value="completion_desc" @selected($filters['sort'] === 'completion_desc')>Profile completion highest first</option>
                <option value="completion_asc" @selected($filters['sort'] === 'completion_asc')>Profile completion lowest first</option>
            </select>
        </label>
    </section>

    <section id="studentResults" class="student-tracking-list" aria-live="polite" aria-busy="false"></section>
    <section id="studentsEmpty" class="students-empty" hidden>
        <span class="students-empty-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
        <strong>No students found. Try a different name, program, or skill.</strong>
    </section>
    <nav id="studentsPagination" class="students-pagination" aria-label="Student pages"></nav>
</main>

<script id="studentsInitialData" type="application/json">{!! json_encode([
    'students' => $initialStudents,
    'pagination' => $pagination,
    'summary' => $summary,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endsection

@push('scripts')
    <script src="{{ asset('js/coordinator-students.js') }}" defer></script>
@endpush
