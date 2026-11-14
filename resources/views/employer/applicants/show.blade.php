@extends('layouts.app')

@section('title', $profile['name'])

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/employer-applicant-review.css') }}?v={{ @filemtime(public_path('css/employer-applicant-review.css')) }}">
@endpush

@section('content')
@php
    $hasSkillsTab = (bool) ($profile['competencies'] || $profile['certificates'] || $profile['skills']);
    $hasExperienceTab = (bool) ($profile['experiences'] || $profile['references']);
    $hasFilesTab = (bool) ($profile['projects'] || $profile['documents']);
@endphp

<div class="ar-wrap">
<x-page-header :title="$profile['name']" :subtitle="trim(($profile['program'] ?? '').($profile['year_level'] ? ' — '.$profile['year_level'] : ''))">
    <a href="{{ route('employer.applicants.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Applicants</a>
    @if($application->student?->user)
        @include('partials.report-button', ['type' => 'user', 'id' => $application->student->user_id, 'label' => $profile['name']])
    @endif
</x-page-header>

<div class="ar" data-refresh-on-focus>

    {{-- ============ Main: summary strip + tabs ============ --}}
    <div class="ar-main">

        <div class="card ar-summary">
            <div class="ar-sum-item ar-sum-wide">
                <span class="ar-sum-label">Applied for</span>
                <strong>{{ $application->internship->title }}</strong>
            </div>
            <div class="ar-sum-item">
                <span class="ar-sum-label">Match score</span>
                <span class="sb-tag sb-tag--brand">{{ $application->match_percentage }}%</span>
            </div>
            <div class="ar-sum-item">
                <span class="ar-sum-label">Applied</span>
                <strong>{{ $application->applied_at?->format('M d, Y h:i A') }}</strong>
            </div>
            <div class="ar-sum-item">
                <span class="ar-sum-label">Status</span>
                {{-- Status badge is rewritten by resources/js/app.js after an ajax status update. --}}
                <span class="badge {{ $application->status->badgeClass() }}" data-application-status>{{ $application->status->label() }}</span>
            </div>
        </div>

        <div class="card ar-tabs-card">
            <div class="ar-tabs" role="tablist" aria-label="Applicant details">
                <button type="button" class="ar-tab is-active" role="tab" id="arTabOverview" aria-controls="arPanelOverview" aria-selected="true" data-ar-tab="overview">Overview</button>
                @if($hasSkillsTab)<button type="button" class="ar-tab" role="tab" id="arTabSkills" aria-controls="arPanelSkills" aria-selected="false" tabindex="-1" data-ar-tab="skills">Skills &amp; certificates</button>@endif
                @if($hasExperienceTab)<button type="button" class="ar-tab" role="tab" id="arTabExperience" aria-controls="arPanelExperience" aria-selected="false" tabindex="-1" data-ar-tab="experience">Experience &amp; references</button>@endif
                @if($hasFilesTab)<button type="button" class="ar-tab" role="tab" id="arTabFiles" aria-controls="arPanelFiles" aria-selected="false" tabindex="-1" data-ar-tab="files">Projects &amp; documents</button>@endif
            </div>

            {{-- Overview: bio-data in columns so it fits one screen --}}
            <div class="ar-panel" role="tabpanel" id="arPanelOverview" aria-labelledby="arTabOverview" data-ar-panel="overview">
                <div class="ar-cols">
                    @if($profile['contact'])
                        @include('employer.applicants.partials.info-group', ['heading' => 'Contact', 'rows' => $profile['contact']])
                    @else
                        <section class="ar-group"><h2>Contact</h2><p class="ar-note"><i class="bi bi-eye-slash" aria-hidden="true"></i> Hidden because this application was withdrawn.</p></section>
                    @endif
                    @include('employer.applicants.partials.info-group', ['heading' => 'Personal', 'rows' => $profile['personal']])
                    @include('employer.applicants.partials.info-group', ['heading' => 'Academic', 'rows' => $profile['academic']])
                    @include('employer.applicants.partials.info-group', ['heading' => 'Emergency contact', 'rows' => $profile['emergency_contact'] ?? []])
                </div>

                @if($profile['awards'])
                    <section class="ar-group ar-block">
                        <h2>Honors / awards</h2>
                        <dl class="ar-awards">
                            @foreach($profile['awards'] as $group)
                                <div>
                                    <dt>{{ $group['label'] }}</dt>
                                    <dd>
                                        <ul>
                                            @foreach($group['awards'] as $award)
                                                <li>{!! nl2br(e($award['title'])) !!}@if($award['meta'] !== '')<span class="ar-meta">{{ $award['meta'] }}</span>@endif</li>
                                            @endforeach
                                        </ul>
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                @endif

                @if($profile['social_links'])
                    <section class="ar-group ar-block">
                        <h2>Links</h2>
                        <ul class="ar-links">
                            @foreach($profile['social_links'] as $label => $url)
                                <li><a href="{{ $url }}" target="_blank" rel="noopener noreferrer nofollow">{{ $label }}</a></li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if($profile['career_objectives'])
                    <section class="ar-group ar-block">
                        <h2>Career objectives</h2>
                        <p>{{ $profile['career_objectives'] }}</p>
                    </section>
                @endif

                @if($application->cover_letter)
                    <section class="ar-group ar-block">
                        <h2>Cover letter</h2>
                        <p class="sb-tile">{!! nl2br(e($application->cover_letter)) !!}</p>
                    </section>
                @endif
            </div>

            @if($hasSkillsTab)
            <div class="ar-panel" role="tabpanel" id="arPanelSkills" aria-labelledby="arTabSkills" data-ar-panel="skills" hidden>
                @if($profile['competencies'])
                    <section class="ar-group ar-block">
                        <h2>Certificates</h2>
                        <div class="ar-certs">
                            @foreach($profile['competencies'] as $certificate)
                                <div class="sb-tile">
                                    <strong>{{ $certificate['name'] }}</strong>
                                    @if($certificate['issuer'])<small class="text-muted d-block">{{ $certificate['issuer'] }}</small>@endif
                                    <div class="ar-tags mt-2">
                                        @if($certificate['type'])<span class="sb-tag">{{ $certificate['type'] }}</span>@endif
                                        @if($certificate['skill_type'])<span class="sb-tag">{{ $certificate['skill_type'] }}</span>@endif
                                        @if($certificate['level'])<span class="sb-tag sb-tag--brand">{{ $certificate['level'] }}</span>@endif
                                    </div>
                                    @if($certificate['skills'])
                                        <small class="d-block mt-2">Skills learned: {{ implode(', ', $certificate['skills']) }}</small>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
                @if($profile['certificates'])
                    <section class="ar-group ar-block">
                        <h2>Older certificate files</h2>
                        <ul class="ar-files">
                            @foreach($profile['certificates'] as $file)
                                @include('employer.applicants.partials.file-row', ['fileCategory' => 'Certificate'])
                            @endforeach
                        </ul>
                    </section>
                @endif
                @if($profile['skills'])
                    <section class="ar-group ar-block">
                        <h2>Skills</h2>
                        <p class="ar-note">Self-declared by the student. Not part of the match score.</p>
                        <div class="ar-tags">
                            @foreach($profile['skills'] as $skill)
                                <span class="sb-tag sb-tag--brand">{{ $skill['name'] }}@if($skill['level']) · {{ $skill['level'] }}@endif @if($skill['has_certificate'])<i class="bi bi-file-earmark-check" aria-hidden="true"></i><span class="visually-hidden">(has certificate)</span>@endif</span>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>
            @endif

            @if($hasExperienceTab)
            <div class="ar-panel" role="tabpanel" id="arPanelExperience" aria-labelledby="arTabExperience" data-ar-panel="experience" hidden>
                @if($profile['experiences'])
                    <section class="ar-group ar-block">
                        <h2>Experience</h2>
                        <ul class="ar-list">
                            @foreach($profile['experiences'] as $experience)
                                <li>
                                    <strong>{{ $experience['position'] }}</strong> · {{ $experience['organization'] }}
                                    <small class="text-muted d-block">{{ $experience['period'] }}</small>
                                    @if($experience['description'])<small class="d-block">{{ $experience['description'] }}</small>@endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
                @if($profile['references'])
                    <section class="ar-group ar-block">
                        <h2>Character references</h2>
                        <ul class="ar-list">
                            @foreach($profile['references'] as $reference)
                                <li>
                                    <strong>{{ $reference['name'] }}</strong>
                                    <small class="text-muted d-block">{{ $reference['position'] }} · {{ $reference['organization'] }} · {{ $reference['contact_number'] }}</small>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>
            @endif

            @if($hasFilesTab)
            <div class="ar-panel" role="tabpanel" id="arPanelFiles" aria-labelledby="arTabFiles" data-ar-panel="files" hidden>
                @foreach(['projects' => 'Projects', 'documents' => 'Documents'] as $fileGroup => $fileGroupLabel)
                    @if($profile[$fileGroup])
                        <section class="ar-group ar-block">
                            <h2>{{ $fileGroupLabel }}</h2>
                            <ul class="ar-files">
                                @foreach($profile[$fileGroup] as $file)
                                    @include('employer.applicants.partials.file-row', ['fileCategory' => \Illuminate\Support\Str::singular($fileGroupLabel)])
                                @endforeach
                            </ul>
                        </section>
                    @endif
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- ============ Aside: actions + status (always visible) ============ --}}
    <div class="ar-aside">
        @if(in_array($application->status, [\App\Enums\ApplicationStatus::Accepted, \App\Enums\ApplicationStatus::Completed], true))
            @include('employer.partials.coordinator-contact', ['student' => $application->student])
        @endif

        <div class="card">
            <div class="card-body ar-actions">
                @if($applicationResume)
                    <button type="button" class="btn btn-outline-primary employer-file-preview"
                        data-preview-url="{{ route('employer.applicants.resume.preview', $application, false) }}"
                        data-download-url="{{ route('employer.applicants.resume', $application, false) }}"
                        data-file-name="Resume" data-file-category="Resume" data-file-issuer=""
                        data-file-uploaded="{{ $applicationResume->generated_at?->format('M d, Y') ?? 'Unknown' }}"
                        data-file-status="Available" data-file-reviewer="{{ $applicationResume->reviewerUser?->name ?? 'Not reviewed' }}"
                        data-file-reviewed-at="{{ $applicationResume->reviewed_at?->format('M d, Y') ?? 'Not reviewed' }}"
                        data-file-size="{{ $applicationResume->file_size_display }}" data-file-extension="pdf">
                        <i class="bi bi-eye" aria-hidden="true"></i> View resume
                    </button>
                    <a href="{{ route('employer.applicants.resume', $application, false) }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Download resume</a>
                @endif
                @can('message.send', $application->student->user)
                    <a href="{{ route('messages.show', $application->student->user) }}" class="btn btn-outline-secondary ar-wide"><i class="bi bi-chat" aria-hidden="true"></i> Message Student</a>
                @else
                    <small class="text-muted ar-wide">Mark the application Reviewed to message the student.</small>
                @endcan
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Update Status</h2></div>
            <div class="card-body">
                {{-- The student already has an ongoing OJT from another application, so "Accepted" is not offered. --}}
                @if($placedElsewhere)
                    <div class="alert alert-warning small" role="status">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        This student already has an ongoing OJT placement with another company, so you can't accept this application right now. A student can only have one OJT at a time.
                    </div>
                @endif
                @if($availableStatuses !== [])
                <form method="POST" action="{{ route('employer.applicants.status', $application) }}" data-ajax-form data-application-status-form data-ajax-redirect>
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label" for="statusSelect">Status</label>
                        <select id="statusSelect" class="form-select @error('status') is-invalid @enderror" name="status" required>
                            @foreach($availableStatuses as $status)
                                <option value="{{ $status->value }}" {{ $application->status === $status ? 'selected' : '' }}>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Interview Date</label>
                        <input type="datetime-local" class="form-control" name="interview_at" value="{{ old('interview_at', $application->interview_at?->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="employer_notes" rows="2">{{ old('employer_notes', $application->employer_notes) }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Update Status</button>
                </form>
                @else
                    <p class="text-muted mb-0">No further status changes are available.</p>
                @endif
                @if($application->placement)
                    <a href="{{ route('employer.interns.show', $application->placement) }}" class="btn btn-outline-primary w-100 mt-3">
                        <i class="bi bi-clock-history" aria-hidden="true"></i> Open OJT record (DTR, journal, evaluation)
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
</div>{{-- /.ar-wrap --}}

<script>
(() => {
    const tabs = [...document.querySelectorAll('[data-ar-tab]')];
    const panels = [...document.querySelectorAll('[data-ar-panel]')];
    const show = (name, focus) => {
        tabs.forEach((tab) => {
            const on = tab.dataset.arTab === name;
            tab.classList.toggle('is-active', on);
            tab.setAttribute('aria-selected', on ? 'true' : 'false');
            tab.tabIndex = on ? 0 : -1;
            if (on && focus) tab.focus();
        });
        panels.forEach((panel) => { panel.hidden = panel.dataset.arPanel !== name; });
    };
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => show(tab.dataset.arTab));
        tab.addEventListener('keydown', (event) => {
            if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;
            event.preventDefault();
            const next = tabs[(index + (event.key === 'ArrowRight' ? 1 : tabs.length - 1)) % tabs.length];
            show(next.dataset.arTab, true);
        });
    });
})();
</script>


<dialog id="employerFilePreview" class="sb-dialog" aria-labelledby="employerPreviewTitle">
    <div class="sb-dialog-header">
        <h2 id="employerPreviewTitle">File preview</h2>
        <button type="button" class="btn-close" id="closeEmployerPreview" aria-label="Close"></button>
    </div>
    <div class="row g-3 sb-dialog-body">
        <div class="col-lg-8" id="employerPreviewPane"></div>
        <dl class="col-lg-4">
            <dt>Category</dt><dd id="employerPreviewCategory"></dd>
            <dt>Issuer</dt><dd id="employerPreviewIssuer"></dd>
            <dt>Date uploaded</dt><dd id="employerPreviewUploaded"></dd>
            <dt>Status</dt><dd id="employerPreviewStatus"></dd>
            <dt>Reviewed by</dt><dd id="employerPreviewReviewer"></dd>
            <dt>Reviewed on</dt><dd id="employerPreviewReviewedAt"></dd>
            <dt>File size</dt><dd id="employerPreviewSize"></dd>
            <a class="btn btn-primary mt-3" id="employerPreviewDownload" href hidden>Download</a>
        </dl>
    </div>
</dialog>
<script>
(() => {
    const modal = document.getElementById('employerFilePreview');
    const pane = document.getElementById('employerPreviewPane');
    document.querySelectorAll('.employer-file-preview').forEach((button) => {
        button.addEventListener('click', () => {
            const data = button.dataset;
            document.getElementById('employerPreviewTitle').textContent = data.fileName;
            document.getElementById('employerPreviewCategory').textContent = data.fileCategory;
            document.getElementById('employerPreviewIssuer').textContent = data.fileIssuer || 'Not provided';
            document.getElementById('employerPreviewUploaded').textContent = data.fileUploaded;
            document.getElementById('employerPreviewStatus').textContent = data.fileStatus;
            document.getElementById('employerPreviewReviewer').textContent = data.fileReviewer;
            document.getElementById('employerPreviewReviewedAt').textContent = data.fileReviewedAt;
            document.getElementById('employerPreviewSize').textContent = data.fileSize;
            const download = document.getElementById('employerPreviewDownload');
            download.hidden = !data.downloadUrl;
            if (data.downloadUrl) download.href = data.downloadUrl;
            pane.replaceChildren();
            if (data.fileExtension === 'pdf') {
                const frame = document.createElement('iframe');
                frame.title = `Preview of ${data.fileName}`;
                frame.src = data.previewUrl;
                                pane.append(frame);
            } else if (['jpg', 'jpeg', 'png'].includes(data.fileExtension)) {
                const image = document.createElement('img');
                image.src = data.previewUrl;
                image.alt = data.fileName;
                                pane.append(image);
            } else {
                const message = document.createElement('p');
                message.textContent = `Preview not available for ${data.fileName} (${data.fileExtension || 'unknown type'}, ${data.fileSize}).`;
                pane.append(message);
            }
            modal.showModal();
        });
    });
    document.getElementById('closeEmployerPreview').addEventListener('click', () => modal.close());
    modal.addEventListener('click', (event) => {
        if (event.target === modal) modal.close();
    });
})();
</script>
@endsection
