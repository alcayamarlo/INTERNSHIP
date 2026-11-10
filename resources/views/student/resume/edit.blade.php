@extends('layouts.app')

@section('title', 'Edit resume')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/resume-editor.css') }}?v={{ @filemtime(public_path('css/resume-editor.css')) }}">
@endpush

@php
    $generatedAt = $resume->generated_at ?? $resume->created_at;
    $editedAt = filled($resume->content['edited_at'] ?? null) ? \Illuminate\Support\Carbon::parse($resume->content['edited_at'])->timezone('Asia/Manila') : null;

    // Entry sections on the form, in the order students think about them (the PDF order is fixed by the template).
    $entrySections = [
        'experiences' => ['heading' => 'Work experience', 'icon' => 'bi-briefcase', 'noun' => 'experience', 'title' => 'Position / role', 'titlePlaceholder' => 'e.g. IT Support Assistant',
            'rowLabel' => 'e.g. Organization', 'rowValue' => 'e.g. City Hall IT Office', 'skills' => false, 'description' => true, 'descriptionPlaceholder' => 'What you did and achieved.',
            'empty' => 'No work experience yet. Add part-time jobs, volunteer work, or school roles.'],
        'projects' => ['heading' => 'Projects', 'icon' => 'bi-kanban', 'noun' => 'project', 'title' => 'Project title', 'titlePlaceholder' => 'e.g. Library booking system',
            'rowLabel' => 'e.g. Tools used', 'rowValue' => 'e.g. Laravel, MySQL', 'skills' => false, 'description' => true, 'descriptionPlaceholder' => 'What the project does and your part in it.',
            'empty' => 'No projects yet.'],
        'certificates' => ['heading' => 'Certifications', 'icon' => 'bi-award', 'noun' => 'certificate', 'title' => 'Certificate name', 'titlePlaceholder' => 'e.g. Computer Systems Servicing NC II',
            'rowLabel' => 'e.g. Issuer', 'rowValue' => 'e.g. TESDA', 'skills' => true, 'description' => false, 'descriptionPlaceholder' => '',
            'empty' => 'No certificates yet.'],
        'references' => ['heading' => 'Character references', 'icon' => 'bi-people', 'noun' => 'reference', 'title' => 'Full name', 'titlePlaceholder' => 'e.g. Engr. Maria Santos',
            'rowLabel' => 'e.g. Position', 'rowValue' => 'e.g. Program Head', 'skills' => false, 'description' => false, 'descriptionPlaceholder' => '',
            'empty' => 'No references yet.'],
    ];
    $atLimit = $resumeCount >= $maxResumes;
@endphp

@section('content')
<x-breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('student.dashboard')], ['label' => 'Resume Builder', 'url' => route('student.resume.index')], ['label' => 'Edit resume']]" />

<x-page-header title="Edit resume" subtitle="Changes are saved to this resume only. Your profile stays as it is.">
    <span class="sb-tag"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Generated {{ $generatedAt->format('M d, Y h:i A') }}</span>
    @if($editedAt)<span class="sb-tag" data-resume-edited-at>Edited {{ $editedAt->format('M d, Y h:i A') }}</span>@endif
</x-page-header>

<div class="re-page">
    <form id="resumeEditorForm" class="re-form" novalidate data-resume-editor data-no-loading="true"
          data-preview-url="{{ route('student.resume.preview', $resume) }}"
          data-update-url="{{ route('student.resume.update', $resume) }}"
          data-copy-url="{{ route('student.resume.copy', $resume) }}"
          data-pdf-url="{{ route('student.resume.pdf', $resume) }}">

        <div class="alert alert-danger re-error-summary" data-error-summary role="alert" tabindex="-1" hidden></div>

        {{-- Personal info --}}
        <section class="card re-section" aria-labelledby="reTitlePersonal">
            <div class="card-header"><h2 id="reTitlePersonal"><i class="bi bi-person-vcard" aria-hidden="true"></i> Personal info</h2></div>
            <div class="card-body">
                <div class="re-grid re-grid--2">
                    <label class="re-field re-span-2">
                        <span>Full name <b class="re-req" aria-hidden="true">*</b></span>
                        <input type="text" class="form-control" data-field="name" value="{{ $editor['name'] }}" maxlength="120" required autocomplete="name">
                    </label>
                    <label class="re-field">
                        <span>Course</span>
                        <input type="text" class="form-control" data-field="course" value="{{ $editor['course'] }}" maxlength="150" placeholder="e.g. BS Information Technology">
                    </label>
                    <label class="re-field">
                        <span>School</span>
                        <input type="text" class="form-control" data-field="school" value="{{ $editor['school'] }}" maxlength="150">
                    </label>
                    <label class="re-field">
                        <span>Contact email <b class="re-req" aria-hidden="true">*</b></span>
                        <input type="email" class="form-control" data-field="contact.email" value="{{ $editor['contact']['email'] }}" maxlength="150" required autocomplete="email">
                    </label>
                    <label class="re-field">
                        <span>Mobile</span>
                        <input type="tel" class="form-control" data-field="contact.mobile" value="{{ $editor['contact']['mobile'] }}" maxlength="40" autocomplete="tel">
                    </label>
                    <label class="re-field">
                        <span>City address</span>
                        <input type="text" class="form-control" data-field="contact.city_address" value="{{ $editor['contact']['city_address'] }}" maxlength="300">
                    </label>
                    <label class="re-field">
                        <span>Provincial address</span>
                        <input type="text" class="form-control" data-field="contact.provincial_address" value="{{ $editor['contact']['provincial_address'] }}" maxlength="300">
                    </label>
                </div>

                <h3 class="re-subhead">Personal details</h3>
                <p class="re-hint">Shown under "Personal data", e.g. Birthday, Sex, Civil status.</p>
                <div class="re-list re-list--rows" data-list="personal" data-empty-text="No personal details.">
                    @foreach($editor['personal'] as $row)
                        @include('student.resume.partials.editor-row', ['row' => $row, 'labelPlaceholder' => 'e.g. Birthday', 'valuePlaceholder' => 'e.g. March 4, 2004'])
                    @endforeach
                </div>
                <button type="button" class="re-add" data-add="row-personal"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add detail</button>
            </div>
        </section>

        {{-- Career summary --}}
        <section class="card re-section" aria-labelledby="reTitleSummary">
            <div class="card-header"><h2 id="reTitleSummary"><i class="bi bi-chat-square-text" aria-hidden="true"></i> Career summary</h2></div>
            <div class="card-body">
                <label class="re-field">
                    <span class="visually-hidden">Career summary</span>
                    <textarea class="form-control" data-field="summary" rows="4" maxlength="2000" placeholder="Two or three sentences about your goals and strengths.">{{ $editor['summary'] }}</textarea>
                </label>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="reUpdateProfile" data-field="update_profile_summary">
                    <label class="form-check-label" for="reUpdateProfile">Also update my profile's career summary with this text</label>
                </div>
            </div>
        </section>

        {{-- Education --}}
        <section class="card re-section" aria-labelledby="reTitleEducation">
            <div class="card-header"><h2 id="reTitleEducation"><i class="bi bi-mortarboard" aria-hidden="true"></i> Education</h2></div>
            <div class="card-body">
                <div class="re-list re-list--rows" data-list="education" data-empty-text="No education details.">
                    @foreach($editor['education'] as $row)
                        @include('student.resume.partials.editor-row', ['row' => $row, 'labelPlaceholder' => 'e.g. High school / SHS', 'valuePlaceholder' => 'e.g. Cebu City NHS (2022)'])
                    @endforeach
                </div>
                <button type="button" class="re-add" data-add="row-education"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add education</button>
            </div>
        </section>

        {{-- Skills --}}
        <section class="card re-section" aria-labelledby="reTitleSkills">
            <div class="card-header"><h2 id="reTitleSkills"><i class="bi bi-stars" aria-hidden="true"></i> Skills</h2></div>
            <div class="card-body">
                <div class="re-list re-list--values" data-list="skills" data-empty-text="No skills yet.">
                    @foreach($editor['skills'] as $skill)
                        @include('student.resume.partials.editor-value', ['value' => $skill])
                    @endforeach
                </div>
                <button type="button" class="re-add" data-add="value-skills"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add skill</button>
            </div>
        </section>

        {{-- Experience, projects, certifications, references --}}
        @foreach($entrySections as $section => $config)
            @if($section === 'references')
                {{-- Honors / awards sit before references. --}}
                <section class="card re-section" aria-labelledby="reTitleAwards">
                    <div class="card-header"><h2 id="reTitleAwards"><i class="bi bi-trophy" aria-hidden="true"></i> Honors / awards</h2></div>
                    <div class="card-body">
                        <div class="re-list" data-list="awards" data-empty-text="No honors or awards yet.">
                            @foreach($editor['awards'] as $award)
                                @include('student.resume.partials.editor-award', ['award' => $award])
                            @endforeach
                        </div>
                        <button type="button" class="re-add" data-add="award"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add award</button>
                    </div>
                </section>
            @endif

            <section class="card re-section" aria-labelledby="reTitle{{ ucfirst($section) }}">
                <div class="card-header"><h2 id="reTitle{{ ucfirst($section) }}"><i class="bi {{ $config['icon'] }}" aria-hidden="true"></i> {{ $config['heading'] }}</h2></div>
                <div class="card-body">
                    <div class="re-list" data-list="{{ $section }}" data-empty-text="{{ $config['empty'] }}">
                        @foreach($editor[$section] as $entry)
                            @include('student.resume.partials.editor-entry', ['section' => $section, 'config' => $config, 'entry' => $entry])
                        @endforeach
                    </div>
                    <button type="button" class="re-add" data-add="entry-{{ $section }}"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add {{ $config['noun'] }}</button>
                </div>
            </section>
        @endforeach

        {{-- Actions stay in reach while scrolling the form. --}}
        <div class="re-actions" data-editor-actions>
            <span class="re-save-status" data-save-status role="status" aria-live="polite">No unsaved changes</span>
            <div class="re-actions-buttons">
                <button type="submit" form="resumeCancelForm" class="btn btn-link re-cancel">Cancel</button>
                <button type="submit" class="btn btn-outline-primary" name="intent" value="pdf"><i class="bi bi-download" aria-hidden="true"></i> Download PDF</button>
                <button type="submit" class="btn btn-outline-primary" name="intent" value="copy"
                    @if($atLimit) data-confirm="Only your {{ $maxResumes }} latest resumes are kept, so saving a new one removes your oldest resume. Continue?" data-confirm-title="Save as new resume?" data-confirm-label="Save as new" @endif>
                    <i class="bi bi-files" aria-hidden="true"></i> Save as new resume
                </button>
                <button type="submit" class="btn btn-primary" name="intent" value="save"><i class="bi bi-check2" aria-hidden="true"></i> Save changes</button>
            </div>
        </div>
    </form>

    <x-resume-preview :html="$previewHtml" class="re-preview" />
</div>

{{-- Cancel goes back to the list; resume-editor.js asks first when there are unsaved changes. --}}
<form id="resumeCancelForm" method="GET" action="{{ route('student.resume.index') }}" data-no-loading="true" data-cancel-form
      data-confirm-title="Discard your changes?" data-confirm-label="Discard"></form>

{{-- Blank items for the Add buttons. --}}
<template data-template="row-personal">@include('student.resume.partials.editor-row', ['row' => null, 'labelPlaceholder' => 'e.g. Birthday', 'valuePlaceholder' => 'e.g. March 4, 2004'])</template>
<template data-template="row-education">@include('student.resume.partials.editor-row', ['row' => null, 'labelPlaceholder' => 'e.g. High school / SHS', 'valuePlaceholder' => 'e.g. Cebu City NHS (2022)'])</template>
<template data-template="value-skills">@include('student.resume.partials.editor-value', ['value' => ''])</template>
<template data-template="award">@include('student.resume.partials.editor-award', ['award' => null])</template>
@foreach($entrySections as $section => $config)
    <template data-template="entry-{{ $section }}">@include('student.resume.partials.editor-entry', ['section' => $section, 'config' => $config, 'entry' => null])</template>
    <template data-template="row-{{ $section }}">@include('student.resume.partials.editor-row', ['row' => null, 'labelPlaceholder' => $config['rowLabel'], 'valuePlaceholder' => $config['rowValue']])</template>
@endforeach
@endsection

@push('scripts')
    <script src="{{ asset('js/resume-editor.js') }}?v={{ @filemtime(public_path('js/resume-editor.js')) }}" defer></script>
@endpush
