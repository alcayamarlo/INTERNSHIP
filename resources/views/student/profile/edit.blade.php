@extends('layouts.app')

@section('title', 'My Profile')

@push('styles')
<style>
    .student-profile-page { color: #e6f0fa; padding: 8px 0 30px; }
    .student-profile-page, .student-profile-page * { box-sizing: border-box; }
    .student-profile-page .profile-header,
    .student-profile-page .profile-panel,
    .student-profile-page .stat-card {
        background: #0f2238;
        border: 1px solid #1c3250;
        border-radius: 8px;
    }
    .student-profile-page .profile-header { display: flex; align-items: center; justify-content: space-between; gap: 28px; padding: 20px 24px; }
    .student-profile-page .identity { display: flex; align-items: center; gap: 16px; min-width: 0; }
    .student-profile-page .avatar-wrap { position: relative; flex: 0 0 72px; width: 72px; height: 72px; }
    .student-profile-page .avatar { width: 72px; height: 72px; display: flex; align-items: center; justify-content: center; overflow: hidden; border-radius: 50%; background: #142d49; color: #8ba3bd; font-size: 26px; }
    .student-profile-page .avatar img { width: 100%; height: 100%; object-fit: cover; }
    .student-profile-page .avatar-action { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; border: 0; border-radius: 50%; background: rgba(11,26,46,.78); color: #38bdf8; font-size: 11px; opacity: 0; cursor: pointer; transition: opacity .15s ease; }
    .student-profile-page .avatar-wrap:hover .avatar-action,
    .student-profile-page .avatar-action:focus-visible { opacity: 1; }
    .student-profile-page .profile-name { margin: 0; color: #f2f7fc; font-size: 20px; font-weight: 600; }
    .student-profile-page .profile-subline { margin: 4px 0 0; color: #9aacc1; font-size: 13px; }
    .student-profile-page .completion { width: min(330px, 40%); }
    .student-profile-page .completion-line { display: flex; justify-content: space-between; margin-bottom: 8px; color: #9aacc1; font-size: 12px; }
    .student-profile-page .completion-line strong { color: #dce9f5; font-size: 12px; font-weight: 500; }
    .student-profile-page .completion-track { height: 6px; overflow: hidden; border-radius: 4px; background: #1b3450; }
    .student-profile-page .completion-fill { height: 100%; border-radius: 4px; background: #38bdf8; transition: width .2s ease; }
    .student-profile-page .completion-steps { margin-top: 8px; color: #a9bad0; font-size: 12px; }
    .student-profile-page .completion-steps summary { color: #dce9f5; cursor: pointer; }
    .student-profile-page .completion-steps ul { display: grid; gap: 4px; margin: 7px 0 0; padding-left: 18px; }
    .student-profile-page .resume-actions { display: flex; gap: 8px; margin-top: 12px; }
    .student-profile-page .btn-profile,
    .student-profile-page .btn-primary-profile {
        display: inline-flex; align-items: center; justify-content: center; gap: 7px;
        min-height: 36px; padding: 7px 12px; border: 1px solid #2a4566; border-radius: 6px;
        color: #dce9f5; background: #112942; font-size: 13px; font-weight: 500; text-decoration: none;
    }
    .student-profile-page .btn-profile:hover { border-color: #38bdf8; color: #38bdf8; }
    .student-profile-page .btn-primary-profile { border-color: #38bdf8; background: #38bdf8; color: #0b1a2e; }
    .student-profile-page .btn-primary-profile:hover { border-color: #69ccfa; background: #69ccfa; color: #0b1a2e; }
    .student-profile-page .tabs { display: flex; gap: 24px; margin-top: 22px; overflow-x: auto; border-bottom: 1px solid #1c3250; scrollbar-width: thin; }
    .student-profile-page .tab { position: relative; display: inline-flex; align-items: center; gap: 7px; flex: 0 0 auto; min-height: 44px; padding: 0 2px; border: 0; border-bottom: 2px solid transparent; background: transparent; color: #91a5bb; font-size: 13px; white-space: nowrap; cursor: pointer; }
    .student-profile-page .tab[aria-selected="true"] { border-bottom-color: #38bdf8; color: #38bdf8; }
    .student-profile-page .tab:focus-visible { outline: 2px solid #38bdf8; outline-offset: -3px; }
    .student-profile-page .tab-count { display: inline-flex; align-items: center; justify-content: center; min-width: 20px; height: 20px; padding: 0 6px; border-radius: 10px; background: #173653; color: #b9cce0; font-size: 11px; }
    .student-profile-page .tab[aria-selected="true"] .tab-count { background: #123b55; color: #75d3ff; }
    .student-profile-page .tab-error-dot { display: none; width: 7px; height: 7px; border-radius: 50%; background: #ef6464; }
    .student-profile-page .tab.has-error .tab-error-dot { display: inline-block; }
    .student-profile-page .profile-panel { margin-top: 18px; padding: 20px; }
    .student-profile-page [role="tabpanel"][hidden] { display: none !important; }
    .student-profile-page .panel-heading { margin: 0 0 18px; color: #e6f0fa; font-size: 14px; font-weight: 500; }
    .student-profile-page .profile-field-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
    .student-profile-page .field-wide { grid-column: 1 / -1; }
    .student-profile-page .form-label { display: block; margin-bottom: 6px; color: #a9b9ca; font-size: 12px; }
    .student-profile-page .form-control,
    .student-profile-page .form-select { width: 100%; min-height: 38px; border: 1px solid #2a4566; border-radius: 6px; padding: 8px 10px; background-color: #0b1a2e; color: #e6f0fa; font-size: 13px; }
    .student-profile-page textarea.form-control { min-height: 88px; resize: vertical; }
    .student-profile-page .form-control:focus,
    .student-profile-page .form-select:focus { border-color: #38bdf8; outline: 0; box-shadow: 0 0 0 2px rgba(56,189,248,.12); }
    .student-profile-page .form-control.is-invalid,
    .student-profile-page .form-select.is-invalid { border-color: #ef6464; }
    .student-profile-page .invalid-feedback { display: block; margin-top: 5px; color: #ff8585; font-size: 12px; }
    .student-profile-page .section-divider { margin: 22px 0 18px; border: 0; border-top: 1px solid #1c3250; }
    .student-profile-page .stat-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 18px; }
    .student-profile-page .stat-card { min-height: 92px; padding: 14px; }
    .student-profile-page .stat-label { color: #9aacc1; font-size: 12px; }
    .student-profile-page .stat-value { display: block; margin-top: 8px; color: #edf6ff; font-size: 20px; font-weight: 600; }
    .student-profile-page .portfolio-toolbar { display: flex; align-items: end; gap: 12px; margin-bottom: 16px; }
    .student-profile-page .portfolio-toolbar .search-field { flex: 1 1 auto; }
    .student-profile-page .portfolio-toolbar .type-field { flex: 0 0 220px; }
    .student-profile-page .portfolio-table { width: 100%; border-collapse: collapse; }
    .student-profile-page .portfolio-table th { padding: 11px 12px; border-bottom: 1px solid #1c3250; color: #9aacc1; font-size: 11px; font-weight: 500; text-align: left; }
    .student-profile-page .portfolio-table td { padding: 13px 12px; border-bottom: 1px solid #1a3049; color: #d9e5f1; font-size: 13px; vertical-align: middle; }
    .student-profile-page .portfolio-name { color: #eff6fc; font-weight: 500; }
    .student-profile-page .portfolio-description { display: block; margin-top: 4px; color: #91a5bb; font-size: 12px; }
    .student-profile-page .deployment-requirements { margin-bottom: 20px; padding: 16px; border: 1px solid #2a4566; border-radius: 10px; background: #0b1a2e; }
    .student-profile-page .deployment-requirement { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 12px; align-items: center; padding: 12px 0; border-bottom: 1px solid #1c3250; }
    .student-profile-page .deployment-requirement:last-child { border-bottom: 0; }
    .student-profile-page .deployment-requirement-name { color: #e6f0fa; font-weight: 500; }
    .student-profile-page .deployment-requirement-note { display: block; margin-top: 4px; color: #a9bad0; font-size: 12px; }
    .student-profile-page .deployment-requirement-status { display: inline-flex; border-radius: 999px; padding: 4px 9px; font-size: 12px; font-weight: 500; }
    .student-profile-page .deployment-requirement-status.missing { color: #e6edf5; background: rgba(142,163,187,.18); }
    .student-profile-page .deployment-requirement-status.submitted { color: #fbbf24; background: rgba(245,158,11,.15); }
    .student-profile-page .deployment-requirement-status.approved { color: #86efac; background: rgba(34,197,94,.15); }
    .student-profile-page .deployment-requirement-status.rejected { color: #fca5a5; background: rgba(239,68,68,.15); }
    .student-profile-page .deployment-requirement-upload { grid-column: 1 / -1; display: flex; flex-wrap: wrap; align-items: end; gap: 8px; }
    .student-profile-page .deployment-requirement-upload input[type="file"] { max-width: 100%; }
    .student-profile-page .status-badge { display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 4px; font-size: 11px; white-space: nowrap; }
    .student-profile-page .status-verified { background: #123b31; color: #6ee7b7; }
    .student-profile-page .status-pending { background: #493515; color: #f6c667; }
    .student-profile-page .status-rejected { background: #4a2228; color: #ff9a9a; }
    .student-profile-page .portfolio-actions { display: flex; align-items: center; justify-content: flex-end; gap: 8px; }
    .student-profile-page .icon-action { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border: 1px solid #2a4566; border-radius: 6px; background: transparent; color: #b9cce0; text-decoration: none; cursor: pointer; }
    .student-profile-page .icon-action:hover { border-color: #38bdf8; color: #38bdf8; }
    .student-profile-page .file-preview-modal { width: min(900px, calc(100% - 24px)); max-height: calc(100% - 24px); padding: 0; border: 1px solid #1c3250; border-radius: 8px; background: #0f2238; color: #e6f0fa; }
    .student-profile-page .file-preview-modal::backdrop { background: rgba(3, 10, 20, .78); }
    .student-profile-page .preview-heading { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; border-bottom: 1px solid #1c3250; }
    .student-profile-page .preview-heading h2 { margin: 0; font-size: 15px; font-weight: 500; }
    .student-profile-page .preview-close { border: 0; background: transparent; color: #dce9f5; font-size: 22px; cursor: pointer; }
    .student-profile-page .preview-content { display: grid; grid-template-columns: minmax(0, 1fr) 230px; gap: 18px; padding: 18px; }
    .student-profile-page .preview-frame { width: 100%; min-height: 440px; border: 1px solid #1c3250; border-radius: 6px; background: #0b1a2e; }
    .student-profile-page .preview-image { display: block; max-width: 100%; max-height: 60vh; margin: auto; object-fit: contain; }
    .student-profile-page .preview-meta { color: #a9b9ca; font-size: 12px; }
    .student-profile-page .preview-meta dt { margin-top: 12px; color: #91a5bb; }
    .student-profile-page .preview-meta dd { margin: 3px 0 0; color: #e6f0fa; overflow-wrap: anywhere; }
    .student-profile-page .empty-state { padding: 34px 16px; text-align: center; }
    .student-profile-page .empty-state h3 { margin: 0 0 7px; color: #e6f0fa; font-size: 15px; font-weight: 500; }
    .student-profile-page .empty-state p { margin: 0 0 15px; color: #91a5bb; font-size: 13px; }
    .student-profile-page .save-bar { position: sticky; bottom: 0; z-index: 5; display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 18px; padding: 12px 16px; border: 1px solid #1c3250; border-radius: 8px; background: #0f2238; }
    .student-profile-page .save-bar[hidden] { display: none !important; }
    .student-profile-page .dirty-indicator { display: flex; align-items: center; gap: 8px; color: #f3c56a; font-size: 12px; }
    .student-profile-page .dirty-dot { width: 7px; height: 7px; border-radius: 50%; background: #f3b941; }
    .student-profile-page .save-actions { display: flex; gap: 8px; }
    .student-profile-page .save-toast { position: fixed; right: 22px; bottom: 22px; z-index: 20; padding: 11px 16px; border: 1px solid #1c3250; border-radius: 6px; background: #0f2238; color: #e6f0fa; font-size: 13px; opacity: 0; transform: translateY(6px); transition: opacity .18s ease, transform .18s ease; pointer-events: none; }
    .student-profile-page .save-toast.is-visible { opacity: 1; transform: translateY(0); }
    .student-profile-page .visually-hidden { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
    @media (max-width: 991px) {
        .student-profile-page .profile-field-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .student-profile-page .stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 767px) {
        .student-profile-page { padding-bottom: 92px; }
        .student-profile-page .profile-header { align-items: stretch; flex-direction: column; padding: 17px; }
        .student-profile-page .completion { width: 100%; }
        .student-profile-page .resume-actions { flex-direction: column; }
        .student-profile-page .resume-actions > * { width: 100%; }
        .student-profile-page .resume-actions .btn-profile,
        .student-profile-page .resume-actions .btn-primary-profile { width: 100%; }
        .student-profile-page .tabs { gap: 18px; margin-top: 14px; }
        .student-profile-page .profile-panel { padding: 16px; }
        .student-profile-page .profile-field-grid { grid-template-columns: 1fr; }
        .student-profile-page .stat-grid { gap: 8px; }
        .student-profile-page .stat-card { min-height: 78px; padding: 11px; }
        .student-profile-page .portfolio-toolbar { align-items: stretch; flex-direction: column; }
        .student-profile-page .portfolio-toolbar .type-field { flex-basis: auto; }
        .student-profile-page .portfolio-toolbar .btn-primary-profile { width: 100%; }
        .student-profile-page .portfolio-table thead { display: none; }
        .student-profile-page .portfolio-table,
        .student-profile-page .portfolio-table tbody,
        .student-profile-page .portfolio-table tr,
        .student-profile-page .portfolio-table td { display: block; width: 100%; }
        .student-profile-page .portfolio-table tr { margin-bottom: 12px; border: 1px solid #1c3250; border-radius: 7px; }
        .student-profile-page .portfolio-table td { display: flex; justify-content: space-between; gap: 12px; padding: 10px; border-bottom: 1px solid #1a3049; text-align: right; }
        .student-profile-page .portfolio-table td:last-child { border-bottom: 0; }
        .student-profile-page .portfolio-table td::before { content: attr(data-label); flex: 0 0 auto; color: #91a5bb; font-size: 11px; text-align: left; }
        .student-profile-page .portfolio-table td:first-child { display: block; text-align: left; }
        .student-profile-page .portfolio-table td:first-child::before { display: block; margin-bottom: 6px; }
        .student-profile-page .save-bar { position: fixed; right: 0; bottom: 0; left: 0; margin: 0; padding: 11px 14px calc(11px + env(safe-area-inset-bottom)); border-width: 1px 0 0; border-radius: 0; }
        .student-profile-page .save-actions { flex: 1 1 auto; }
        .student-profile-page .save-actions .btn-profile,
        .student-profile-page .save-actions .btn-primary-profile { flex: 1 1 0; width: 100%; padding: 7px 10px; }
    }
    @media (max-width: 480px) {
        .student-profile-page .stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .student-profile-page .preview-content { grid-template-columns: 1fr; }
        .student-profile-page .preview-frame { min-height: 55vh; }
        .student-profile-page .identity { align-items: flex-start; }
        .student-profile-page .profile-name { font-size: 18px; }
        .student-profile-page .dirty-indicator { font-size: 11px; }
        .student-profile-page .save-bar { gap: 7px; }
        .student-profile-page .save-actions { gap: 6px; }
    }
</style>
@endpush

@section('content')
@php
    $personalFields = ['first_name', 'middle_name', 'last_name', 'suffix', 'gender', 'date_of_birth'];
    $contactFields = ['email', 'phone', 'address', 'city', 'province', 'zip_code'];
    $academicFields = ['student_number', 'program_id', 'major_id', 'department', 'year_level', 'section', 'school_year', 'semester', 'expected_graduation', 'enrollment_status', 'gwa', 'career_objectives', 'preferred_internship_field', 'preferred_work_setup', 'preferred_location'];
    $errorTab = $errors->hasAny($personalFields)
        ? 'personal'
        : ($errors->hasAny($contactFields) ? 'contact' : ($errors->hasAny($academicFields) ? 'academic' : null));
    $activeTab = $errorTab ?? 'personal';
    $profilePictureUrl = \App\Support\PublicFileUrl::relative($student->profile_picture);
    $studentInitials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->take(2)
        ->implode('');
    $studentInitials = strtoupper($studentInitials ?: 'U');
@endphp
<main class="student-profile-page" id="studentProfilePage">
    <header class="profile-header">
        <div class="identity">
            <div class="avatar-wrap">
                <div class="avatar">
                    @if($profilePictureUrl)
                        <img src="{{ $profilePictureUrl }}" alt="{{ $user->name }}" onerror="this.hidden=true;this.nextElementSibling.hidden=false;">
                        <span hidden>{{ $studentInitials }}</span>
                    @else
                        {{ $studentInitials }}
                    @endif
                </div>
                <button type="button" class="avatar-action" id="uploadPhotoButton">Upload photo</button>
                <form method="POST" action="{{ route('student.profile.update-picture') }}" enctype="multipart/form-data" class="visually-hidden" id="profilePictureForm">
                    @csrf
                    <input type="file" name="profile_picture" id="profilePictureInput" accept="image/jpeg,image/jpg,image/png">
                </form>
            </div>
            <div>
                <h1 class="profile-name" id="profileDisplayName">{{ $user->name }}</h1>
                <p class="profile-subline">Student · <span id="profileDisplayProgram">{{ $student->program ?: 'Program not set' }}</span></p>
                @error('profile_picture')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="completion">
            <div class="completion-line">
                <strong>Profile completion</strong>
                <span><span id="completionValue">{{ $profileCompletion['percentage'] }}</span>%</span>
            </div>
            <div class="completion-track" role="progressbar" aria-label="Profile completion" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $profileCompletion['percentage'] }}">
                <div class="completion-fill" id="completionFill" style="width: {{ $profileCompletion['percentage'] }}%"></div>
            </div>
            <details class="completion-steps" id="completionSteps" @if($profileCompletion['remaining_steps'] === 0) hidden @endif>
                <summary id="completionStepsSummary">{{ $profileCompletion['remaining_steps'] }} steps left</summary>
                <ul id="completionMissingSteps">
                    @foreach($profileCompletion['missing_steps'] as $step)
                        <li>{{ $step }}</li>
                    @endforeach
                </ul>
            </details>
            <div class="small text-muted mt-2">
                Internship hours:
                @if($requiredInternshipHours)
                    {{ number_format($renderedInternshipHours) }} / {{ number_format($requiredInternshipHours) }}
                @else
                    Program not set
                @endif
            </div>
            <div class="resume-actions">
                <a href="{{ route('student.resume.index') }}" class="btn-profile"><i class="bi bi-file-earmark-person" aria-hidden="true"></i> View resume</a>
                <form method="POST" action="{{ route('student.resume.generate') }}">
                    @csrf
                    <button type="submit" class="btn-primary-profile"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Generate resume</button>
                </form>
            </div>
        </div>
    </header>

    <div class="tabs" role="tablist" aria-label="Profile sections">
        <button type="button" class="tab {{ $errorTab === 'personal' ? 'has-error' : '' }}" id="tab-personal" role="tab" aria-controls="panel-personal" aria-selected="{{ $activeTab === 'personal' ? 'true' : 'false' }}" data-tab="personal" tabindex="{{ $activeTab === 'personal' ? '0' : '-1' }}">Personal <span class="tab-error-dot" aria-label="Has validation errors"></span></button>
        <button type="button" class="tab {{ $errorTab === 'contact' ? 'has-error' : '' }}" id="tab-contact" role="tab" aria-controls="panel-contact" aria-selected="{{ $activeTab === 'contact' ? 'true' : 'false' }}" data-tab="contact" tabindex="{{ $activeTab === 'contact' ? '0' : '-1' }}">Contact <span class="tab-error-dot" aria-label="Has validation errors"></span></button>
        <button type="button" class="tab {{ $errorTab === 'academic' ? 'has-error' : '' }}" id="tab-academic" role="tab" aria-controls="panel-academic" aria-selected="{{ $activeTab === 'academic' ? 'true' : 'false' }}" data-tab="academic" tabindex="{{ $activeTab === 'academic' ? '0' : '-1' }}">Academic <span class="tab-error-dot" aria-label="Has validation errors"></span></button>
        <button type="button" class="tab" id="tab-portfolio" role="tab" aria-controls="panel-portfolio" aria-selected="{{ $activeTab === 'portfolio' ? 'true' : 'false' }}" data-tab="portfolio" tabindex="{{ $activeTab === 'portfolio' ? '0' : '-1' }}">Portfolio <span class="tab-count" data-portfolio-count="{{ $portfolioStats['total'] }}">{{ $portfolioStats['total'] }}</span></button>
    </div>

    <form id="profileForm" method="POST" action="{{ route('student.profile.update') }}" novalidate>
        @csrf
        @method('PUT')
        <section class="profile-panel" id="panel-personal" role="tabpanel" aria-labelledby="tab-personal" tabindex="0" @if($activeTab !== 'personal') hidden @endif>
            <h2 class="panel-heading">Personal information</h2>
            <div class="profile-field-grid">
                <div>
                    <label class="form-label" for="first_name">First name</label>
                    <input id="first_name" type="text" class="form-control @error('first_name') is-invalid @enderror" name="first_name" value="{{ old('first_name', $student->first_name ?: (explode(' ', $user->name)[0] ?? '')) }}" required>
                    @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="middle_name">Middle name</label>
                    <input id="middle_name" type="text" class="form-control @error('middle_name') is-invalid @enderror" name="middle_name" value="{{ old('middle_name', $student->middle_name) }}">
                    @error('middle_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="last_name">Last name</label>
                    <input id="last_name" type="text" class="form-control @error('last_name') is-invalid @enderror" name="last_name" value="{{ old('last_name', $student->last_name ?: str($user->name)->afterLast(' ')->toString()) }}" required>
                    @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="suffix">Suffix</label>
                    @php($suffixValue = old('suffix', $student->suffix ?? ''))
                    <select id="suffix" class="form-select @error('suffix') is-invalid @enderror" name="suffix">
                        <option value="" {{ $suffixValue === '' ? 'selected' : '' }}>N/A</option>
                        @foreach(['Jr.', 'Sr.', 'II', 'III', 'IV', 'V'] as $suffix)
                            <option value="{{ $suffix }}" {{ $suffixValue === $suffix ? 'selected' : '' }}>{{ $suffix }}</option>
                        @endforeach
                    </select>
                    @error('suffix')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="gender">Gender</label>
                    <select id="gender" class="form-select @error('gender') is-invalid @enderror" name="gender">
                        <option value="">Select...</option>
                        @foreach(['Male', 'Female', 'Other', 'Prefer Not to Say'] as $gender)
                            <option value="{{ $gender }}" {{ old('gender', $student->gender) === $gender ? 'selected' : '' }}>{{ $gender }}</option>
                        @endforeach
                    </select>
                    @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="date_of_birth">Date of birth</label>
                    <input id="date_of_birth" type="date" class="form-control @error('date_of_birth') is-invalid @enderror" name="date_of_birth" value="{{ old('date_of_birth', $student->date_of_birth?->format('Y-m-d')) }}">
                    @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <section class="profile-panel" id="panel-contact" role="tabpanel" aria-labelledby="tab-contact" tabindex="0" @if($activeTab !== 'contact') hidden @endif>
            <h2 class="panel-heading">Contact information</h2>
            <div class="profile-field-grid">
                <div>
                    <label class="form-label" for="email">Email address</label>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $user->email) }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="phone">Mobile number</label>
                    <input id="phone" type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" placeholder="+63 900 000 0000" value="{{ old('phone', $user->phone) }}" required>
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="address">Address</label>
                    <input id="address" type="text" class="form-control @error('address') is-invalid @enderror" name="address" value="{{ old('address', $student->address) }}" required>
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="city">City</label>
                    <input id="city" type="text" class="form-control @error('city') is-invalid @enderror" name="city" value="{{ old('city', $student->city) }}" required>
                    @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="province">Province</label>
                    <input id="province" type="text" class="form-control @error('province') is-invalid @enderror" name="province" value="{{ old('province', $student->province) }}" required>
                    @error('province')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="zip_code">ZIP code</label>
                    <input id="zip_code" type="text" class="form-control @error('zip_code') is-invalid @enderror" name="zip_code" value="{{ old('zip_code', $student->zip_code) }}" required>
                    @error('zip_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <section class="profile-panel" id="panel-academic" role="tabpanel" aria-labelledby="tab-academic" tabindex="0" @if($activeTab !== 'academic') hidden @endif>
            <h2 class="panel-heading">Academic information</h2>
            @if($profileVerified)
                <div class="alert alert-info">
                    Your coordinator verified this profile. Student ID, program, and year level are locked. Use “Request change” if any of them need updating.
                </div>
            @endif
            <section class="card mb-4" aria-labelledby="internship-hours-heading">
                <div class="card-body">
                    <h3 class="h6" id="internship-hours-heading">Internship hours</h3>
                    @if($requiredInternshipHours)
                        <div class="d-flex justify-content-between small mb-2">
                            <span>Rendered hours</span>
                            <strong>{{ number_format($renderedInternshipHours) }} / {{ number_format($requiredInternshipHours) }} hours required</strong>
                        </div>
                        <div class="progress" role="progressbar" aria-label="Internship hours progress" aria-valuemin="0" aria-valuemax="{{ $requiredInternshipHours }}" aria-valuenow="{{ min($renderedInternshipHours, $requiredInternshipHours) }}">
                            <div class="progress-bar bg-info" style="width: {{ $internshipHoursProgress }}%"></div>
                        </div>
                    @else
                        <p class="text-muted mb-0">Set your program to see the required internship hours.</p>
                    @endif
                    <p class="small text-muted mt-2 mb-0">Rendered hours are maintained by your coordinator.</p>
                </div>
            </section>
            @if($profileVerified)
                <section class="card mb-4" aria-labelledby="change-requests-heading">
                    <div class="card-body">
                        <h3 class="h6" id="change-requests-heading">Profile change requests</h3>
                        @if($studentChangeRequests->isEmpty())
                            <p class="text-muted mb-0">You have not submitted any change requests.</p>
                        @else
                            <div class="list-group list-group-flush">
                                @foreach($studentChangeRequests as $changeRequest)
                                    <div class="list-group-item px-0">
                                        <div class="d-flex flex-wrap justify-content-between gap-2">
                                            <strong>{{ match($changeRequest->field) {'student_id_number' => 'Student ID', 'program_id' => 'Program', 'year_level' => 'Year level', default => 'Profile field'} }}</strong>
                                            <span class="badge text-bg-{{ $changeRequest->status === 'approved' ? 'success' : ($changeRequest->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($changeRequest->status) }}</span>
                                        </div>
                                        @if($changeRequest->field === 'program_id')
                                            <p class="small mb-1">{{ $programs->firstWhere('id', (int) $changeRequest->requested_value)?->display_name ?? 'Program no longer available' }}</p>
                                        @else
                                            <p class="small mb-1">{{ $changeRequest->requested_value }}</p>
                                        @endif
                                        @if($changeRequest->coordinator_note)
                                            <p class="small text-muted mb-0">Coordinator note: {{ $changeRequest->coordinator_note }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            @endif
            <div class="profile-field-grid">
                <div>
                    <label class="form-label" for="student_number">Student ID number</label>
                    <input id="student_number" type="text" class="form-control @error('student_number') is-invalid @enderror" name="student_number" value="{{ old('student_number', $student->student_id_number) }}" placeholder="SCC-21-00013240" autocomplete="off" data-student-id-pattern="{{ $studentIdPattern }}" @readonly($profileVerified)>
                    <small id="studentIdCorrectionNotice" class="form-text text-warning" @if(!$studentIdNeedsCorrection) hidden @endif>This ID doesn't match the SCC-YY-NNNNNNNN format. Enter your correct student ID.</small>
                    @if($profileVerified)<button type="button" class="btn btn-sm btn-outline-primary mt-2" data-request-change-field="student_id_number">Request change</button>@endif
                    @error('student_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="program_id">Program</label>
                    <select id="program_id" class="form-select @error('program_id') is-invalid @enderror" @unless($profileVerified) name="program_id" @endunless @disabled($profileVerified)>
                        <option value="">Select...</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}" data-coordinator="{{ $program->coordinator?->user?->name ?? '' }}" {{ (string) old('program_id', $profileVerified ? $student->program_id : $selectedProgramId) === (string) $program->id ? 'selected' : '' }}>{{ $program->display_name }}</option>
                        @endforeach
                    </select>
                    @if($profileVerified)
                        <input type="hidden" name="program_id" value="{{ $student->program_id }}">
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" data-request-change-field="program_id">Request change</button>
                    @endif
                    @error('program_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @error('program')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div id="majorField">
                    <label class="form-label" for="major_id">Major</label>
                    <select id="major_id" class="form-select @error('major_id') is-invalid @enderror" name="major_id">
                        <option value="">Select...</option>
                        @foreach($programs as $program)
                            @foreach($program->majors as $major)
                                <option value="{{ $major->id }}" data-program-id="{{ $program->id }}" {{ (string) old('major_id', $student->major_id) === (string) $major->id ? 'selected' : '' }}>{{ $major->name }}</option>
                            @endforeach
                        @endforeach
                    </select>
                    @error('major_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="department">Department</label>
                    <input id="department" type="text" class="form-control @error('department') is-invalid @enderror" name="department" value="{{ old('department', $student->department) }}">
                    @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="year_level">Year level</label>
                    <select id="year_level" class="form-select @error('year_level') is-invalid @enderror" @unless($profileVerified) name="year_level" @endunless @disabled($profileVerified)>
                        <option value="">Select...</option>
                        @foreach(['1st Year', '2nd Year', '3rd Year', '4th Year'] as $yearLevel)
                            <option value="{{ $yearLevel }}" {{ old('year_level', $student->year_level) === $yearLevel ? 'selected' : '' }}>{{ $yearLevel }}</option>
                        @endforeach
                    </select>
                    @if($profileVerified)
                        <input type="hidden" name="year_level" value="{{ $student->year_level }}">
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" data-request-change-field="year_level">Request change</button>
                    @endif
                    @error('year_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="section">Section</label>
                    <input id="section" type="text" class="form-control @error('section') is-invalid @enderror" name="section" value="{{ old('section', $student->section) }}">
                    @error('section')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="school_year">School year</label>
                    <select id="school_year" class="form-select @error('school_year') is-invalid @enderror" name="school_year">
                        <option value="">Select...</option>
                        @foreach($academicYears as $schoolYear)
                            <option value="{{ $schoolYear }}" {{ old('school_year', $student->school_year ?: $academicDefaults['school_year']) === $schoolYear ? 'selected' : '' }}>{{ $schoolYear }}</option>
                        @endforeach
                    </select>
                    @error('school_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="semester">Semester</label>
                    <select id="semester" class="form-select @error('semester') is-invalid @enderror" name="semester">
                        <option value="">Select...</option>
                        @foreach($semesterOptions as $semesterKey => $semesterLabel)
                            <option value="{{ $semesterKey }}" {{ old('semester', $student->semester ?: $academicDefaults['semester']) === $semesterKey ? 'selected' : '' }}>{{ $semesterLabel }}</option>
                        @endforeach
                    </select>
                    @error('semester')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="expected_graduation">Expected graduation</label>
                    <input id="expected_graduation" type="month" class="form-control @error('expected_graduation') is-invalid @enderror" name="expected_graduation" value="{{ old('expected_graduation', $student->expected_graduation?->format('Y-m')) }}">
                    @error('expected_graduation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="enrollment_status">Enrollment status</label>
                    <select id="enrollment_status" class="form-select @error('enrollment_status') is-invalid @enderror" name="enrollment_status">
                        <option value="">Select...</option>
                        @foreach(['Regular', 'Irregular'] as $enrollmentStatus)
                            <option value="{{ $enrollmentStatus }}" {{ old('enrollment_status', $student->enrollment_status) === $enrollmentStatus ? 'selected' : '' }}>{{ $enrollmentStatus }}</option>
                        @endforeach
                    </select>
                    @error('enrollment_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="gwa">GWA (optional)</label>
                    <input id="gwa" type="number" step="0.01" class="form-control @error('gwa') is-invalid @enderror" name="gwa" value="{{ old('gwa', $student->gwa) }}">
                    @error('gwa')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="assigned_coordinator">Assigned coordinator</label>
                    <input id="assigned_coordinator" type="text" class="form-control" value="{{ $programs->firstWhere('id', (int) old('program_id', $selectedProgramId))?->coordinator?->user?->name ?? 'Not assigned' }}" readonly>
                </div>
            </div>

            <hr class="section-divider">
            <h2 class="panel-heading">Career information</h2>
            <div class="profile-field-grid">
                <div class="field-wide">
                    <label class="form-label" for="career_objectives">Career objectives</label>
                    <textarea id="career_objectives" class="form-control @error('career_objectives') is-invalid @enderror" name="career_objectives" rows="3" placeholder="Describe your career goals and aspirations">{{ old('career_objectives', $student->career_objectives) }}</textarea>
                    @error('career_objectives')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="preferred_internship_field">Preferred internship field</label>
                    <input id="preferred_internship_field" type="text" class="form-control @error('preferred_internship_field') is-invalid @enderror" name="preferred_internship_field" value="{{ old('preferred_internship_field', $student->preferred_internship_field) }}">
                    @error('preferred_internship_field')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="preferred_work_setup">Work setup</label>
                    <select id="preferred_work_setup" class="form-select @error('preferred_work_setup') is-invalid @enderror" name="preferred_work_setup">
                        <option value="">Select...</option>
                        @foreach(['Remote', 'Hybrid', 'Onsite'] as $workSetup)
                            <option value="{{ $workSetup }}" {{ old('preferred_work_setup', $student->preferred_work_setup) === $workSetup ? 'selected' : '' }}>{{ $workSetup }}</option>
                        @endforeach
                    </select>
                    @error('preferred_work_setup')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="preferred_location">Preferred location</label>
                    <input id="preferred_location" type="text" class="form-control @error('preferred_location') is-invalid @enderror" name="preferred_location" value="{{ old('preferred_location', $student->preferred_location) }}">
                    @error('preferred_location')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>
    </form>

    <section class="profile-panel" id="panel-portfolio" role="tabpanel" aria-labelledby="tab-portfolio" tabindex="0" hidden>
        <div class="stat-grid">
            <article class="stat-card"><span class="stat-label">Total files</span><strong class="stat-value" data-total-files="{{ $portfolioStats['total'] }}">{{ $portfolioStats['total'] }}</strong></article>
            <article class="stat-card"><span class="stat-label">Certificates</span><strong class="stat-value" data-total-certificates="{{ $portfolioStats['certificates'] }}">{{ $portfolioStats['certificates'] }}</strong></article>
            <article class="stat-card"><span class="stat-label">Projects</span><strong class="stat-value">{{ $portfolioStats['projects'] }}</strong></article>
            <article class="stat-card"><span class="stat-label">Storage used</span><strong class="stat-value">{{ $portfolioStats['storage_usage'] }}</strong><span class="portfolio-description">10 MB limit</span></article>
        </div>

        <section class="deployment-requirements" aria-labelledby="deploymentRequirementsHeading">
            <h2 class="panel-heading" id="deploymentRequirementsHeading">Deployment requirements</h2>
            <p class="portfolio-description">Submit the required documents below. Rejected documents include the coordinator's note so you can correct and replace the file.</p>
            <?php if ($errors->has('file')): ?>
                <div class="invalid-feedback d-block mb-2">{{ $errors->first('file') }}</div>
            <?php endif; ?>
            <?php foreach ($deploymentRequirements as $deploymentDocument):
                $requirement = $deploymentDocument['requirement'];
                $document = $deploymentDocument['document'];
                $status = $deploymentDocument['status'];
                $statusClass = strtolower($status);
            ?>
                <div class="deployment-requirement">
                    <div>
                        <span class="deployment-requirement-name">{{ $requirement->name }}</span>
                        <span class="deployment-requirement-status {{ $statusClass }}">{{ $status }}</span>
                        <?php if ($status === 'Rejected' && filled($document?->review_notes)): ?>
                            <span class="deployment-requirement-note">Coordinator note: {{ $document->review_notes }}</span>
                        <?php elseif ($requirement->upload_source === 'coordinator'): ?>
                            <span class="deployment-requirement-note">Uploaded by your coordinator.</span>
                        <?php elseif ($requirement->upload_source === 'system'): ?>
                            <span class="deployment-requirement-note">Generated by the system.</span>
                        <?php elseif ($document && filled($document->file_path)): ?>
                            <span class="deployment-requirement-note">{{ basename($document->file_path) }}</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($requirement->upload_source === 'student'): ?>
                        <form class="deployment-requirement-upload" method="POST" enctype="multipart/form-data" action="{{ route('student.portfolio.deployment-requirements.store', $requirement) }}" data-ajax-form>
                            @csrf
                            <label class="visually-hidden" for="deployment-file-{{ $requirement->id }}">Upload {{ $requirement->name }}</label>
                            <input class="form-control" type="file" id="deployment-file-{{ $requirement->id }}" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                            <button class="btn-primary-profile" type="submit">{{ $status === 'Missing' ? 'Upload' : 'Replace file' }}</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>

        <div class="portfolio-toolbar">
            <div class="search-field">
                <label class="form-label" for="portfolioSearch">Search</label>
                <input class="form-control" id="portfolioSearch" type="search" value="{{ $portfolioFilters['search'] }}" placeholder="Search portfolio">
            </div>
            <div class="type-field">
                <label class="form-label" for="portfolioTypeFilter">File type</label>
                <select class="form-select" id="portfolioTypeFilter">
                    <option value="">All types</option>
                    <option value="certificate" {{ $portfolioFilters['type'] === 'certificate' ? 'selected' : '' }}>Certificates</option>
                    <option value="project" {{ $portfolioFilters['type'] === 'project' ? 'selected' : '' }}>Projects</option>
                    <option value="documents" {{ $portfolioFilters['type'] === 'documents' ? 'selected' : '' }}>Documents</option>
                </select>
            </div>
            <a href="{{ route('student.portfolio.create') }}" class="btn-primary-profile"><i class="bi bi-cloud-upload" aria-hidden="true"></i> Upload file</a>
        </div>

        @if($portfolioItems->isNotEmpty())
            <div class="table-responsive">
                <table class="portfolio-table">
                    <thead><tr><th scope="col">Name</th><th scope="col">Type</th><th scope="col">Status</th><th scope="col" class="text-end">Actions</th></tr></thead>
                    <tbody id="portfolioRows">
                @foreach($portfolioItems as $item)
                    <tr data-portfolio-row data-type="{{ $item['type_key'] }}" data-search="{{ $item['search'] }}">
                        <td data-label="Name">
                            <span class="portfolio-name">{{ $item['title'] }}</span>
                            @if($item['description'] !== '')
                                <span class="portfolio-description">{{ $item['description'] }}</span>
                            @endif
                        </td>
                        <td data-label="Type">{{ $item['type_label'] }}</td>
                        <td data-label="Status">
                            <span class="status-badge {{ $item['status_class'] }}">{{ $item['status_label'] }}</span>
                        </td>
                        <td data-label="Actions">
                            <div class="portfolio-actions">
                                @if($item['has_file'])
                                    <button type="button" class="icon-action" data-file-preview
                                        data-preview-url="{{ $item['preview_url'] }}"
                                        data-download-url="{{ $item['download_url'] }}"
                                        data-file-name="{{ $item['title'] }}"
                                        data-file-category="{{ $item['file_category'] }}"
                                        data-file-issuer="{{ $item['file_issuer'] }}"
                                        data-file-uploaded="{{ $item['uploaded_at'] }}"
                                        data-file-status="{{ $item['file_status'] }}"
                                        data-file-reviewer="{{ $item['reviewer'] }}"
                                        data-file-reviewed-at="{{ $item['reviewed_at'] }}"
                                        data-file-size="{{ $item['file_size_kb'] }} KB"
                                        data-file-extension="{{ $item['file_extension'] }}"
                                        aria-label="View {{ $item['title'] }}" title="View">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>
                                    <a class="icon-action" href="{{ $item['download_url'] }}" aria-label="Download {{ $item['title'] }}" title="Download">
                                        <i class="bi bi-download" aria-hidden="true"></i>
                                    </a>
                                @endif
                                @if($item['edit_url'])
                                    <a class="icon-action" href="{{ $item['edit_url'] }}" aria-label="Edit {{ $item['title'] }}" title="Edit"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                @endif
                                @if($item['can_delete'])
                                    <form method="POST" action="{{ $item['delete_url'] }}" onsubmit="return confirm(@js($item['delete_confirmation']));">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-action" aria-label="Delete {{ $item['title'] }}" title="Delete"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                    </form>
                                @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="empty-state" id="noPortfolioResults" hidden>No files match your search.</p>
        @else
            <div class="empty-state" id="portfolioEmptyState">
                <h3>Start your portfolio</h3>
                <p>Upload certificates, projects, and documents to showcase your experience.</p>
                <a href="{{ route('student.portfolio.create') }}" class="btn-primary-profile"><i class="bi bi-cloud-upload" aria-hidden="true"></i> Upload file</a>
            </div>
        @endif
    </section>

    @if($profileVerified)
        <dialog class="file-preview-modal" id="studentChangeRequestModal" aria-labelledby="studentChangeRequestTitle" @if($errors->has('requested_value') || $errors->has('field')) open @endif>
            <div class="preview-heading">
                <h2 id="studentChangeRequestTitle">Request a profile change</h2>
                <button type="button" class="preview-close" id="closeStudentChangeRequest" aria-label="Close request dialog">&times;</button>
            </div>
            <form method="POST" action="{{ route('student.profile.change-requests.store') }}" class="p-3">
                @csrf
                <input type="hidden" name="field" id="changeRequestField" value="{{ old('field') }}">
                <div id="changeRequestStudentIdField" hidden>
                    <label class="form-label" for="changeRequestStudentId">New student ID</label>
                    <input class="form-control" id="changeRequestStudentId" name="requested_value" value="{{ old('field') === 'student_id_number' ? old('requested_value') : '' }}" placeholder="SCC-21-00013240" disabled>
                </div>
                <div id="changeRequestProgramField" hidden>
                    <label class="form-label" for="changeRequestProgram">New program</label>
                    <select class="form-select" id="changeRequestProgram" name="requested_value" disabled>
                        <option value="">Select...</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}" @selected(old('field') === 'program_id' && (string) old('requested_value') === (string) $program->id)>{{ $program->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="changeRequestYearField" hidden>
                    <label class="form-label" for="changeRequestYear">New year level</label>
                    <select class="form-select" id="changeRequestYear" name="requested_value" disabled>
                        <option value="">Select...</option>
                        @foreach(['1st Year', '2nd Year', '3rd Year', '4th Year'] as $yearLevel)
                            <option value="{{ $yearLevel }}" @selected(old('field') === 'year_level' && old('requested_value') === $yearLevel)>{{ $yearLevel }}</option>
                        @endforeach
                    </select>
                </div>
                @error('field')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                @error('requested_value')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="button" class="btn-profile" id="cancelStudentChangeRequest">Cancel</button>
                    <button type="submit" class="btn-primary-profile">Send request</button>
                </div>
            </form>
        </dialog>
    @endif

    <dialog class="file-preview-modal" id="portfolioPreviewModal" aria-labelledby="portfolioPreviewTitle">
        <div class="preview-heading">
            <h2 id="portfolioPreviewTitle">File preview</h2>
            <button type="button" class="preview-close" id="closePortfolioPreview" aria-label="Close preview">&times;</button>
        </div>
        <div class="preview-content">
            <div id="portfolioPreviewPane"></div>
            <dl class="preview-meta">
                <dt>Category</dt><dd id="previewCategory"></dd>
                <dt>Issuer</dt><dd id="previewIssuer"></dd>
                <dt>Date uploaded</dt><dd id="previewUploaded"></dd>
                <dt>Status</dt><dd id="previewStatus"></dd>
                <dt>Reviewed by</dt><dd id="previewReviewer"></dd>
                <dt>Reviewed on</dt><dd id="previewReviewedAt"></dd>
                <dt>File size</dt><dd id="previewSize"></dd>
                <a class="btn-primary-profile mt-3" id="previewDownload" href>Download</a>
            </dl>
        </div>
    </dialog>

    <div class="save-bar" id="profileSaveBar">
        <div class="dirty-indicator" id="dirtyIndicator" hidden><span class="dirty-dot" aria-hidden="true"></span>Unsaved changes</div>
        <div class="save-actions">
            <button type="button" class="btn-profile" id="cancelProfileChanges">Cancel</button>
            <button type="submit" class="btn-primary-profile" form="profileForm">Save changes</button>
        </div>
    </div>
    <div class="save-toast" id="profileToast" role="status" aria-live="polite"></div>
</main>

@push('scripts')
<script>
(() => {
    const page = document.getElementById('studentProfilePage');
    const form = document.getElementById('profileForm');
    const saveButton = document.querySelector('#profileSaveBar button[type="submit"]');
    const tabs = Array.from(page.querySelectorAll('[role="tab"]'));
    const panels = Array.from(page.querySelectorAll('[role="tabpanel"]'));
    const saveBar = document.getElementById('profileSaveBar');
    const dirtyIndicator = document.getElementById('dirtyIndicator');
    const toast = document.getElementById('profileToast');
    const studentIdField = form.elements.namedItem('student_number');
    const studentIdCorrectionNotice = document.getElementById('studentIdCorrectionNotice');
    const errorFields = {
        personal: ['first_name', 'middle_name', 'last_name', 'suffix', 'gender', 'date_of_birth', 'profile_picture'],
        contact: ['email', 'phone', 'address', 'city', 'province', 'zip_code'],
        academic: ['student_number', 'program_id', 'major_id', 'department', 'year_level', 'section', 'school_year', 'semester', 'expected_graduation', 'enrollment_status', 'gwa', 'career_objectives', 'preferred_internship_field', 'preferred_work_setup', 'preferred_location']
    };
    let dirty = false;
    let toastTimer;

    const updateStudentIdCorrectionNotice = () => {
        const normalizedStudentId = studentIdField.value.trim().toUpperCase();
        try {
            const pattern = new RegExp(studentIdField.dataset.studentIdPattern, 'u');
            studentIdCorrectionNotice.hidden = normalizedStudentId === '' || pattern.test(normalizedStudentId);
        } catch {
            studentIdCorrectionNotice.hidden = true;
        }
    };
    studentIdField.addEventListener('input', updateStudentIdCorrectionNotice);
    updateStudentIdCorrectionNotice();

    const showToast = (message) => {
        toast.textContent = message;
        toast.classList.add('is-visible');
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => toast.classList.remove('is-visible'), 2600);
    };

    const activateTab = (name, updateHash = false) => {
        if (!tabs.some((tab) => tab.dataset.tab === name)) name = 'personal';
        tabs.forEach((tab) => {
            const selected = tab.dataset.tab === name;
            tab.setAttribute('aria-selected', String(selected));
            tab.tabIndex = selected ? 0 : -1;
        });
        panels.forEach((panel) => {
            panel.hidden = panel.id !== `panel-${name}`;
        });
        saveBar.hidden = name === 'portfolio';
        if (updateHash && window.location.hash !== `#${name}`) {
            window.location.hash = name;
        }
    };

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activateTab(tab.dataset.tab, true));
        tab.addEventListener('keydown', (event) => {
            let nextIndex = null;
            if (event.key === 'ArrowRight') nextIndex = (index + 1) % tabs.length;
            if (event.key === 'ArrowLeft') nextIndex = (index - 1 + tabs.length) % tabs.length;
            if (event.key === 'Home') nextIndex = 0;
            if (event.key === 'End') nextIndex = tabs.length - 1;
            if (nextIndex !== null) {
                event.preventDefault();
                tabs[nextIndex].focus();
                activateTab(tabs[nextIndex].dataset.tab, true);
            }
        });
    });

    const activateFromHash = () => activateTab(window.location.hash.slice(1) || 'personal');
    window.addEventListener('hashchange', activateFromHash);
    window.addEventListener('popstate', activateFromHash);
    activateFromHash();

    const updateDirtyState = () => {
        dirtyIndicator.hidden = !dirty;
    };
    form.addEventListener('input', (event) => {
        if (event.target.name) {
            dirty = true;
            updateDirtyState();
        }
    });
    form.addEventListener('change', (event) => {
        if (event.target.name) {
            dirty = true;
            updateDirtyState();
        }
    });
    window.addEventListener('beforeunload', (event) => {
        if (dirty) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    const clearErrors = () => {
        form.querySelectorAll('.is-invalid').forEach((field) => field.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback[data-client-error]').forEach((node) => node.remove());
        tabs.forEach((tab) => tab.classList.remove('has-error'));
    };
    const showErrors = (errors) => {
        clearErrors();
        let firstTab = null;
        Object.entries(errors).forEach(([name, messages]) => {
            const field = form.elements.namedItem(name);
            if (field && 'classList' in field) {
                field.classList.add('is-invalid');
                const error = document.createElement('div');
                error.className = 'invalid-feedback';
                error.dataset.clientError = 'true';
                error.textContent = messages[0];
                field.insertAdjacentElement('afterend', error);
            }
            const tabName = Object.keys(errorFields).find((key) => errorFields[key].includes(name));
            if (tabName) {
                page.querySelector(`#tab-${tabName}`).classList.add('has-error');
                firstTab ||= tabName;
            }
        });
        if (firstTab) activateTab(firstTab, true);
    };

    const updateDefaults = () => {
        Array.from(form.elements).forEach((field) => {
            if (field instanceof HTMLSelectElement) {
                Array.from(field.options).forEach((option) => { option.defaultSelected = option.selected; });
            } else if (field instanceof HTMLTextAreaElement) {
                field.defaultValue = field.value;
            } else if (field instanceof HTMLInputElement && !['hidden', 'submit', 'button'].includes(field.type)) {
                field.defaultValue = field.value;
            }
        });
    };

    document.getElementById('cancelProfileChanges').addEventListener('click', () => {
        form.reset();
        syncProgramFields();
        updateStudentIdCorrectionNotice();
        clearErrors();
        dirty = false;
        updateDirtyState();
    });

    const changeRequestModal = document.getElementById('studentChangeRequestModal');
    if (changeRequestModal) {
        const changeRequestField = document.getElementById('changeRequestField');
        const changeRequestInputs = {
            student_id_number: document.getElementById('changeRequestStudentId'),
            program_id: document.getElementById('changeRequestProgram'),
            year_level: document.getElementById('changeRequestYear')
        };
        const changeRequestGroups = {
            student_id_number: document.getElementById('changeRequestStudentIdField'),
            program_id: document.getElementById('changeRequestProgramField'),
            year_level: document.getElementById('changeRequestYearField')
        };
        const setChangeRequestField = (field) => {
            changeRequestField.value = field;
            Object.entries(changeRequestInputs).forEach(([name, input]) => {
                const active = name === field;
                input.disabled = !active;
                changeRequestGroups[name].hidden = !active;
            });
        };
        document.querySelectorAll('[data-request-change-field]').forEach((button) => {
            button.addEventListener('click', () => {
                setChangeRequestField(button.dataset.requestChangeField);
                changeRequestModal.showModal();
            });
        });
        document.getElementById('closeStudentChangeRequest').addEventListener('click', () => changeRequestModal.close());
        document.getElementById('cancelStudentChangeRequest').addEventListener('click', () => changeRequestModal.close());
        if (changeRequestField.value) setChangeRequestField(changeRequestField.value);
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const originalSaveLabel = saveButton.textContent;
        saveButton.disabled = true;
        saveButton.setAttribute('aria-busy', 'true');
        saveButton.textContent = 'Saving…';
        clearErrors();
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const payload = await response.json();
            if (response.status === 422 && payload.errors) {
                showErrors(payload.errors);
                return;
            }
            if (!response.ok) {
                throw new Error(payload.message || 'Unable to save profile.');
            }
            studentIdField.value = payload.student_id_number || '';
            studentIdCorrectionNotice.hidden = !payload.student_id_needs_correction;
            dirty = false;
            updateDirtyState();
            updateDefaults();
            document.getElementById('completionValue').textContent = payload.profile_completion;
            document.getElementById('completionFill').style.width = `${payload.profile_completion}%`;
            page.querySelector('[role="progressbar"]').setAttribute('aria-valuenow', payload.profile_completion);
            const missingSteps = payload.missing_steps || [];
            const completionSteps = document.getElementById('completionSteps');
            completionSteps.hidden = missingSteps.length === 0;
            document.getElementById('completionStepsSummary').textContent = missingSteps.length
                ? `${payload.remaining_steps} steps left`
                : 'Profile complete';
            document.getElementById('completionMissingSteps').replaceChildren(
                ...missingSteps.map((step) => {
                    const item = document.createElement('li');
                    item.textContent = step;
                    return item;
                })
            );
            const fullName = ['first_name', 'middle_name', 'last_name', 'suffix']
                .map((name) => form.elements.namedItem(name).value.trim())
                .filter(Boolean).join(' ');
            document.getElementById('profileDisplayName').textContent = fullName;
            const selectedProgram = document.getElementById('program_id').selectedOptions[0];
            document.getElementById('profileDisplayProgram').textContent = selectedProgram?.value
                ? selectedProgram.textContent.trim()
                : 'Program not set';
            showToast('Profile saved');
        } catch (error) {
            showToast(error.message || 'Unable to save profile.');
        } finally {
            saveButton.disabled = false;
            saveButton.removeAttribute('aria-busy');
            saveButton.textContent = originalSaveLabel;
        }
    });

    const photoInput = document.getElementById('profilePictureInput');
    document.getElementById('uploadPhotoButton').addEventListener('click', () => photoInput.click());
    photoInput.addEventListener('change', () => {
        if (!photoInput.files.length) return;
        const photoForm = document.getElementById('profilePictureForm');
        const uploadButton = document.getElementById('uploadPhotoButton');
        const originalLabel = uploadButton.textContent;
        uploadButton.disabled = true;
        uploadButton.textContent = 'Uploading…';

        fetch(photoForm.action, {
            method: 'POST',
            body: new FormData(photoForm),
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(async (response) => {
            const payload = await response.json();
            if (!response.ok) {
                const firstError = payload.errors ? Object.values(payload.errors).flat()[0] : null;
                throw new Error(firstError || payload.message || 'Unable to upload profile photo.');
            }

            const avatar = page.querySelector('.avatar');
            const image = document.createElement('img');
            image.src = payload.profile_picture_url;
            image.alt = document.getElementById('profileDisplayName').textContent;
            avatar.replaceChildren(image);
            showToast(payload.message || 'Profile picture updated.');
        }).catch((error) => {
            showToast(error.message || 'Unable to upload profile photo.');
        }).finally(() => {
            uploadButton.disabled = false;
            uploadButton.textContent = originalLabel;
            photoInput.value = '';
        });
    });

    const programSelect = document.getElementById('program_id');
    const majorSelect = document.getElementById('major_id');
    const majorField = document.getElementById('majorField');
    const coordinatorField = document.getElementById('assigned_coordinator');
    const syncProgramFields = () => {
        const selectedId = programSelect.value;
        const selectedOption = programSelect.selectedOptions[0];
        const majorOptions = Array.from(majorSelect.options).filter((option) => option.dataset.programId);
        const hasMajors = majorOptions.some((option) => option.dataset.programId === selectedId);

        majorOptions.forEach((option) => {
            const belongs = option.dataset.programId === selectedId;
            option.hidden = !belongs;
            option.disabled = !belongs;
        });
        majorField.hidden = !hasMajors;

        const selectedMajor = majorSelect.selectedOptions[0];
        if (selectedMajor?.dataset.programId !== selectedId) majorSelect.value = '';
        coordinatorField.value = selectedOption?.dataset.coordinator || 'Not assigned';
    };
    programSelect.addEventListener('change', syncProgramFields);
    syncProgramFields();

    const search = document.getElementById('portfolioSearch');
    const typeFilter = document.getElementById('portfolioTypeFilter');
    const rows = Array.from(page.querySelectorAll('[data-portfolio-row]'));
    const noResults = document.getElementById('noPortfolioResults');
    const filterPortfolio = () => {
        if (!rows.length) return;
        const query = search.value.trim().toLocaleLowerCase();
        const type = typeFilter.value;
        let visibleCount = 0;
        rows.forEach((row) => {
            const matchesSearch = row.dataset.search.includes(query);
            const matchesType = !type || (type === 'documents'
                ? !['certificate', 'project'].includes(row.dataset.type)
                : row.dataset.type === type);
            row.hidden = !(matchesSearch && matchesType);
            if (!row.hidden) visibleCount += 1;
        });
        noResults.hidden = visibleCount > 0;
    };
    if (search && typeFilter) {
        search.addEventListener('input', filterPortfolio);
        typeFilter.addEventListener('change', filterPortfolio);
        filterPortfolio();
    }

    const previewModal = document.getElementById('portfolioPreviewModal');
    const previewPane = document.getElementById('portfolioPreviewPane');
    const clearPreview = () => {
        previewPane.replaceChildren();
    };
    const showPreviewMessage = (message) => {
        const notice = document.createElement('p');
        notice.className = 'portfolio-description';
        notice.textContent = message;
        previewPane.replaceChildren(notice);
    };
    const showUnsupportedPreview = (data, mimeType) => {
        const details = document.createElement('div');
        details.className = 'portfolio-description';
        const fileName = document.createElement('p');
        fileName.textContent = data.fileName;
        const fileType = document.createElement('p');
        fileType.textContent = `Type: ${mimeType || 'Unknown'}`;
        const fileSize = document.createElement('p');
        fileSize.textContent = `Size: ${data.fileSize}`;
        const download = document.createElement('a');
        download.className = 'btn-primary-profile';
        download.href = data.downloadUrl;
        download.textContent = 'Download';
        details.append(fileName, fileType, fileSize, download);
        previewPane.replaceChildren(details);
    };
    page.querySelectorAll('[data-file-preview]').forEach((button) => {
        button.addEventListener('click', () => {
            const data = button.dataset;
            clearPreview();
            document.getElementById('portfolioPreviewTitle').textContent = data.fileName;
            document.getElementById('previewCategory').textContent = data.fileCategory;
            document.getElementById('previewIssuer').textContent = data.fileIssuer || 'Not provided';
            document.getElementById('previewUploaded').textContent = data.fileUploaded;
            document.getElementById('previewStatus').textContent = data.fileStatus;
            document.getElementById('previewReviewer').textContent = data.fileReviewer;
            document.getElementById('previewReviewedAt').textContent = data.fileReviewedAt;
            document.getElementById('previewSize').textContent = data.fileSize;
            document.getElementById('previewDownload').href = data.downloadUrl;
            previewModal.showModal();

            if (data.fileExtension === 'pdf') {
                const frame = document.createElement('iframe');
                frame.className = 'preview-frame';
                frame.title = `Preview of ${data.fileName}`;
                frame.src = data.previewUrl;
                frame.addEventListener('error', () => {
                    showPreviewMessage('Preview not available. The file could not be loaded.');
                }, { once: true });
                previewPane.append(frame);
            } else if (['jpg', 'jpeg', 'png'].includes(data.fileExtension)) {
                const image = document.createElement('img');
                image.className = 'preview-image preview-frame';
                image.alt = data.fileName;
                image.addEventListener('error', () => {
                    showPreviewMessage('Preview not available. The file could not be loaded.');
                }, { once: true });
                image.src = data.previewUrl;
                previewPane.append(image);
            } else {
                showUnsupportedPreview(data, data.fileExtension ? `.${data.fileExtension}` : 'Unknown');
            }
        });
    });
    document.getElementById('closePortfolioPreview').addEventListener('click', () => previewModal.close());
    previewModal.addEventListener('close', clearPreview);
    previewModal.addEventListener('click', (event) => {
        if (event.target === previewModal) previewModal.close();
    });

    const initialErrorTab = @json($errorTab);
    if (initialErrorTab) {
        const invalidFields = @json($errors->keys());
        invalidFields.forEach((name) => {
            const tabName = Object.keys(errorFields).find((key) => errorFields[key].includes(name));
            if (tabName) page.querySelector(`#tab-${tabName}`).classList.add('has-error');
        });
        activateTab(initialErrorTab);
    }
    @if(session('success') === 'Profile saved')
        showToast('Profile saved');
    @endif
})();
</script>
@endpush
@endsection
