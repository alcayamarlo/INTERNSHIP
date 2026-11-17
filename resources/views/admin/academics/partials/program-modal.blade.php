{{-- Add program pop-up (shared <x-modal> shell). Same fields, names, old() values and errors as the old create page; posts to the same store route. --}}
@php
    $reopenProgramModal = old('code') !== null || old('required_internship_hours') !== null || $errors->hasAny(['code', 'required_internship_hours', 'majors']);
@endphp
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
