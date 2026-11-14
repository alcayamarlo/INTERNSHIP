{{-- Posting form fields, shared by the create/edit page (form.blade.php) and the
     "Post New Internship" pop-up on the list page (index.blade.php). --}}
<div class="pf">
<div class="pf-main">
<div class="row g-3 pf-grid">
    <div class="col-md-8 pf-title">
        <label class="form-label" for="internship-title">Title <span class="text-danger" aria-hidden="true">*</span></label>
        <input type="text" id="internship-title" class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title', $internship->title) }}" required>
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 pf-status">
        <label class="form-label" for="internship-status">Status <span class="text-danger" aria-hidden="true">*</span></label>
        <select id="internship-status" class="form-select @error('status') is-invalid @enderror" name="status" required>
            <option value="open" {{ old('status', $internship->status) === 'open' ? 'selected' : '' }}>Open</option>
            <option value="closed" {{ old('status', $internship->status) === 'closed' ? 'selected' : '' }}>Closed</option>
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 pf-setup">
        <label class="form-label" for="internship-work-setup">Work Setup <span class="text-danger" aria-hidden="true">*</span></label>
        <select id="internship-work-setup" class="form-select @error('work_setup') is-invalid @enderror" name="work_setup" required>
            @foreach($workSetups as $setup)
                <option value="{{ $setup->value }}" {{ old('work_setup', $internship->work_setup?->value) === $setup->value ? 'selected' : '' }}>{{ $setup->label() }}</option>
            @endforeach
        </select>
        @error('work_setup')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 pf-programs">
        @php $selectedPrograms = collect(old('program_ids', $internship->exists ? $internship->programs->pluck('id')->all() : []))->map(fn ($id) => (int) $id); @endphp
        <fieldset>
            <legend class="form-label fs-6">Open to programs</legend>
            <div class="d-flex flex-wrap gap-3">
                @foreach($programs as $program)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="program_ids[]" value="{{ $program->id }}" id="program-{{ $program->id }}" @checked($selectedPrograms->contains($program->id))>
                        <label class="form-check-label" for="program-{{ $program->id }}">{{ $program->display_name }}</label>
                    </div>
                @endforeach
            </div>
            <div class="form-text">Students of the selected programs see this internship first. Leave all unticked to open it to every program. Students can still find it by searching.</div>
            @error('program_ids.*')<div class="text-danger small">{{ $message }}</div>@enderror
        </fieldset>
    </div>
    <div class="col-md-4 pf-location">
        <label class="form-label" for="internship-location">Location</label>
        <input type="text" id="internship-location" class="form-control @error('location') is-invalid @enderror" name="location" value="{{ old('location', $internship->location) }}">
        @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 pf-duration">
        <label class="form-label" for="internship-duration">Duration</label>
        <input type="text" id="internship-duration" class="form-control @error('duration') is-invalid @enderror" name="duration" value="{{ old('duration', $internship->duration) }}" placeholder="e.g. 3 months">
        @error('duration')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 pf-deadline">
        <label class="form-label" for="expires_at">Application deadline</label>
        <input type="date" id="expires_at" class="form-control @error('expires_at') is-invalid @enderror" name="expires_at" value="{{ old('expires_at', $internship->expires_at?->format('Y-m-d')) }}" @unless($internship->expires_at?->isPast()) min="{{ now()->format('Y-m-d') }}" @endunless aria-describedby="expires_at_help">
        <div id="expires_at_help" class="form-text">Optional. Applications close at the end of this day, and students who match get a reminder a few days before.</div>
        @error('expires_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    {{-- New postings are posted without an allowance; editing keeps the field so older values can be changed or cleared. --}}
    @if($internship->exists)
        <div class="col-md-4 pf-allowance">
            <label class="form-label" for="internship-allowance">Allowance (₱)</label>
            <input type="number" id="internship-allowance" step="0.01" min="0" class="form-control @error('allowance') is-invalid @enderror" name="allowance" value="{{ old('allowance', $internship->allowance) }}">
            @error('allowance')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @endif
    <div class="col-12 pf-desc">
        <label class="form-label" for="internship-description">Description <span class="text-danger" aria-hidden="true">*</span></label>
        <textarea id="internship-description" class="form-control @error('description') is-invalid @enderror" name="description" rows="4" required>{{ old('description', $internship->description) }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 pf-resp">
        <label class="form-label" for="internship-responsibilities">Responsibilities</label>
        <textarea id="internship-responsibilities" class="form-control" name="responsibilities" rows="4">{{ old('responsibilities', $internship->responsibilities) }}</textarea>
    </div>
    <div class="col-md-6 pf-req">
        <label class="form-label" for="internship-requirements">General Requirements</label>
        <textarea id="internship-requirements" class="form-control" name="requirements" rows="4">{{ old('requirements', $internship->requirements) }}</textarea>
    </div>
</div>
</div>{{-- /.pf-main --}}

<div class="pf-side">
<hr class="my-4">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h6 mb-0">Competency Requirements</h2>
    <button type="button" class="btn btn-sm btn-outline-primary" data-add-requirement><i class="bi bi-plus-lg" aria-hidden="true"></i> Add Requirement</button>
</div>
<div data-requirements>
    @php
        $existingReqs = old('requirement_names')
            ? collect(old('requirement_names'))->map(fn($n, $i) => ['name' => $n, 'level' => old('requirement_levels')[$i] ?? 'intermediate'])
            : ($internship->exists ? $internship->requirementsList->map(fn($r) => ['name' => $r->requirement_name, 'level' => $r->required_level instanceof \App\Enums\ProficiencyLevel ? $r->required_level->value : $r->required_level]) : collect([['name' => '', 'level' => 'intermediate']]));
    @endphp
    @foreach($existingReqs as $req)
        <div class="row g-2 mb-2 requirement-row">
            <div class="col-md-7">
                <input type="text" class="form-control" name="requirement_names[]" value="{{ $req['name'] }}" placeholder="Skill / competency name" aria-label="Skill or competency name">
            </div>
            <div class="col-md-4">
                <select class="form-select" name="requirement_levels[]" aria-label="Required level">
                    @foreach($levels as $level)
                        <option value="{{ $level->value }}" {{ ($req['level'] ?? '') === $level->value ? 'selected' : '' }}>{{ $level->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-danger w-100 remove-requirement" aria-label="Remove requirement"><i class="bi bi-x" aria-hidden="true"></i></button>
            </div>
        </div>
    @endforeach
</div>
@error('requirement_names.*')<div class="text-danger small">{{ $message }}</div>@enderror

<hr class="my-4">

<h2 class="h6">Applicant requirements</h2>
<div class="form-check mb-3">
    <input type="hidden" name="requires_character_reference" value="0">
    <input class="form-check-input" type="checkbox" name="requires_character_reference" value="1" id="requiresCharacterReference" @checked(old('requires_character_reference', $internship->requires_character_reference))>
    <label class="form-check-label" for="requiresCharacterReference">Require at least one character reference before a student can apply</label>
</div>
@if($onRequestDocuments->isNotEmpty())
    <fieldset>
        <legend class="form-label fs-6 mb-1">Request these IDs from applicants</legend>
        <p class="small text-muted mb-2">Students upload a file of each ID after they apply. Only ID files are collected, never ID numbers.</p>
        @php($requestedDocuments = old('requested_documents', $internship->requested_documents ?? []))
        @foreach($onRequestDocuments as $document)
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="requested_documents[]" value="{{ $document->code }}" id="requested-{{ $document->code }}" @checked(in_array($document->code, $requestedDocuments, true))>
                <label class="form-check-label" for="requested-{{ $document->code }}">{{ $document->name }}</label>
            </div>
        @endforeach
        @error('requested_documents.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </fieldset>
@endif
</div>{{-- /.pf-side --}}
</div>{{-- /.pf --}}

<template data-requirement-template>
    <div class="row g-2 mb-2 requirement-row">
        <div class="col-md-7">
            <input type="text" class="form-control" name="requirement_names[]" placeholder="Skill / competency name" aria-label="Skill or competency name">
        </div>
        <div class="col-md-4">
            <select class="form-select" name="requirement_levels[]" aria-label="Required level">
                @foreach($levels as $level)<option value="{{ $level->value }}">{{ $level->label() }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-outline-danger w-100 remove-requirement" aria-label="Remove requirement"><i class="bi bi-x" aria-hidden="true"></i></button>
        </div>
    </div>
</template>

@once
    @push('scripts')
    <script>
        // Competency requirement rows: add from the template, remove all but the last one.
        document.addEventListener('click', (event) => {
            const add = event.target.closest('[data-add-requirement]');
            const form = (add || event.target).closest('form');
            if (!form) return;
            if (add) {
                const row = form.querySelector('[data-requirement-template]').content.cloneNode(true);
                form.querySelector('[data-requirements]').append(row);
                form.querySelector('[data-requirements] .requirement-row:last-child input')?.focus();
                return;
            }
            const remove = event.target.closest('.remove-requirement');
            if (remove && form.querySelectorAll('[data-requirements] .requirement-row').length > 1) {
                remove.closest('.requirement-row').remove();
            }
        });
    </script>
    @endpush
@endonce
