@extends('layouts.app')

@section('title', 'Browse Students')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/employer-browse-students.css') }}?v={{ @filemtime(public_path('css/employer-browse-students.css')) }}">
@endpush

@section('content')
<div class="bs">
<x-page-header title="Browse Students" subtitle="Find students who allow companies to see their profile, and invite them to apply.">
    <a href="{{ route('employer.invitations.index') }}" class="btn btn-outline-secondary"><i class="bi bi-envelope-paper" aria-hidden="true"></i> Sent invitations</a>
</x-page-header>

<form method="GET" action="{{ route('employer.students.index') }}" class="card bs-toolbar" role="search" aria-label="Filter students">
    @if($filters['shortlisted'] ?? false)<input type="hidden" name="shortlisted" value="1">@endif

    <div class="bs-tabs" role="group" aria-label="Student lists">
        <a href="{{ route('employer.students.index') }}" class="bs-tab {{ ($filters['shortlisted'] ?? false) ? '' : 'is-active' }}">All students</a>
        <a href="{{ route('employer.students.index', ['shortlisted' => 1]) }}" class="bs-tab {{ ($filters['shortlisted'] ?? false) ? 'is-active' : '' }}"><i class="bi bi-star-fill" aria-hidden="true"></i> Shortlisted</a>
    </div>

    <div class="bs-filters">
        <div class="bs-field">
            <label class="form-label" for="filterName">Name</label>
            <input id="filterName" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" maxlength="100">
        </div>
        <div class="bs-field">
            <label class="form-label" for="filterProgram">Course / program</label>
            <select id="filterProgram" name="program_id" class="form-select" onchange="this.form.requestSubmit()">
                <option value="">Any</option>
                @foreach($programs as $program)
                    <option value="{{ $program->id }}" @selected((string) ($filters['program_id'] ?? '') === (string) $program->id)>{{ $program->display_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="bs-field bs-field-sm">
            <label class="form-label" for="filterYear">Year level</label>
            <select id="filterYear" name="year_level" class="form-select" onchange="this.form.requestSubmit()">
                <option value="">Any</option>
                @foreach($yearLevels as $yearLevel)
                    <option value="{{ $yearLevel }}" @selected(($filters['year_level'] ?? '') === $yearLevel)>{{ $yearLevel }}</option>
                @endforeach
            </select>
        </div>
        <div class="bs-field bs-field-sm">
            <label class="form-label" for="filterSkill">Skill</label>
            <input id="filterSkill" type="search" name="skill" value="{{ $filters['skill'] ?? '' }}" class="form-control" maxlength="60" placeholder="e.g. Python">
        </div>
        <div class="bs-field bs-field-sm">
            <label class="form-label" for="filterCertificate">Certificate</label>
            <input id="filterCertificate" type="search" name="certificate" value="{{ $filters['certificate'] ?? '' }}" class="form-control" maxlength="100" placeholder="e.g. CCNA">
        </div>
        <div class="bs-actions">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Search</button>
            <a href="{{ route('employer.students.index') }}" class="btn btn-outline-secondary">Clear</a>
        </div>
    </div>
</form>

@if($students->isEmpty())
    <div class="card"><div class="card-body text-center text-muted py-5">No students match these filters.</div></div>
@else
    <div class="bs-results">
        <div class="bs-grid">
            @foreach($students as $student)
                <article class="card bs-card" aria-labelledby="student-{{ $student['student_id'] }}">
                    <div class="bs-head">
                        @if($student['photo_url'])
                            <img src="{{ $student['photo_url'] }}" alt="" width="46" height="46" class="bs-avatar">
                        @else
                            <span class="bs-avatar" aria-hidden="true"><i class="bi bi-person"></i></span>
                        @endif
                        <div class="bs-who">
                            <h2 id="student-{{ $student['student_id'] }}">{{ $student['name'] }}</h2>
                            <small>{{ collect([$student['program'], $student['year_level']])->filter()->implode(' · ') ?: 'Program not set' }}</small>
                        </div>
                    </div>
                    <div class="bs-body">
                        @if($student['skills'])
                            <div class="bs-line">
                                <strong>Skills</strong>
                                <span class="bs-chips">
                                    @foreach($student['skills'] as $skill)<span>{{ $skill }}</span>@endforeach
                                    @if($student['skill_count'] > count($student['skills']))<span class="more">+{{ $student['skill_count'] - count($student['skills']) }}</span>@endif
                                </span>
                            </div>
                        @endif
                        @if($student['certificates'])
                            <div class="bs-line">
                                <strong>Certificates</strong>
                                <span class="bs-text">{{ implode(' • ', $student['certificates']) }}@if($student['certificate_count'] > count($student['certificates'])) <span class="text-muted">+{{ $student['certificate_count'] - count($student['certificates']) }} more</span>@endif</span>
                            </div>
                        @endif
                    </div>
                    <div class="bs-foot">
                        <a href="{{ route('employer.students.show', $student['student_id']) }}" class="btn btn-outline-primary btn-sm">View profile</a>
                        @include('employer.students.partials.shortlist-button')
                        <div>@include('employer.students.partials.invite-button')</div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
    @if($students->hasPages())<div class="bs-pages">{{ $students->links() }}</div>@endif
@endif

@include('employer.students.partials.invite-modal')
</div>{{-- /.bs --}}
@endsection
