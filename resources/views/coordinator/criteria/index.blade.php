@extends('layouts.app')

@section('title', 'Eligibility Criteria')

@section('content')
<x-page-header title="Eligibility Criteria" subtitle="Internship requirements per program and term. Each save keeps the earlier versions as history." />

<form method="GET" action="{{ route($routePrefix.'.criteria.index') }}" class="card mb-4" aria-label="Choose a term">
    <div class="card-body row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label" for="criteriaSchoolYear">School year</label>
            <select id="criteriaSchoolYear" name="school_year" class="form-select" onchange="this.form.requestSubmit()">
                @foreach($schoolYears as $year)
                    <option value="{{ $year }}" @selected($year === $schoolYear)>{{ $year }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="criteriaTerm">Term</label>
            <select id="criteriaTerm" name="term" class="form-select" onchange="this.form.requestSubmit()">
                @foreach($terms as $key => $label)
                    <option value="{{ $key }}" @selected($key === $term)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4"><button type="submit" class="btn btn-primary">Show</button></div>
    </div>
</form>

@if($schoolYear === $currentTerm['school_year'] && $term === $currentTerm['term'])
    <p class="small text-muted">This is the current term, so these criteria apply to students right now.</p>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Program</th><th>Criteria for {{ $schoolYear }} · {{ $terms[$term] ?? $term }}</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
                @forelse($programs as $row)
                    <tr>
                        <td>{{ $row['program']->display_name }}</td>
                        <td>
                            @if($row['current'])
                                @include('coordinator.criteria.partials.summary', ['criterion' => $row['current']])
                                <small class="text-muted d-block">Version {{ $row['current']->version }}</small>
                            @else
                                <span class="text-muted">None set. The school-wide rules apply:
                                    {{ collect([$globalRules['require_fourth_year'] ? '4th Year standing' : null, $globalRules['require_major_subjects_passed'] ? 'major subjects passed' : null])->filter()->implode(', ') ?: 'no requirements' }}.</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route($routePrefix.'.criteria.show', [$row['program'], 'school_year' => $schoolYear, 'term' => $term]) }}" class="btn btn-sm btn-outline-primary">{{ $row['current'] ? 'Edit' : 'Set criteria' }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-4">No programs are assigned to you yet. Ask an admin to assign your programs.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
