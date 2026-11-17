{{-- Posting form fields, shared by the create/edit page (form.blade.php) and the
     "Post New Internship" pop-up on the list page (index.blade.php). --}}
@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/employer-internship-form.css') }}?v={{ @filemtime(public_path('css/employer-internship-form.css')) }}">
    @endpush
@endonce
<div class="sb-form ei-form">
<div class="sb-form-col">

{{-- 1. Basics --}}
<section class="card sb-form-section" aria-labelledby="internshipDetailsTitle">
    <div class="ei-head">
        <span class="ei-head-icon" aria-hidden="true"><i class="bi bi-briefcase"></i></span>
        <div class="ei-head-text">
            <h2 class="ei-title" id="internshipDetailsTitle">Internship basics</h2>
            <p class="ei-sub">Give the role a clear title and say who it is open to.</p>
        </div>
    </div>
    <div class="ei-body">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label" for="internship-title">Title <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" id="internship-title" class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title', $internship->title) }}" placeholder="e.g. Junior Web Developer Intern" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="internship-status">Status <span class="text-danger" aria-hidden="true">*</span></label>
                <select id="internship-status" class="form-select @error('status') is-invalid @enderror" name="status" required>
                    <option value="open" {{ old('status', $internship->status) === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="closed" {{ old('status', $internship->status) === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                @php $selectedPrograms = collect(old('program_ids', $internship->exists ? $internship->programs->pluck('id')->all() : []))->map(fn ($id) => (int) $id); @endphp
                <fieldset>
                    <div class="ei-programs-top">
                        <legend class="form-label">Open to programs</legend>
                        <span class="ei-selected">{{ $selectedPrograms->isEmpty() ? 'All programs' : $selectedPrograms->count().' selected' }}</span>
                    </div>
                    <div class="sb-check-grid">
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
        </div>
    </div>
</section>

{{-- 2. Logistics --}}
<section class="card sb-form-section" aria-labelledby="internshipLogisticsTitle">
    <div class="ei-head">
        <span class="ei-head-icon" aria-hidden="true"><i class="bi bi-geo-alt"></i></span>
        <div class="ei-head-text">
            <h2 class="ei-title" id="internshipLogisticsTitle">Placement details</h2>
            <p class="ei-sub">Where, how long, how many interns, and until when to apply.</p>
        </div>
    </div>
    <div class="ei-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="internship-location">Location</label>
                <input type="text" id="internship-location" class="form-control @error('location') is-invalid @enderror" name="location" value="{{ old('location', $internship->location) }}" placeholder="e.g. Cebu City">
                @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label" for="internship-duration">Duration</label>
                <input type="text" id="internship-duration" class="form-control @error('duration') is-invalid @enderror" name="duration" value="{{ old('duration', $internship->duration) }}" placeholder="e.g. 3 months">
                @error('duration')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label" for="internship-slots">Number of slots</label>
                <input type="number" id="internship-slots" min="1" max="1000" step="1" class="form-control @error('slots') is-invalid @enderror" name="slots" value="{{ old('slots', $internship->slots) }}" placeholder="e.g. 3">
                @error('slots')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="expires_at">Application deadline</label>
                <input type="date" id="expires_at" class="form-control @error('expires_at') is-invalid @enderror" name="expires_at" value="{{ old('expires_at', $internship->expires_at?->format('Y-m-d')) }}" @unless($internship->expires_at?->isPast()) min="{{ now()->format('Y-m-d') }}" @endunless aria-describedby="expires_at_help">
                <div id="expires_at_help" class="form-text">Optional. Applications close at the end of this day, and students who match get a reminder a few days before.</div>
                @error('expires_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            {{-- New postings are posted without an allowance; editing keeps the field so older values can be changed or cleared. --}}
            @if($internship->exists)
                <div class="col-md-6">
                    <label class="form-label" for="internship-allowance">Allowance (₱)</label>
                    <input type="number" id="internship-allowance" step="0.01" min="0" class="form-control @error('allowance') is-invalid @enderror" name="allowance" value="{{ old('allowance', $internship->allowance) }}">
                    @error('allowance')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            @endif
        </div>
    </div>
</section>

{{-- 3. Role description --}}
<section class="card sb-form-section" aria-labelledby="internshipRoleTitle">
    <div class="ei-head">
        <span class="ei-head-icon" aria-hidden="true"><i class="bi bi-card-text"></i></span>
        <div class="ei-head-text">
            <h2 class="ei-title" id="internshipRoleTitle">About the role</h2>
            <p class="ei-sub">Help students understand what they will do and who fits.</p>
        </div>
    </div>
    <div class="ei-body">
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label" for="internship-description">Description <span class="text-danger" aria-hidden="true">*</span></label>
                <textarea id="internship-description" class="form-control @error('description') is-invalid @enderror" name="description" rows="4" placeholder="Summarize the role, the team and what the intern will gain." required>{{ old('description', $internship->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="internship-responsibilities">Responsibilities</label>
                <textarea id="internship-responsibilities" class="form-control" name="responsibilities" rows="5" placeholder="One duty per line">{{ old('responsibilities', $internship->responsibilities) }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="internship-qualifications">Qualifications</label>
                <textarea id="internship-qualifications" class="form-control @error('qualifications') is-invalid @enderror" name="qualifications" rows="5" placeholder="One requirement per line">{{ old('qualifications', $internship->qualifications) }}</textarea>
                @error('qualifications')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</section>
</div>{{-- /left .sb-form-col --}}

<div class="sb-form-col">
<section class="card sb-form-section" aria-labelledby="competencyRequirementsTitle">
    <div class="ei-head">
        <span class="ei-head-icon" aria-hidden="true"><i class="bi bi-bullseye"></i></span>
        <div class="ei-head-text">
            <h2 class="ei-title" id="competencyRequirementsTitle">Competency Requirements</h2>
            <p class="ei-sub">Skills used to match students.</p>
        </div>
    </div>
    <div class="ei-body">
        <div data-requirements>
            @php
                $existingReqs = old('requirement_names')
                    ? collect(old('requirement_names'))->map(fn($n, $i) => ['name' => $n, 'level' => old('requirement_levels')[$i] ?? 'intermediate'])
                    : ($internship->exists ? $internship->requirementsList->map(fn($r) => ['name' => $r->requirement_name, 'level' => $r->required_level instanceof \App\Enums\ProficiencyLevel ? $r->required_level->value : $r->required_level]) : collect([['name' => '', 'level' => 'intermediate']]));
            @endphp
            @foreach($existingReqs as $req)
                <div class="sb-repeat-row requirement-row">
                    <input type="text" class="form-control" name="requirement_names[]" value="{{ $req['name'] }}" placeholder="Skill / competency name" aria-label="Skill or competency name">
                    <select class="form-select" name="requirement_levels[]" aria-label="Required level">
                        @foreach($levels as $level)
                            <option value="{{ $level->value }}" {{ ($req['level'] ?? '') === $level->value ? 'selected' : '' }}>{{ $level->label() }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-outline-danger remove-requirement" aria-label="Remove requirement"><i class="bi bi-x" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" class="ei-add" data-add-requirement><i class="bi bi-plus-lg" aria-hidden="true"></i> Add Requirement</button>
        @error('requirement_names.*')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
    </div>
</section>

@if($onRequestDocuments->isNotEmpty())
<section class="card sb-form-section" aria-labelledby="applicantRequirementsTitle">
    <div class="ei-head">
        <span class="ei-head-icon" aria-hidden="true"><i class="bi bi-file-earmark-check"></i></span>
        <div class="ei-head-text">
            <h2 class="ei-title" id="applicantRequirementsTitle">Applicant requirements</h2>
            <p class="ei-sub">IDs students upload when they apply.</p>
        </div>
    </div>
    <div class="ei-body">
        <fieldset>
            <legend class="form-label mb-1">Request these IDs from applicants</legend>
            <p class="sb-form-intro mt-0">Students upload a file of each ID after they apply. Only ID files are collected, never ID numbers.</p>
            @php($requestedDocuments = old('requested_documents', $internship->requested_documents ?? []))
            <div class="ei-docs">
                @foreach($onRequestDocuments as $document)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="requested_documents[]" value="{{ $document->code }}" id="requested-{{ $document->code }}" @checked(in_array($document->code, $requestedDocuments, true))>
                        <label class="form-check-label" for="requested-{{ $document->code }}">{{ $document->name }}</label>
                    </div>
                @endforeach
            </div>
            @error('requested_documents.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </fieldset>
    </div>
</section>
@endif
</div>{{-- /right .sb-form-col --}}
</div>{{-- /.sb-form --}}

<template data-requirement-template>
    <div class="sb-repeat-row requirement-row">
            <input type="text" class="form-control" name="requirement_names[]" placeholder="Skill / competency name" aria-label="Skill or competency name">
            <select class="form-select" name="requirement_levels[]" aria-label="Required level">
                @foreach($levels as $level)<option value="{{ $level->value }}">{{ $level->label() }}</option>@endforeach
            </select>
            <button type="button" class="btn btn-outline-danger remove-requirement" aria-label="Remove requirement"><i class="bi bi-x" aria-hidden="true"></i></button>
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

        // "Open to programs": live count of ticked programs.
        document.addEventListener('change', (event) => {
            if (!event.target.matches('input[name="program_ids[]"]')) return;
            const fieldset = event.target.closest('fieldset');
            const total = fieldset.querySelectorAll('input[name="program_ids[]"]:checked').length;
            const label = fieldset.querySelector('.ei-selected');
            if (label) label.textContent = total === 0 ? 'All programs' : total + ' selected';
        });
    </script>
    @endpush
@endonce
