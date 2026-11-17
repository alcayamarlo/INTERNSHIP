@extends('layouts.app')

@section('title', 'Edit resume')

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

<div class="sb-editor">
    <form id="resumeEditorForm" class="sb-editor-form" novalidate data-resume-editor data-no-loading="true"
          data-preview-url="{{ route('student.resume.preview', $resume) }}"
          data-pages-url="{{ route('student.resume.pages', $resume) }}"
          data-update-url="{{ route('student.resume.update', $resume) }}"
          data-copy-url="{{ route('student.resume.copy', $resume) }}"
          data-pdf-url="{{ route('student.resume.pdf', $resume) }}">

        <div class="alert alert-danger sb-error-summary" data-error-summary role="alert" tabindex="-1" hidden></div>

        {{-- Personal info --}}
        <section class="card sb-editor-section" aria-labelledby="reTitlePersonal">
            <div class="card-header"><h2 id="reTitlePersonal"><i class="bi bi-person-vcard" aria-hidden="true"></i> Personal info</h2></div>
            <div class="card-body">
                <div class="sb-field-grid sb-field-grid--2">
                    <label class="sb-field sb-span-all">
                        <span>Full name <b class="sb-req" aria-hidden="true">*</b></span>
                        <input type="text" class="form-control" data-field="name" value="{{ $editor['name'] }}" maxlength="120" required autocomplete="name">
                    </label>
                    <label class="sb-field">
                        <span>Course</span>
                        <input type="text" class="form-control" data-field="course" value="{{ $editor['course'] }}" maxlength="150" placeholder="e.g. BS Information Technology">
                    </label>
                    <label class="sb-field">
                        <span>School</span>
                        <input type="text" class="form-control" data-field="school" value="{{ $editor['school'] }}" maxlength="150">
                    </label>
                    <label class="sb-field">
                        <span>Contact email <b class="sb-req" aria-hidden="true">*</b></span>
                        <input type="email" class="form-control" data-field="contact.email" value="{{ $editor['contact']['email'] }}" maxlength="150" required autocomplete="email">
                    </label>
                    <label class="sb-field">
                        <span>Mobile</span>
                        <input type="tel" class="form-control" data-field="contact.mobile" value="{{ $editor['contact']['mobile'] }}" maxlength="40" autocomplete="tel">
                    </label>
                    <label class="sb-field">
                        <span>City address</span>
                        <input type="text" class="form-control" data-field="contact.city" value="{{ $editor['contact']['city'] }}" maxlength="120" placeholder="e.g. Cebu City, Cebu" autocomplete="address-level2">
                    </label>
                </div>

                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="reShowPhoto" data-field="show_photo" @checked($editor['show_photo'])>
                    <label class="form-check-label" for="reShowPhoto">Show my 2x2 photo on the resume</label>
                </div>
                <p class="sb-form-intro mt-2 mb-0"><i class="bi bi-shield-lock" aria-hidden="true"></i> Your resume leaves out personal details such as birth date, civil status, religion and home address. Employers do not need them, and they stay private on your profile.</p>
            </div>
        </section>

        {{-- Career summary --}}
        <section class="card sb-editor-section" aria-labelledby="reTitleSummary">
            <div class="card-header"><h2 id="reTitleSummary"><i class="bi bi-chat-square-text" aria-hidden="true"></i> About me</h2></div>
            <div class="card-body">
                <label class="sb-field">
                    <span class="visually-hidden">About me</span>
                    <textarea class="form-control" data-field="summary" rows="4" maxlength="1200" placeholder="Two or three sentences about your goals and strengths.">{{ $editor['summary'] }}</textarea>
                </label>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="reUpdateProfile" data-field="update_profile_summary">
                    <label class="form-check-label" for="reUpdateProfile">Also update my profile's About me with this text</label>
                </div>
            </div>
        </section>

        {{-- Education --}}
        <section class="card sb-editor-section" aria-labelledby="reTitleEducation">
            <div class="card-header"><h2 id="reTitleEducation"><i class="bi bi-mortarboard" aria-hidden="true"></i> Education</h2></div>
            <div class="card-body">
                <div class="sb-list-edit sb-list-edit--rows" data-list="education" data-empty-text="No education details.">
                    @foreach($editor['education'] as $row)
                        @include('student.resume.partials.editor-row', ['row' => $row, 'labelPlaceholder' => 'e.g. High school / SHS', 'valuePlaceholder' => 'e.g. Cebu City NHS (2022)'])
                    @endforeach
                </div>
                <button type="button" class="sb-add-btn" data-add="row-education"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add education</button>
            </div>
        </section>

        {{-- Skills --}}
        <section class="card sb-editor-section" aria-labelledby="reTitleSkills">
            <div class="card-header"><h2 id="reTitleSkills"><i class="bi bi-stars" aria-hidden="true"></i> Skills</h2></div>
            <div class="card-body">
                <div class="sb-list-edit sb-list-edit--values" data-list="skills" data-empty-text="No skills yet.">
                    @foreach($editor['skills'] as $skill)
                        @include('student.resume.partials.editor-value', ['value' => $skill])
                    @endforeach
                </div>
                <button type="button" class="sb-add-btn" data-add="value-skills"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add skill</button>
            </div>
        </section>

        {{-- Experience, projects, certifications, references --}}
        @foreach($entrySections as $section => $config)
            @if($section === 'references')
                {{-- Honors / awards sit before references. --}}
                <section class="card sb-editor-section" aria-labelledby="reTitleAwards">
                    <div class="card-header"><h2 id="reTitleAwards"><i class="bi bi-trophy" aria-hidden="true"></i> Honors / awards</h2></div>
                    <div class="card-body">
                        <div class="sb-list-edit" data-list="awards" data-empty-text="No honors or awards yet.">
                            @foreach($editor['awards'] as $award)
                                @include('student.resume.partials.editor-award', ['award' => $award])
                            @endforeach
                        </div>
                        <button type="button" class="sb-add-btn" data-add="award"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add award</button>
                    </div>
                </section>
            @endif

            <section class="card sb-editor-section" aria-labelledby="reTitle{{ ucfirst($section) }}">
                <div class="card-header"><h2 id="reTitle{{ ucfirst($section) }}"><i class="bi {{ $config['icon'] }}" aria-hidden="true"></i> {{ $config['heading'] }}</h2></div>
                <div class="card-body">
                    <div class="sb-list-edit" data-list="{{ $section }}" data-empty-text="{{ $config['empty'] }}">
                        @foreach($editor[$section] as $entry)
                            @include('student.resume.partials.editor-entry', ['section' => $section, 'config' => $config, 'entry' => $entry])
                        @endforeach
                    </div>
                    <button type="button" class="sb-add-btn" data-add="entry-{{ $section }}"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add {{ $config['noun'] }}</button>
                </div>
            </section>
        @endforeach

        {{-- Actions stay in reach while scrolling the form. --}}
        <div class="sb-sticky-actions" data-editor-actions>
            <span class="sb-save-status" data-save-status role="status" aria-live="polite">No unsaved changes</span>
            {{-- Updated about 3 seconds after the last change: 1 page is ideal, 2 pages is the most that can be saved. --}}
            <span class="sb-save-status" data-page-status role="status" aria-live="polite">Checking page count…</span>
            <div class="sb-sticky-actions-buttons">
                <button type="submit" form="resumeCancelForm" class="btn btn-link sb-link-muted">Cancel</button>
                <button type="submit" class="btn btn-outline-primary" name="intent" value="pdf"><i class="bi bi-download" aria-hidden="true"></i> Download PDF</button>
                <button type="submit" class="btn btn-outline-primary" name="intent" value="copy"
                    @if($atLimit) data-confirm="Only your {{ $maxResumes }} latest resumes are kept, so saving a new one removes your oldest resume. Continue?" data-confirm-title="Save as new resume?" data-confirm-label="Save as new" @endif>
                    <i class="bi bi-files" aria-hidden="true"></i> Save as new resume
                </button>
                <button type="submit" class="btn btn-primary" name="intent" value="save"><i class="bi bi-check2" aria-hidden="true"></i> Save changes</button>
            </div>
        </div>
    </form>

    <x-resume-preview :html="$previewHtml" class="sb-editor-preview" />
</div>

{{-- Cancel goes back to the list; resume-editor.js asks first when there are unsaved changes. --}}
<form id="resumeCancelForm" method="GET" action="{{ route('student.resume.index') }}" data-no-loading="true" data-cancel-form
      data-confirm-title="Discard your changes?" data-confirm-label="Discard"></form>

{{-- Blank items for the Add buttons. --}}
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
