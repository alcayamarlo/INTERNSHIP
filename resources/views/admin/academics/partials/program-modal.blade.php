{{-- Add program pop-up (shared <x-modal> shell). Same fields, names, old() values and errors as the old create page; posts to the same store route. --}}
@php
    $editingProgramId = old('_edit_program');
    $reopenProgramModal = $editingProgramId === null && (old('code') !== null || old('required_internship_hours') !== null || $errors->hasAny(['code', 'required_internship_hours', 'majors']));
    $reopenEditModal = $editingProgramId !== null;
@endphp
@if($canManagePrograms ?? false)
<x-modal id="program-modal" title="Add program" subtitle="Set the program details, available majors and required internship hours." icon="bi-mortarboard"
         :action="route($academicsRoute.'programs.store')" :reopen="$reopenProgramModal" submit="Save program">
    <div class="sb-modal__grid">
        <div class="sm-4">
            <label class="form-label" for="pm-name">Program name <span class="text-danger" aria-hidden="true">*</span></label>
            <input id="pm-name" name="name" value="{{ old('name') }}" class="form-control @if($reopenProgramModal) @error('name') is-invalid @enderror @endif" required>
            @if($reopenProgramModal) @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
        </div>
        <div class="sm-2">
            <label class="form-label" for="pm-code">Program code <span class="text-danger" aria-hidden="true">*</span></label>
            <input id="pm-code" name="code" value="{{ old('code') }}" class="form-control @error('code') is-invalid @enderror" required>
            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="sm-3">
            <label class="form-label" for="pm-hours">Required internship hours <span class="text-danger" aria-hidden="true">*</span></label>
            <input id="pm-hours" name="required_internship_hours" type="number" min="1" max="10000" value="{{ old('required_internship_hours') }}" class="form-control @error('required_internship_hours') is-invalid @enderror" required>
            @error('required_internship_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="sm-3 sm-end">
            <div class="form-check sb-modal__check">
                <input type="hidden" name="required_hours_confirmed" value="0">
                <input class="form-check-input" type="checkbox" id="pm-confirmed" name="required_hours_confirmed" value="1" @checked(old('required_hours_confirmed'))>
                <label class="form-check-label" for="pm-confirmed">Hours confirmed by the school</label>
            </div>
        </div>
        <p class="form-text mb-0">Each department has one coordinator. Assign it from Users when creating or editing the coordinator's account.</p>
        <div>
            <label class="form-label" for="pm-majors">Majors</label>
            <textarea id="pm-majors" name="majors" rows="4" class="form-control @error('majors') is-invalid @enderror" aria-describedby="pm-major-help">{{ old('majors') }}</textarea>
            <div id="pm-major-help" class="sb-modal__hint">Enter one major per line. Leave blank when the program has no majors.</div>
            @error('majors')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</x-modal>
@endif

{{-- Edit program pop-up: one shared dialog, filled from the Edit button's data-* attributes (script on the Academic settings page).
     Posts to the same update route as the old edit page; after a validation error the page reloads with this dialog open. --}}
<x-modal id="program-edit-modal" title="Edit program" subtitle="Update the program details, available majors and required internship hours." icon="bi-pencil"
         :action="$editingProgramId ? route($academicsRoute.'programs.update', $editingProgramId) : '#'" method="PUT" :reopen="$reopenEditModal" submit="Save changes">
    <input type="hidden" id="pe-id" name="_edit_program" value="{{ $editingProgramId }}">
    <div class="sb-modal__grid">
        <div class="sm-4">
            <label class="form-label" for="pe-name">Program name <span class="text-danger" aria-hidden="true">*</span></label>
            <input id="pe-name" name="name" value="{{ $reopenEditModal ? old('name') : '' }}" class="form-control @if($reopenEditModal) @error('name') is-invalid @enderror @endif" required>
            @if($reopenEditModal) @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
        </div>
        <div class="sm-2">
            <label class="form-label" for="pe-code">Program code <span class="text-danger" aria-hidden="true">*</span></label>
            <input id="pe-code" name="code" value="{{ $reopenEditModal ? old('code') : '' }}" class="form-control @if($reopenEditModal) @error('code') is-invalid @enderror @endif" required>
            @if($reopenEditModal) @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
        </div>
        <div class="sm-3">
            <label class="form-label" for="pe-hours">Required internship hours <span class="text-danger" aria-hidden="true">*</span></label>
            <input id="pe-hours" name="required_internship_hours" type="number" min="1" max="10000" value="{{ $reopenEditModal ? old('required_internship_hours') : '' }}" class="form-control @if($reopenEditModal) @error('required_internship_hours') is-invalid @enderror @endif" required>
            @if($reopenEditModal) @error('required_internship_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
        </div>
        <div class="sm-3 sm-end">
            <div class="form-check sb-modal__check">
                <input type="hidden" name="required_hours_confirmed" value="0">
                <input class="form-check-input" type="checkbox" id="pe-confirmed" name="required_hours_confirmed" value="1" @checked($reopenEditModal && old('required_hours_confirmed'))>
                <label class="form-check-label" for="pe-confirmed">Hours confirmed by the school</label>
            </div>
        </div>
        <div>
            <span class="form-label d-block">Assigned coordinator</span>
            <p class="mb-1" id="pe-coordinator">&nbsp;</p>
            <div class="sb-modal__hint">Each department has one coordinator. An admin assigns it from Users when creating or editing the coordinator's account.</div>
        </div>
        <div>
            <label class="form-label" for="pe-majors">Majors</label>
            <textarea id="pe-majors" name="majors" rows="4" class="form-control @if($reopenEditModal) @error('majors') is-invalid @enderror @endif" aria-describedby="pe-major-help">{{ $reopenEditModal ? old('majors') : '' }}</textarea>
            <div id="pe-major-help" class="sb-modal__hint">Enter one major per line. Leave blank when the program has no majors.</div>
            @if($reopenEditModal) @error('majors')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
        </div>
    </div>
</x-modal>
