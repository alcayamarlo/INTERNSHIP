@extends('layouts.app')

@section('title', 'Applications')

@section('content')
@php($monitor = \App\Http\Controllers\Coordinator\ApplicationMonitorController::class)
<x-page-header title="Applications and placements" subtitle="Where your students are in the internship process.">
    @if($pendingValidation)
        <a href="{{ route('coordinator.placements.index', ['status' => 'pending_validation']) }}" class="btn btn-warning"><i class="bi bi-shield-exclamation" aria-hidden="true"></i> {{ $pendingValidation }} placement{{ $pendingValidation === 1 ? '' : 's' }} to validate</a>
    @endif
</x-page-header>

<div class="row g-3 mb-4" role="list" aria-label="Applications by stage">
    @foreach($stages as $key => $label)
        <div class="col-6 col-md-3 col-xl" role="listitem">
            <a href="{{ route('coordinator.applications.index', ['stage' => $key] + array_filter(['program_id' => $filters['program_id'] ?? null])) }}" class="card text-decoration-none h-100 {{ ($filters['stage'] ?? null) === $key ? 'border-primary' : '' }}" data-stage="{{ $key }}">
                <div class="card-body">
                    <span class="small text-muted d-block">{{ $label }}</span>
                    <strong class="fs-4">{{ $counts[$key] }}</strong>
                </div>
            </a>
        </div>
    @endforeach
</div>

<form method="GET" action="{{ route('coordinator.applications.index') }}" class="card mb-4" role="search" aria-label="Filter applications">
    <div class="card-body row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label" for="monitorSearch">Student name</label>
            <input id="monitorSearch" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" maxlength="100">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="monitorStage">Stage</label>
            <select id="monitorStage" name="stage" class="form-select">
                <option value="">All</option>
                @foreach($stages as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['stage'] ?? null) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="monitorProgram">Program</label>
            <select id="monitorProgram" name="program_id" class="form-select">
                <option value="">All my programs</option>
                @foreach($programs as $program)
                    <option value="{{ $program->id }}" @selected((string) ($filters['program_id'] ?? '') === (string) $program->id)>{{ $program->code }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Filter</button></div>
    </div>
</form>

<div class="card mb-4">
    <div class="card-header"><h2>Applications</h2></div>
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

<div class="card" id="unplaced">
    <div class="card-header"><h2>Students not placed yet ({{ $unplaced->total() }})</h2></div>
    <div class="table-responsive">
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
                    <tr><td colspan="4" class="text-center text-muted py-4">Every student is placed.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($unplaced->hasPages())<div class="card-footer">{{ $unplaced->links() }}</div>@endif
</div>
@endsection
