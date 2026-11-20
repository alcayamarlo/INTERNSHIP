@extends('layouts.app')

@section('title', 'Eligibility Criteria')

@section('content')
<div class="sb-dash">
<x-page-header title="Eligibility Criteria" subtitle="Internship requirements per program and term." />

<form method="GET" action="{{ route($routePrefix.'.criteria.index') }}" class="card sb-filterbar" aria-label="Choose a term">
    <div class="sb-filter-row">
        <div class="sb-filter-field">
            <label class="form-label" for="criteriaSchoolYear">School year</label>
            <select id="criteriaSchoolYear" name="school_year" class="form-select" onchange="this.form.requestSubmit()">
                @foreach($schoolYears as $year)
                    <option value="{{ $year }}" @selected($year === $schoolYear)>{{ $year }}</option>
                @endforeach
            </select>
        </div>
        <div class="sb-filter-field">
            <label class="form-label" for="criteriaTerm">Term</label>
            <select id="criteriaTerm" name="term" class="form-select" onchange="this.form.requestSubmit()">
                @foreach($terms as $key => $label)
                    <option value="{{ $key }}" @selected($key === $term)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="sb-filter-actions"><button type="submit" class="btn btn-primary">Show</button></div>
    </div>
</form>

@if($schoolYear === $currentTerm['school_year'] && $term === $currentTerm['term'])
    <p class="sb-note mb-0">This is the current term, so these criteria apply to students right now.</p>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Program</th><th>Criteria for {{ $schoolYear }} · {{ $terms[$term] ?? $term }}</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
                @forelse($programs as $row)
                    <tr>
                        <td><span class="sb-row-title">{{ $row['program']->display_name }}</span></td>
                        <td>
                            @if($row['current'])
                                @include('coordinator.criteria.partials.summary', ['criterion' => $row['current']])
                            @else
                                <span class="text-muted">None set. The school-wide rules apply:
                                    {{ collect([$globalRules['require_fourth_year'] ? '4th Year standing' : null, $globalRules['require_major_subjects_passed'] ? 'major subjects passed' : null])->filter()->implode(', ') ?: 'no requirements' }}.</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @php $cur = $row['current']; @endphp
                            @if($row['program']->limitedToSecondSemester() && $term !== \App\Models\Program::ELIGIBLE_TERM)
                                <span class="text-muted small">Eligible in the 2nd semester only. Choose the 2nd semester to set criteria.</span>
                            @else
                            <a href="{{ route($routePrefix.'.criteria.show', [$row['program'], 'school_year' => $schoolYear, 'term' => $term]) }}" class="btn btn-sm btn-outline-primary"
                               data-open="criteria-modal" aria-haspopup="dialog" data-criteria-edit
                               data-action="{{ route($routePrefix.'.criteria.store', $row['program']) }}"
                               data-program-id="{{ $row['program']->id }}"
                               data-program-name="{{ $row['program']->display_name }}"
                               data-has-current="{{ $cur ? 1 : 0 }}"
                               data-fourth="{{ ($cur?->require_fourth_year ?? $globalRules['require_fourth_year']) ? 1 : 0 }}"
                               data-major="{{ ($cur?->require_major_subjects_passed ?? $globalRules['require_major_subjects_passed']) ? 1 : 0 }}"
                               data-hours="{{ $cur?->required_hours }}"
                               data-program-hours="{{ $row['program']->required_internship_hours ?: 'not set' }}"
                               data-docs="{{ implode(',', $cur?->requiredDocumentIds() ?? []) }}"
                               data-notes="{{ $cur?->notes }}">{{ $row['current'] ? 'Edit' : 'Set criteria' }}</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="sb-table-empty text-center text-muted">No programs are assigned to you yet. Ask an admin to assign your programs.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>

{{-- Set / edit criteria pop-up. One shared dialog, filled from the row's data-* attributes (script below). Same fields and the same save route as the old page;
     after a validation error the page reloads with this dialog open and the values you typed. --}}
@php
    $editingProgramId = old('_criteria_program');
    $reopenCriteria = $editingProgramId !== null;
    $oldDocs = array_map('intval', (array) old('required_document_ids', []));
    $reopenRow = $reopenCriteria ? $programs->first(fn ($r) => (int) $r['program']->id === (int) $editingProgramId) : null;
@endphp
<x-modal id="criteria-modal" title="Set criteria" subtitle="Internship requirements for {{ $schoolYear }} · {{ $terms[$term] ?? $term }}" icon="bi-ui-checks"
         :action="$editingProgramId ? route($routePrefix.'.criteria.store', $editingProgramId) : '#'" :reopen="$reopenCriteria" submit="Save criteria" size="large">
    <input type="hidden" name="_criteria_program" id="cm-program-id" value="{{ $editingProgramId }}">
    <input type="hidden" name="school_year" value="{{ $schoolYear }}">
    <input type="hidden" name="term" value="{{ $term }}">
    <div class="sb-modal__grid">
        <div>
            <p class="cm-program" id="cm-program-name">{{ $reopenRow ? $reopenRow['program']->display_name : '' }}</p>
            <p class="form-text mb-0" id="cm-none-note" hidden>No criteria yet for this term, so the school-wide rules apply. Saving here replaces them for this program.</p>
        </div>
        <div>
            <div class="form-check">
                <input type="hidden" name="require_fourth_year" value="0">
                <input class="form-check-input" type="checkbox" name="require_fourth_year" value="1" id="cm-fourth" @checked($reopenCriteria && old('require_fourth_year'))>
                <label class="form-check-label" for="cm-fourth">Must be in 4th Year</label>
            </div>
            <div class="form-check mt-2">
                <input type="hidden" name="require_major_subjects_passed" value="0">
                <input class="form-check-input" type="checkbox" name="require_major_subjects_passed" value="1" id="cm-major" @checked($reopenCriteria && old('require_major_subjects_passed'))>
                <label class="form-check-label" for="cm-major">All major subjects (prerequisites) passed, confirmed by the coordinator</label>
            </div>
        </div>
        <div class="sm-3">
            <label class="form-label" for="cm-hours">Required OJT hours</label>
            <input id="cm-hours" type="number" min="1" max="2000" name="required_hours" class="form-control @if($reopenCriteria) @error('required_hours') is-invalid @enderror @endif" value="{{ $reopenCriteria ? old('required_hours') : '' }}" aria-describedby="cm-hours-hint">
            @if($reopenCriteria) @error('required_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
            <div class="sb-modal__hint" id="cm-hours-hint">Used for new placements this term. Leave blank to use the program's hours.</div>
        </div>
        <fieldset>
            <legend class="form-label">Required documents before applying</legend>
            <div class="cm-docs">
                @forelse($documents as $document)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="required_document_ids[]" value="{{ $document->id }}" id="cm-doc{{ $document->id }}" data-cm-doc @checked($reopenCriteria && in_array($document->id, $oldDocs, true))>
                        <label class="form-check-label" for="cm-doc{{ $document->id }}">{{ $document->name }}</label>
                    </div>
                @empty
                    <p class="small text-muted mb-0">No student documents are set up yet.</p>
                @endforelse
            </div>
            @if($reopenCriteria) @error('required_document_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror @endif
        </fieldset>
        <div>
            <label class="form-label" for="cm-notes">Notes (optional)</label>
            <textarea id="cm-notes" name="notes" rows="2" maxlength="500" class="form-control @if($reopenCriteria) @error('notes') is-invalid @enderror @endif">{{ $reopenCriteria ? old('notes') : '' }}</textarea>
            @if($reopenCriteria) @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
        </div>
    </div>
</x-modal>

<script>
    (function () {
        const dialog = document.getElementById('criteria-modal');
        if (!dialog) return;
        const form = dialog.querySelector('form');
        const byId = (id) => document.getElementById(id);
        // Capture phase: the fields are filled before the dialog opens.
        document.addEventListener('click', (event) => {
            const btn = event.target.closest && event.target.closest('[data-criteria-edit]');
            if (!btn) return;
            const d = btn.dataset;
            form.action = d.action;
            byId('cm-program-id').value = d.programId;
            byId('cm-program-name').textContent = d.programName;
            byId('cm-none-note').hidden = d.hasCurrent === '1';
            dialog.querySelector('.sb-modal__title').textContent = d.hasCurrent === '1' ? 'Edit criteria' : 'Set criteria';
            byId('cm-fourth').checked = d.fourth === '1';
            byId('cm-major').checked = d.major === '1';
            byId('cm-hours').value = d.hours || '';
            byId('cm-hours-hint').textContent = 'Used for new placements this term. Leave blank to use the program\'s hours (' + d.programHours + ').';
            const ids = (d.docs || '').split(',').filter(Boolean);
            dialog.querySelectorAll('[data-cm-doc]').forEach((box) => { box.checked = ids.includes(box.value); });
            byId('cm-notes').value = d.notes || '';
            dialog.querySelectorAll('.is-invalid').forEach((n) => n.classList.remove('is-invalid'));
            dialog.querySelectorAll('.invalid-feedback').forEach((n) => n.remove());
        }, true);
    })();
</script>
@endsection
