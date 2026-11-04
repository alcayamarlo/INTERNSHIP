@extends('layouts.app')

@section('title', 'Criteria · '.$program->code)

@section('content')
<x-page-header :title="$program->display_name" :subtitle="'Internship criteria for '.$schoolYear.' · '.($terms[$term] ?? $term)">
    <a href="{{ route($routePrefix.'.criteria.index', ['school_year' => $schoolYear, 'term' => $term]) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> All programs</a>
</x-page-header>

<div class="row g-4">
    <div class="col-lg-7">
        <form method="POST" action="{{ route($routePrefix.'.criteria.store', $program) }}" class="card" novalidate>
            @csrf
            <input type="hidden" name="school_year" value="{{ $schoolYear }}">
            <input type="hidden" name="term" value="{{ $term }}">
            <div class="card-header"><h2>{{ $current ? 'Edit criteria (saves version '.($current->version + 1).')' : 'Set criteria' }}</h2></div>
            <div class="card-body">
                @unless($current)
                    <p class="small text-muted">No criteria yet for this term, so the school-wide rules apply. Saving here replaces them for {{ $program->code }}.</p>
                @endunless

                <div class="form-check mb-2">
                    <input type="hidden" name="require_fourth_year" value="0">
                    <input class="form-check-input" type="checkbox" name="require_fourth_year" value="1" id="requireFourthYear" @checked(old('require_fourth_year', $current?->require_fourth_year ?? $globalRules['require_fourth_year']))>
                    <label class="form-check-label" for="requireFourthYear">Must be in 4th Year</label>
                </div>
                <div class="form-check mb-3">
                    <input type="hidden" name="require_major_subjects_passed" value="0">
                    <input class="form-check-input" type="checkbox" name="require_major_subjects_passed" value="1" id="requireMajorSubjects" @checked(old('require_major_subjects_passed', $current?->require_major_subjects_passed ?? $globalRules['require_major_subjects_passed']))>
                    <label class="form-check-label" for="requireMajorSubjects">All major subjects (prerequisites) passed, confirmed by the coordinator</label>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="maxGwa">Minimum grade (GWA)</label>
                        <input id="maxGwa" type="number" step="0.01" min="1" max="5" name="max_gwa" class="form-control @error('max_gwa') is-invalid @enderror" value="{{ old('max_gwa', $current?->max_gwa) }}" placeholder="e.g. 2.50" aria-describedby="maxGwaHint">
                        <small class="form-text text-muted" id="maxGwaHint">Students need this GWA or better (lower is better). Leave blank for no grade requirement.</small>
                        @error('max_gwa')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="requiredHours">Required OJT hours</label>
                        <input id="requiredHours" type="number" min="1" max="2000" name="required_hours" class="form-control @error('required_hours') is-invalid @enderror" value="{{ old('required_hours', $current?->required_hours) }}" placeholder="{{ $program->required_internship_hours ?: 'e.g. 486' }}" aria-describedby="requiredHoursHint">
                        <small class="form-text text-muted" id="requiredHoursHint">Used for new placements this term. Blank uses the program's hours ({{ $program->required_internship_hours ?: 'not set' }}).</small>
                        @error('required_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <fieldset class="mt-3">
                    <legend class="form-label fs-6">Required documents before applying</legend>
                    @forelse($documents as $document)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="required_document_ids[]" value="{{ $document->id }}" id="doc{{ $document->id }}"
                                @checked(in_array($document->id, array_map('intval', old('required_document_ids', $current?->requiredDocumentIds() ?? [])), true))>
                            <label class="form-check-label" for="doc{{ $document->id }}">{{ $document->name }}</label>
                        </div>
                    @empty
                        <p class="small text-muted mb-0">No student documents are set up yet.</p>
                    @endforelse
                    @error('required_document_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </fieldset>

                <div class="mt-3">
                    <label class="form-label" for="criteriaNotes">Notes (optional)</label>
                    <textarea id="criteriaNotes" name="notes" rows="2" maxlength="500" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $current?->notes) }}</textarea>
                    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="card-footer text-end"><button type="submit" class="btn btn-primary">Save criteria</button></div>
        </form>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h2>Version history</h2></div>
            <ul class="list-group list-group-flush">
                @forelse($versions as $version)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <strong>Version {{ $version->version }}</strong>
                            @if($loop->first)<span class="badge text-bg-success">Current</span>@endif
                        </div>
                        <small class="d-block">@include('coordinator.criteria.partials.summary', ['criterion' => $version])</small>
                        <small class="text-muted d-block">{{ $version->created_at->format('M d, Y h:i A') }} · {{ $version->createdBy?->name ?? 'Unknown' }}</small>
                        @if($version->notes)<small class="d-block mt-1">{{ $version->notes }}</small>@endif
                    </li>
                @empty
                    <li class="list-group-item text-muted">No versions yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
