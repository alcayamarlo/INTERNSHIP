@extends('layouts.app')

@section('title', $internship->exists ? 'Edit Internship' : 'New Internship')

@section('content')
<x-page-header :title="$internship->exists ? 'Edit Internship' : 'Post New Internship'" subtitle="Describe the role and the competencies you are looking for.">
    <a href="{{ route('employer.internships.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Internships</a>
</x-page-header>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ $internship->exists ? route('employer.internships.update', $internship) : route('employer.internships.store') }}">
            @csrf
            @if($internship->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Title <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title', $internship->title) }}" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status <span class="text-danger" aria-hidden="true">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                        <option value="open" {{ old('status', $internship->status) === 'open' ? 'selected' : '' }}>Open</option>
                        <option value="closed" {{ old('status', $internship->status) === 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Work Setup <span class="text-danger" aria-hidden="true">*</span></label>
                    <select class="form-select @error('work_setup') is-invalid @enderror" name="work_setup" required>
                        @foreach($workSetups as $setup)
                            <option value="{{ $setup->value }}" {{ old('work_setup', $internship->work_setup?->value) === $setup->value ? 'selected' : '' }}>{{ $setup->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-control" name="location" value="{{ old('location', $internship->location) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Duration</label>
                    <input type="text" class="form-control" name="duration" value="{{ old('duration', $internship->duration) }}" placeholder="e.g. 3 months">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Allowance (₱)</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="allowance" value="{{ old('allowance', $internship->allowance) }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Description <span class="text-danger" aria-hidden="true">*</span></label>
                    <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="4" required>{{ old('description', $internship->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Responsibilities</label>
                    <textarea class="form-control" name="responsibilities" rows="4">{{ old('responsibilities', $internship->responsibilities) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">General Requirements</label>
                    <textarea class="form-control" name="requirements" rows="4">{{ old('requirements', $internship->requirements) }}</textarea>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 mb-0">Competency Requirements</h2>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addRequirement"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add Requirement</button>
            </div>
            <div id="requirementsContainer">
                @php
                    $existingReqs = old('requirement_names')
                        ? collect(old('requirement_names'))->map(fn($n, $i) => ['name' => $n, 'level' => old('requirement_levels')[$i] ?? 'intermediate'])
                        : ($internship->exists ? $internship->requirementsList->map(fn($r) => ['name' => $r->requirement_name, 'level' => $r->required_level instanceof \App\Enums\ProficiencyLevel ? $r->required_level->value : $r->required_level]) : collect([['name' => '', 'level' => 'intermediate']]));
                @endphp
                @foreach($existingReqs as $req)
                    <div class="row g-2 mb-2 requirement-row">
                        <div class="col-md-7">
                            <input type="text" class="form-control" name="requirement_names[]" value="{{ $req['name'] }}" placeholder="Skill / competency name">
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="requirement_levels[]">
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

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save" aria-hidden="true"></i> {{ $internship->exists ? 'Update' : 'Post' }} Internship</button>
            </div>
        </form>
    </div>
</div>

<template id="requirementTemplate">
    <div class="row g-2 mb-2 requirement-row">
        <div class="col-md-7">
            <input type="text" class="form-control" name="requirement_names[]" placeholder="Skill / competency name">
        </div>
        <div class="col-md-4">
            <select class="form-select" name="requirement_levels[]">
                @foreach($levels as $level)<option value="{{ $level->value }}">{{ $level->label() }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-outline-danger w-100 remove-requirement" aria-label="Remove requirement"><i class="bi bi-x" aria-hidden="true"></i></button>
        </div>
    </div>
</template>
@endsection

@push('scripts')
<script>
    document.getElementById('addRequirement').addEventListener('click', () => {
        const tpl = document.getElementById('requirementTemplate');
        document.getElementById('requirementsContainer').appendChild(tpl.content.cloneNode(true));
    });
    document.getElementById('requirementsContainer').addEventListener('click', e => {
        if (e.target.closest('.remove-requirement')) {
            const rows = document.querySelectorAll('.requirement-row');
            if (rows.length > 1) e.target.closest('.requirement-row').remove();
        }
    });
</script>
@endpush
