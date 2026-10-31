@extends('layouts.app')

@section('title', 'Browse Students')

@section('content')
<x-page-header title="Browse Students" subtitle="Find students who allow companies to see their profile, and invite them to apply.">
    <a href="{{ route('employer.invitations.index') }}" class="btn btn-outline-secondary"><i class="bi bi-envelope-paper" aria-hidden="true"></i> Sent invitations</a>
</x-page-header>

<form method="GET" action="{{ route('employer.students.index') }}" class="card mb-4" role="search" aria-label="Filter students">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="filterName">Name</label>
                <input id="filterName" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" maxlength="100">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="filterProgram">Course / program</label>
                <select id="filterProgram" name="program_id" class="form-select">
                    <option value="">Any</option>
                    @foreach($programs as $program)
                        <option value="{{ $program->id }}" @selected((string) ($filters['program_id'] ?? '') === (string) $program->id)>{{ $program->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="filterYear">Year level</label>
                <select id="filterYear" name="year_level" class="form-select">
                    <option value="">Any</option>
                    @foreach($yearLevels as $yearLevel)
                        <option value="{{ $yearLevel }}" @selected(($filters['year_level'] ?? '') === $yearLevel)>{{ $yearLevel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="filterSkill">Skill</label>
                <input id="filterSkill" type="search" name="skill" value="{{ $filters['skill'] ?? '' }}" class="form-control" maxlength="60" placeholder="e.g. Python">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="filterCertificate">Certificate</label>
                <input id="filterCertificate" type="search" name="certificate" value="{{ $filters['certificate'] ?? '' }}" class="form-control" maxlength="100" placeholder="e.g. CCNA">
            </div>
        </div>
        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Search</button>
            <a href="{{ route('employer.students.index') }}" class="btn btn-outline-secondary">Clear</a>
        </div>
    </div>
</form>

@if($students->isEmpty())
    <div class="card"><div class="card-body text-center text-muted py-5">No students match these filters.</div></div>
@else
    <div class="row g-3">
        @foreach($students as $student)
            <div class="col-md-6 col-xl-4">
                <article class="card h-100" aria-labelledby="student-{{ $student['student_id'] }}">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            @if($student['photo_url'])
                                <img src="{{ $student['photo_url'] }}" alt="" width="56" height="56" class="rounded-circle object-fit-cover">
                            @else
                                <span class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" style="width:56px;height:56px" aria-hidden="true"><i class="bi bi-person fs-4"></i></span>
                            @endif
                            <div>
                                <h2 class="h6 mb-0" id="student-{{ $student['student_id'] }}">{{ $student['name'] }}</h2>
                                <small class="text-muted">{{ collect([$student['program'], $student['year_level']])->filter()->implode(' · ') ?: 'Program not set' }}</small>
                            </div>
                        </div>
                        @if($student['skills'])
                            <p class="small mb-2"><strong>Skills:</strong> {{ implode(' • ', $student['skills']) }}@if($student['skill_count'] > count($student['skills'])) <span class="text-muted">+{{ $student['skill_count'] - count($student['skills']) }} more</span>@endif</p>
                        @endif
                        @if($student['certificates'])
                            <p class="small mb-2"><strong>Certificates:</strong> {{ implode(' • ', $student['certificates']) }}@if($student['certificate_count'] > count($student['certificates'])) <span class="text-muted">+{{ $student['certificate_count'] - count($student['certificates']) }} more</span>@endif</p>
                        @endif
                        <div class="mt-auto pt-2 d-flex flex-wrap gap-2 align-items-start">
                            <a href="{{ route('employer.students.show', $student['student_id']) }}" class="btn btn-outline-primary btn-sm">View profile</a>
                            <div>@include('employer.students.partials.invite-button')</div>
                        </div>
                    </div>
                </article>
            </div>
        @endforeach
    </div>
    @if($students->hasPages())<div class="mt-4">{{ $students->links() }}</div>@endif
@endif

@include('employer.students.partials.invite-modal')
@endsection
