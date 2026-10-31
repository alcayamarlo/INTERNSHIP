@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
@php
    $tabErrorFields = [
        'personal' => ['first_name', 'middle_name', 'last_name', 'suffix', 'gender', 'date_of_birth', 'place_of_birth', 'citizenship', 'languages', 'civil_status', 'religion', 'height_cm', 'weight_kg', 'profile_picture'],
        'contact' => ['email', 'phone', 'address', 'city', 'province', 'zip_code', 'provincial_address', 'linkedin_url', 'facebook_url', 'github_url', 'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_address', 'emergency_contact_phone'],
        'academic' => ['student_number', 'program_id', 'major_id', 'department', 'year_level', 'section', 'school_year', 'semester', 'expected_graduation', 'enrollment_status', 'gwa', 'elementary_school', 'elementary_year_graduated', 'high_school', 'high_school_year_graduated', 'career_objectives', 'preferred_internship_field', 'preferred_work_setup', 'preferred_location'],
        'portfolio' => ['title', 'type', 'tools_used', 'project_url', 'achievements'],
        'documents' => ['file', 'issuer', 'issue_date', 'expiration_date'],
    ];
    $errorTab = collect($tabErrorFields)->search(fn (array $fields) => $errors->hasAny($fields)) ?: null;
    $errorTab ??= $errors->skills->any() ? 'skills' : ($errors->experience->any() ? 'experience' : ($errors->references->any() ? 'references' : ($errors->awards->any() ? 'academic' : null)));
    $activeTab = $errorTab ?? 'personal';
    $completedSections = collect($profileCompletion['sections'])->filter(fn ($section) => $section['complete'])->keys();
    $tabs = [
        'personal' => 'Personal',
        'contact' => 'Contact',
        'academic' => 'Academic',
        'skills' => 'Skills',
        'experience' => 'Experience',
        'portfolio' => 'Portfolio',
        'references' => 'Character Reference',
        'documents' => 'Documents',
    ];
    $profileAvatar = \App\Support\Avatar::for($user);
@endphp
<main class="student-profile-page" id="studentProfilePage"
    data-error-tab="{{ $errorTab }}"
    data-error-fields="{{ json_encode($errors->keys()) }}"
    data-tab-error-fields="{{ json_encode($tabErrorFields) }}"
    data-flash-success="{{ session('success') }}">
    <header class="profile-header">
        <div class="identity">
            <div class="avatar-wrap">
                <div class="avatar" data-avatar>
                    <x-avatar :avatar="$profileAvatar" :alt="$user->name" />
                </div>
                <button type="button" class="avatar-action" data-photo-pick>{{ $profileAvatar['url'] ? 'Change photo' : 'Upload photo' }}</button>
                <form method="POST" action="{{ route('student.profile.update-picture') }}" enctype="multipart/form-data" class="visually-hidden" id="profilePictureForm">
                    @csrf
                    <input type="file" name="profile_picture" id="profilePictureInput" accept="image/jpeg,image/png,image/webp" aria-label="Profile photo">
                </form>
                <form method="POST" action="{{ route('student.profile.delete-picture') }}" class="visually-hidden" id="profilePictureRemoveForm">
                    @csrf
                    @method('DELETE')
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
                <summary id="completionStepsSummary">{{ $profileCompletion['remaining_steps'] }} {{ \Illuminate\Support\Str::plural('step', $profileCompletion['remaining_steps']) }} left</summary>
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
                    <button type="submit" class="btn-primary-profile" title="Only your 3 latest resumes are kept. Generating a new one removes the oldest."><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Generate resume</button>
                </form>
            </div>
        </div>
    </header>

    @unless($hasPrivacyConsent)
        <section class="privacy-consent-card" id="privacyConsentCard" aria-labelledby="privacyConsentHeading">
            <h2 class="panel-heading" id="privacyConsentHeading"><i class="bi bi-shield-lock" aria-hidden="true"></i> Your privacy</h2>
            <p class="portfolio-description">Before you save your profile, let us know you agree to how SkillBridge uses your information. Sensitive details stay hidden from companies unless you choose to show them.</p>
            <form method="POST" action="{{ route('student.profile.consent') }}" id="privacyConsentForm" novalidate>
                @csrf
                <div class="form-check">
                    <input class="form-check-input @error('privacy_consent') is-invalid @enderror" type="checkbox" name="privacy_consent" value="1" id="privacyConsentCheckbox" required>
                    <label class="form-check-label" for="privacyConsentCheckbox">{{ $privacyStatement }}</label>
                </div>
                <div class="invalid-feedback d-block" id="privacyConsentError">@error('privacy_consent'){{ $message }}@enderror</div>
                <button type="submit" class="btn-primary-profile mt-2">I agree</button>
            </form>
        </section>
    @endunless

    <div class="tabs" role="tablist" aria-label="Profile sections">
        @foreach($tabs as $key => $label)
            <button type="button" class="tab {{ $errorTab === $key ? 'has-error' : '' }} {{ $completedSections->contains($key) ? 'is-complete' : '' }}" id="tab-{{ $key }}" role="tab" aria-controls="panel-{{ $key }}" aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}" data-tab="{{ $key }}" tabindex="{{ $activeTab === $key ? '0' : '-1' }}">
                {{ $label }}
                @if($key === 'experience')<span class="tab-optional">Optional</span>@endif
                <i class="bi bi-check-circle-fill tab-complete-icon" aria-hidden="true"></i>
                <span class="visually-hidden tab-complete-text">(complete)</span>
                <span class="tab-error-dot" aria-label="Has validation errors"></span>
            </button>
        @endforeach
    </div>

    @include('student.profile.tabs.personal')
    @include('student.profile.tabs.contact')
    @include('student.profile.tabs.academic')
    @include('student.profile.tabs.skills')
    @include('student.profile.tabs.experience')
    @include('student.profile.tabs.portfolio')
    @include('student.profile.tabs.references')
    @include('student.profile.tabs.documents')

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

    <div class="save-toast" id="profileToast" role="status" aria-live="polite"></div>
</main>

@push('scripts')
<script src="{{ asset('js/student-profile.js') }}?v=1" defer></script>
@endpush
@endsection
