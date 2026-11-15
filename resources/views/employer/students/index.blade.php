@extends('layouts.app')

@section('title', 'Browse Students')

@section('content')
<div class="sb-dash">
<x-page-header title="Browse Students" subtitle="Find students who allow companies to see their profile, and invite them to apply.">
    <a href="{{ route('employer.invitations.index') }}" class="btn btn-outline-secondary"><i class="bi bi-envelope-paper" aria-hidden="true"></i> Sent invitations</a>
</x-page-header>

<form method="GET" action="{{ route('employer.students.index') }}" class="card sb-filterbar" role="search" aria-label="Filter students">
    @if($filters['shortlisted'] ?? false)<input type="hidden" name="shortlisted" value="1">@endif

    <div class="sb-pill-tabs" role="group" aria-label="Student lists">
        <a href="{{ route('employer.students.index') }}" class="sb-pill-tab {{ ($filters['shortlisted'] ?? false) ? '' : 'is-active' }}">All students</a>
        <a href="{{ route('employer.students.index', ['shortlisted' => 1]) }}" class="sb-pill-tab {{ ($filters['shortlisted'] ?? false) ? 'is-active' : '' }}"><i class="bi bi-star-fill" aria-hidden="true"></i> Shortlisted</a>
    </div>

    <div class="sb-filter-row">
        <div class="sb-filter-field">
            <label class="form-label" for="filterName">Name</label>
            <input id="filterName" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" maxlength="100">
        </div>
        <div class="sb-filter-field">
            <label class="form-label" for="filterProgram">Course / program</label>
            <select id="filterProgram" name="program_id" class="form-select" onchange="this.form.requestSubmit()">
                <option value="">Any</option>
                @foreach($programs as $program)
                    <option value="{{ $program->id }}" @selected((string) ($filters['program_id'] ?? '') === (string) $program->id)>{{ $program->display_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="sb-filter-field sb-filter-field--sm">
            <label class="form-label" for="filterYear">Year level</label>
            <select id="filterYear" name="year_level" class="form-select" onchange="this.form.requestSubmit()">
                <option value="">Any</option>
                @foreach($yearLevels as $yearLevel)
                    <option value="{{ $yearLevel }}" @selected(($filters['year_level'] ?? '') === $yearLevel)>{{ $yearLevel }}</option>
                @endforeach
            </select>
        </div>
        <div class="sb-filter-field sb-filter-field--sm">
            <label class="form-label" for="filterSkill">Skill</label>
            <input id="filterSkill" type="search" name="skill" value="{{ $filters['skill'] ?? '' }}" class="form-control" maxlength="60" placeholder="e.g. Python">
        </div>
        <div class="sb-filter-field sb-filter-field--sm">
            <label class="form-label" for="filterCertificate">Certificate</label>
            <input id="filterCertificate" type="search" name="certificate" value="{{ $filters['certificate'] ?? '' }}" class="form-control" maxlength="100" placeholder="e.g. CCNA">
        </div>
        <div class="sb-filter-actions">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Search</button>
            <a href="{{ route('employer.students.index') }}" class="btn btn-outline-secondary">Clear</a>
        </div>
    </div>
</form>

@if($students->isEmpty())
    <div class="card"><div class="card-body text-center text-muted py-5">No students match these filters.</div></div>
@else
    <div class="sb-results">
        <div class="sb-card-grid">
            @foreach($students as $student)
                <article class="card sb-entity" aria-labelledby="student-{{ $student['student_id'] }}">
                    <div class="sb-person">
                        @if($student['photo_url'])
                            <img src="{{ $student['photo_url'] }}" alt="" width="46" height="46" class="sb-initials sb-initials--lg">
                        @else
                            <span class="sb-initials sb-initials--lg" aria-hidden="true"><i class="bi bi-person"></i></span>
                        @endif
                        <div class="sb-person-text">
                            <h2 id="student-{{ $student['student_id'] }}">{{ $student['name'] }}</h2>
                            <small>{{ collect([$student['program'], $student['year_level']])->filter()->implode(' · ') ?: 'Program not set' }}</small>
                        </div>
                    </div>
                    <div class="sb-entity-body">
                        @if($student['skills'])
                            <div class="sb-line">
                                <strong>Skills</strong>
                                <span class="sb-chips">
                                    @foreach($student['skills'] as $skill)<span>{{ $skill }}</span>@endforeach
                                    @if($student['skill_count'] > count($student['skills']))<span class="more">+{{ $student['skill_count'] - count($student['skills']) }}</span>@endif
                                </span>
                            </div>
                        @endif
                        @if($student['certificates'])
                            <div class="sb-line">
                                <strong>Certificates</strong>
                                <span class="sb-entity-text">{{ implode(' • ', $student['certificates']) }}@if($student['certificate_count'] > count($student['certificates'])) <span class="text-muted">+{{ $student['certificate_count'] - count($student['certificates']) }} more</span>@endif</span>
                            </div>
                        @endif
                    </div>
                    <div class="sb-entity-foot">
                        <a href="{{ route('employer.students.show', $student['student_id']) }}" class="btn btn-outline-primary btn-sm">View profile</a>
                        @include('employer.students.partials.shortlist-button')
                        <div>@include('employer.students.partials.invite-button')</div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
    @if($students->hasPages())<div class="sb-pages">{{ $students->links() }}</div>@endif
@endif

@include('employer.students.partials.invite-modal')
</div>{{-- /.bs --}}
@endsection
