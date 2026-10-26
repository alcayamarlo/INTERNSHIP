@extends('layouts.app')

@section('title', 'My Applications')

@section('content')
<x-breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('student.dashboard')], ['label' => 'Internships', 'url' => route('student.internships.index')], ['label' => 'My Applications']]" />

<x-page-header title="My Applications" subtitle="Track your internship application status">
    <a href="{{ route('student.internships.index') }}" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Browse Internships</a>
</x-page-header>

@include('student.internships.partials.tabs', ['active' => 'applications'])

<form method="GET" class="sb-toolbar" aria-label="Filter applications">
    <div class="sb-field">
        <label class="form-label" for="filter-status">Status</label>
        <select id="filter-status" name="status" class="form-select">
            <option value="">All</option>
            @foreach(\App\Enums\ApplicationStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="sb-field"><label class="form-label" for="filter-title">Job title</label><input id="filter-title" name="job_title" class="form-control" value="{{ request('job_title') }}"></div>
    <div class="sb-field"><label class="form-label" for="filter-company">Company</label><input id="filter-company" name="company" class="form-control" value="{{ request('company') }}"></div>
    <div class="sb-field"><label class="form-label" for="filter-location">Location</label><input id="filter-location" name="location" class="form-control" value="{{ request('location') }}"></div>
    <div class="sb-field"><label class="form-label" for="filter-from">Applied from</label><input id="filter-from" type="date" name="applied_from" class="form-control" value="{{ request('applied_from') }}"></div>
    <div class="sb-field"><label class="form-label" for="filter-to">Applied to</label><input id="filter-to" type="date" name="applied_to" class="form-control" value="{{ request('applied_to') }}"></div>
    <div class="sb-toolbar-actions">
        <button class="btn btn-primary"><i class="bi bi-funnel" aria-hidden="true"></i> Apply filters</button>
        <a href="{{ route('student.applications.index') }}" class="btn btn-outline-secondary">Clear</a>
    </div>
</form>

<div class="card">
    @if($applications->count())
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr><th scope="col">Internship</th><th scope="col">Company</th><th scope="col">Location</th><th scope="col">Match</th><th scope="col">Status</th><th scope="col">Applied</th><th scope="col">Interview</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr>
            </thead>
            <tbody>
                @foreach($applications as $app)
                    <tr>
                        <td>
                            <a href="{{ route('student.internships.show', $app->internship) }}" class="fw-semibold">{{ $app->internship->title }}</a>
                        </td>
                        <td>{{ $app->internship->employer->company_name }}</td>
                        <td>{{ $app->internship->location ?? '—' }}</td>
                        <td><span class="sb-tag sb-tag--brand">{{ $app->match_percentage }}%</span></td>
                        <td><x-status-badge :status="$app->status->value" :label="$app->status->label()" /></td>
                        <td>{{ $app->applied_at?->format('M d, Y') }}</td>
                        <td>{{ $app->interview_at?->format('M d, Y h:i A') ?? '—' }}</td>
                        <td class="text-nowrap">
                            <span class="sb-row-actions">
                                <a href="{{ route('student.applications.show', $app) }}" class="btn btn-sm btn-outline-primary" title="View" aria-label="View application for {{ $app->internship->title }}"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                @if(in_array($app->status, [\App\Enums\ApplicationStatus::Applied, \App\Enums\ApplicationStatus::Submitted], true))
                                    <a href="{{ route('student.applications.edit', $app) }}" class="btn btn-sm btn-outline-secondary" title="Edit" aria-label="Edit application for {{ $app->internship->title }}"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                    <form method="POST" action="{{ route('student.applications.destroy', $app) }}" class="d-inline" data-confirm="Delete this application? This cannot be undone." data-confirm-title="Delete application">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete application for {{ $app->internship->title }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                    </form>
                                @endif
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
        <x-empty-state icon="bi-send" title="No applications match your filters" message="Apply to an internship and it will show up here, or clear the filters to see everything.">
            <a href="{{ route('student.internships.index') }}" class="btn btn-primary">Browse internships</a>
        </x-empty-state>
    @endif
    @if($applications->hasPages())<div class="card-footer">{{ $applications->links() }}</div>@endif
</div>

@endsection
