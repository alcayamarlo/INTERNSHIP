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
    <x-page-header title="Students" subtitle="Monitor student profiles and progress" eyebrow="Student tracking" />

    <section class="sb-stat-grid" aria-label="Student summary">
        <article class="sb-stat">
            <span class="sb-stat-icon" aria-hidden="true"><i class="bi bi-people"></i></span>
            <div class="sb-stat-body">
                <div class="sb-stat-label">Total students</div>
                <div class="sb-stat-value" id="studentsTotal">{{ number_format($summary['total']) }}</div>
            </div>
        </article>
        <article class="sb-stat">
            <span class="sb-stat-icon sb-stat-icon--success" aria-hidden="true"><i class="bi bi-graph-up-arrow"></i></span>
            <div class="sb-stat-body">
                <div class="sb-stat-label">Average profile completion</div>
                <div class="sb-stat-value" id="studentsAverage">{{ $summary['average_completion'] }}%</div>
            </div>
        </article>
        <article class="sb-stat">
            <span class="sb-stat-icon sb-stat-icon--warning" aria-hidden="true"><i class="bi bi-exclamation-circle"></i></span>
            <div class="sb-stat-body">
                <div class="sb-stat-label">Below 50% completion</div>
                <div class="sb-stat-value" id="studentsBelow">{{ number_format($summary['below_50']) }}</div>
            </div>
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
