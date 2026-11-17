@extends('layouts.app')

@section('title', $profile['name'])

@section('content')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/employer-applicant-review.css') }}?v={{ @filemtime(public_path('css/employer-applicant-review.css')) }}">
@endpush
@php
    $hasSkillsTab = (bool) ($profile['competencies'] || $profile['certificates'] || $profile['skills']);
    $hasExperienceTab = (bool) ($profile['experiences'] || $profile['references']);
    $hasFilesTab = (bool) ($profile['projects'] || $profile['documents']);
@endphp

@php
    $initials = collect(preg_split('/\s+/', trim($profile['name'])))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
    $programLine = trim(($profile['program'] ?? '').($profile['year_level'] ? ' · '.$profile['year_level'] : ''));
    $canMessage = auth()->user()?->can('message.send', $application->student->user);
@endphp

<div class="ap-page" data-refresh-on-focus>
    <div class="ap-top">
        <a href="{{ route('employer.applicants.index') }}" class="ap-back"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Applicants</a>
        @if($application->student?->user)
            @include('partials.report-button', ['type' => 'user', 'id' => $application->student->user_id, 'label' => $profile['name']])
        @endif
    </div>

    <div class="ap-main">
        <aside class="ap-profile" aria-label="Applicant profile">
            <div class="ap-band" aria-hidden="true"></div>
            <div class="ap-profile-body">
                <div class="ap-avatar" aria-hidden="true">{{ $initials }}</div>
                <h1 class="ap-name">{{ $profile['name'] }}</h1>
                @if($programLine !== '')<p class="ap-program">{{ $programLine }}</p>@endif
                {{-- The badge keeps its bg-* class: resources/js/app.js rewrites text and class after an ajax status update. --}}
                <span class="badge {{ $application->status->badgeClass() }}" data-application-status>{{ $application->status->companyLabel() }}</span>

                <hr class="ap-hr">
                <dl class="mb-0">
                    <div class="ap-fact"><dt>Applied for</dt><dd class="ap-strong">{{ $application->internship->title }}</dd></div>
                    <div class="ap-fact"><dt>Applied</dt><dd class="ap-nowrap">{{ $application->applied_at?->format('M d, Y · h:i A') }}</dd></div>
                    <div class="ap-fact">
                        <dt>Match score</dt>
                        <dd class="ap-score"><span>{{ $application->match_percentage }}%</span><span class="ap-meter" aria-hidden="true"><span style="width: {{ max(0, min(100, (int) $application->match_percentage)) }}%"></span></span></dd>
                    </div>
                </dl>

                <hr class="ap-hr">
                <div class="ap-actions">
                    @if($applicationResume)
                        <button type="button" class="ap-btn ap-btn--primary employer-file-preview"
                            data-preview-url="{{ route('employer.applicants.resume.preview', $application, false) }}"
                            data-download-url="{{ route('employer.applicants.resume', $application, false) }}"
                            data-file-name="Resume" data-file-category="Resume" data-file-issuer=""
                            data-file-uploaded="{{ $applicationResume->generated_at?->format('M d, Y') ?? 'Unknown' }}"
                            data-file-status="Available" data-file-reviewer="{{ $applicationResume->reviewerUser?->name ?? 'Not reviewed' }}"
                            data-file-reviewed-at="{{ $applicationResume->reviewed_at?->format('M d, Y') ?? 'Not reviewed' }}"
                            data-file-size="{{ $applicationResume->file_size_display }}" data-file-extension="pdf">
                            <i class="bi bi-eye" aria-hidden="true"></i> View resume
                        </button>
                        <a href="{{ route('employer.applicants.resume', $application, false) }}" class="ap-btn"><i class="bi bi-file-earmark-text" aria-hidden="true"></i> Download resume</a>
                    @endif
                    @if($canMessage)
                        <a href="{{ route('messages.show', $application->student->user) }}" class="ap-btn"><i class="bi bi-chat" aria-hidden="true"></i> Message Student</a>
                    @endif
                    @if($application->placement)
                        <a href="{{ route('employer.interns.show', $application->placement) }}" class="ap-btn"><i class="bi bi-clock-history" aria-hidden="true"></i> Open OJT record</a>
                    @endif
                </div>
            </div>
        </aside>

        <div class="ap-right">
            @if(in_array($application->status, [\App\Enums\ApplicationStatus::Accepted, \App\Enums\ApplicationStatus::Completed], true))
                @include('employer.partials.coordinator-contact', ['student' => $application->student])
            @endif

            <section class="ap-card ap-statusbar" aria-label="Update status">
                {{-- The student already has an ongoing OJT from another application, so "Accepted" is not offered. --}}
                @if($placedElsewhere)
                    <div class="alert alert-warning" role="status">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        This student already has an ongoing OJT placement with another company, so you can't accept this application right now. A student can only have one OJT at a time.
                    </div>
                @endif
                @if($availableStatuses !== [])
                {{-- Stays on this page: the badge, the next statuses and the confirmation update in place. --}}
                <form method="POST" action="{{ route('employer.applicants.status', $application) }}" data-ajax-form data-application-status-form>
                    @csrf @method('PUT')
                    <div class="ap-status-row">
                        <div class="ap-field ap-field--status">
                            <label class="ap-label" for="statusSelect">Status</label>
                            <select id="statusSelect" class="form-select @error('status') is-invalid @enderror" name="status" required>
                                @foreach($availableStatuses as $status)
                                    <option value="{{ $status->value }}" {{ $application->status === $status ? 'selected' : '' }}>{{ $status->companyLabel() }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="ap-field ap-field--interview">
                            <label class="ap-label" for="interviewAt">Interview date</label>
                            <input id="interviewAt" type="datetime-local" class="form-control" name="interview_at" value="{{ old('interview_at', $application->interview_at?->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div class="ap-field ap-field--notes">
                            <label class="ap-label" for="employerNotes">Notes</label>
                            <textarea id="employerNotes" class="form-control" name="employer_notes" rows="1">{{ old('employer_notes', $application->employer_notes) }}</textarea>
                        </div>
                        <button type="submit" class="btn ap-update">Update status</button>
                    </div>
                </form>
                @else
                    <p class="ap-help mt-0">No further status changes are available.</p>
                @endif
                @unless($canMessage)
                    <p class="ap-help">Mark the application Reviewed to message the student.</p>
                @endunless
            </section>

            @php
                $overviewGroups = [
                    'Contact' => ['rows' => $profile['contact'] ?? null, 'keys' => ['Email', 'Mobile', 'City address']],
                    'Personal' => ['rows' => $profile['personal'] ?? [], 'keys' => ['Gender', 'Date of birth', 'Place of birth']],
                    'Academic' => ['rows' => $profile['academic'] ?? [], 'keys' => ['College', 'Course', 'Year level']],
                ];
            @endphp
            <section class="ap-card ap-overview" aria-labelledby="apOverviewTitle">
                <header class="ap-overview__head">
                    <span class="ap-overview__icon" aria-hidden="true"><i class="bi bi-person-lines-fill"></i></span>
                    <h2 class="ap-overview__title" id="apOverviewTitle">Overview</h2>
                </header>
                <div class="ap-overview__body">
                    @foreach($overviewGroups as $groupTitle => $group)
                        <section class="ap-ov-group">
                            <h3 class="ap-ov-group__title">{{ $groupTitle }}</h3>
                            @if($group['rows'] === null)
                                <p class="ap-help mt-0">Hidden because this application was withdrawn.</p>
                            @else
                                <dl class="ap-ov-fields">
                                    @foreach($group['keys'] as $key)
                                        <div class="ap-ov-field"><dt>{{ $key === 'City address' ? 'City address' : $key }}</dt><dd>{{ $group['rows'][$key] ?? '—' }}</dd></div>
                                    @endforeach
                                </dl>
                            @endif
                        </section>
                    @endforeach
                </div>
                <footer class="ap-overview__foot">
                    <button type="button" class="ap-overview__btn" data-ar-open="overview">View full profile <span aria-hidden="true">→</span></button>
                </footer>
            </section>
            @if($hasFilesTab)
            <section class="applicant__tiles ap-files-tile" aria-label="Applicant files">
                <button type="button" class="applicant__tile" data-ar-open="files">
                    <span class="applicant__tile-icon" aria-hidden="true"><i class="bi bi-folder2-open"></i></span>
                    <span class="applicant__tile-title">Projects &amp; documents</span>
                    <span class="applicant__tile-cta">View <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                </button>
            </section>
            @endif
        </div>{{-- /.ap-right --}}
    </div>{{-- /.ap-main --}}
</div>{{-- /.ap-page --}}

<dialog class="applicant-modal" id="applicantModal" aria-modal="true" aria-labelledby="applicantModalName">
    <div class="applicant-modal__header">
        <span class="applicant-modal__avatar" aria-hidden="true">{{ $initials }}</span>
        <div class="applicant-modal__who">
            <h2 class="applicant-modal__name" id="applicantModalName">{{ $profile['name'] }}</h2>
            @if($programLine !== '')<p class="applicant-modal__program">{{ $programLine }}</p>@endif
        </div>
        <button type="button" class="applicant-modal__close" data-ar-close aria-label="Close"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    </div>
    <div class="applicant-modal__tabs" role="tablist" aria-label="Applicant details">
        <button type="button" class="applicant-modal__tab" role="tab" id="arTabOverview" aria-controls="arPanelOverview" aria-selected="true" data-ar-tab="overview">Overview</button>
        @if($hasSkillsTab)<button type="button" class="applicant-modal__tab" role="tab" id="arTabSkills" aria-controls="arPanelSkills" aria-selected="false" tabindex="-1" data-ar-tab="skills">Skills &amp; certificates</button>@endif
        @if($hasExperienceTab)<button type="button" class="applicant-modal__tab" role="tab" id="arTabExperience" aria-controls="arPanelExperience" aria-selected="false" tabindex="-1" data-ar-tab="experience">Experience &amp; references</button>@endif
        @if($hasFilesTab)<button type="button" class="applicant-modal__tab" role="tab" id="arTabFiles" aria-controls="arPanelFiles" aria-selected="false" tabindex="-1" data-ar-tab="files">Projects &amp; documents</button>@endif
    </div>
    <div class="applicant-modal__body" tabindex="0" aria-label="Applicant details">
                {{-- Overview: bio-data in columns so it fits one screen --}}
                <div class="sb-tab-panel" role="tabpanel" id="arPanelOverview" aria-labelledby="arTabOverview" data-ar-panel="overview">
                    <div class="sb-grid sb-grid--2">
                        @if($profile['contact'])
                            @include('employer.applicants.partials.info-group', ['heading' => 'Contact', 'rows' => $profile['contact']])
                        @else
                            <section class="sb-block"><h2>Contact</h2><p class="sb-note"><i class="bi bi-eye-slash" aria-hidden="true"></i> Hidden because this application was withdrawn.</p></section>
                        @endif
                        @include('employer.applicants.partials.info-group', ['heading' => 'Personal', 'rows' => $profile['personal']])
                        @include('employer.applicants.partials.info-group', ['heading' => 'Academic', 'rows' => $profile['academic']])
                        @include('employer.applicants.partials.info-group', ['heading' => 'Emergency contact', 'rows' => $profile['emergency_contact'] ?? []])
                    </div>

                    @if($profile['awards'])
                        @include('employer.applicants.partials.awards-blocks', ['awardGroups' => $profile['awards']])
                    @endif

                    @if($profile['social_links'])
                        <section class="sb-block">
                            <h2>Links</h2>
                            <ul class="sb-tags">
                                @foreach($profile['social_links'] as $label => $url)
                                    <li><a href="{{ $url }}" target="_blank" rel="noopener noreferrer nofollow">{{ $label }}</a></li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if($profile['about_me'])
                        <section class="sb-block">
                            <h2>About me</h2>
                            <p>{{ $profile['about_me'] }}</p>
                        </section>
                    @endif

                    @if($application->cover_letter)
                        <section class="sb-block">
                            <h2>Cover letter</h2>
                            <p class="sb-tile">{!! nl2br(e($application->cover_letter)) !!}</p>
                        </section>
                    @endif
                </div>

                @if($hasSkillsTab)
                <div class="sb-tab-panel" role="tabpanel" id="arPanelSkills" aria-labelledby="arTabSkills" data-ar-panel="skills" hidden>
                    @if($profile['competencies'])
                        <section class="sb-block">
                            <h2>Certificates</h2>
                            <div class="sb-grid sb-grid--2">
                                @foreach($profile['competencies'] as $certificate)
                                    <div class="sb-tile">
                                        <span class="sb-row-title">{{ $certificate['name'] }}</span>
                                        @if($certificate['issuer'])<span class="sb-row-sub">{{ $certificate['issuer'] }}</span>@endif
                                        <div class="d-flex flex-wrap gap-2 mt-2">
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
                        <section class="sb-block">
                            <h2>Older certificate files</h2>
                            <ul class="sb-rows">
                                @foreach($profile['certificates'] as $file)
                                    @include('employer.applicants.partials.file-row', ['fileCategory' => 'Certificate'])
                                @endforeach
                            </ul>
                        </section>
                    @endif
                    @if($profile['skills'])
                        <section class="sb-block">
                            <h2>Skills</h2>
                            <p class="sb-note mb-2">Self-declared by the student. Not part of the match score.</p>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($profile['skills'] as $skill)
                                    <span class="sb-tag sb-tag--brand">{{ $skill['name'] }}@if($skill['level']) · {{ $skill['level'] }}@endif @if($skill['has_certificate'])<i class="bi bi-file-earmark-check" aria-hidden="true"></i><span class="visually-hidden">(has certificate)</span>@endif</span>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>
                @endif

                @if($hasExperienceTab)
                <div class="sb-tab-panel" role="tabpanel" id="arPanelExperience" aria-labelledby="arTabExperience" data-ar-panel="experience" hidden>
                    @if($profile['experiences'])
                        <section class="sb-block">
                            <h2>Experience</h2>
                            <ul class="sb-rows">
                                @foreach($profile['experiences'] as $experience)
                                    <li>
                                        <div class="sb-grow">
                                            <span class="sb-row-title">{{ $experience['position'] }} · {{ $experience['organization'] }}</span>
                                            <span class="sb-row-sub">{{ $experience['period'] }}</span>
                                            @if($experience['description'])<small class="d-block mt-1">{{ $experience['description'] }}</small>@endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                    @if($profile['references'])
                        <section class="sb-block">
                            <h2>Character references</h2>
                            <ul class="sb-rows">
                                @foreach($profile['references'] as $reference)
                                    <li>
                                        <div class="sb-grow">
                                            <span class="sb-row-title">{{ $reference['name'] }}</span>
                                            <span class="sb-row-sub">{{ $reference['position'] }} · {{ $reference['organization'] }} · {{ $reference['contact_number'] }}</span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                </div>
                @endif

                @if($hasFilesTab)
                <div class="sb-tab-panel" role="tabpanel" id="arPanelFiles" aria-labelledby="arTabFiles" data-ar-panel="files" hidden>
                    @foreach(['projects' => 'Projects', 'documents' => 'Documents'] as $fileGroup => $fileGroupLabel)
                        @if($profile[$fileGroup])
                            <section class="sb-block">
                                <h2>{{ $fileGroupLabel }}</h2>
                                <ul class="sb-rows">
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
</dialog>

<script>
(() => {
    const modal = document.getElementById('applicantModal');
    if (!modal) return;
    const tabs = [...modal.querySelectorAll('[data-ar-tab]')];
    const panels = [...modal.querySelectorAll('[data-ar-panel]')];
    const body = modal.querySelector('.applicant-modal__body');
    const root = document.documentElement;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let opener = null;

    const show = (name, focus) => {
        tabs.forEach((tab) => {
            const on = tab.dataset.arTab === name;
            tab.setAttribute('aria-selected', on ? 'true' : 'false');
            tab.tabIndex = on ? 0 : -1;
            if (on && focus) tab.focus();
        });
        panels.forEach((panel) => { panel.hidden = panel.dataset.arPanel !== name; });
        body.scrollTop = 0;
    };

    const open = (name, trigger) => {
        opener = trigger;
        show(name);
        root.classList.add('applicant-modal-open');
        if (!modal.open) modal.showModal();
    };

    const finish = () => {
        modal.classList.remove('is-closing');
        if (modal.open) modal.close();
        root.classList.remove('applicant-modal-open');
        if (opener && opener.isConnected) opener.focus();
    };
    const close = () => {
        if (!modal.open || modal.classList.contains('is-closing')) return;
        if (reduceMotion.matches) return finish();
        modal.classList.add('is-closing');
        setTimeout(finish, 150);
    };

    document.querySelectorAll('[data-ar-open]').forEach((tile) => tile.addEventListener('click', () => open(tile.dataset.arOpen, tile)));
    modal.querySelector('[data-ar-close]').addEventListener('click', close);
    // Esc: use the same animated close. A click on the overlay (the dialog element itself) closes too.
    modal.addEventListener('cancel', (event) => { event.preventDefault(); close(); });
    modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
    // Safety net if the dialog is closed any other way.
    modal.addEventListener('close', () => root.classList.remove('applicant-modal-open'));

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => show(tab.dataset.arTab));
        tab.addEventListener('keydown', (event) => {
            if (!['ArrowRight', 'ArrowLeft', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();
            const last = tabs.length - 1;
            const target = event.key === 'Home' ? 0 : event.key === 'End' ? last
                : (index + (event.key === 'ArrowRight' ? 1 : last)) % tabs.length;
            show(tabs[target].dataset.arTab, true);
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
        <div class="col-lg-8 sb-file-preview" id="employerPreviewPane"></div>
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
