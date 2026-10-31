@extends('layouts.app')

@section('title', $profile['name'])

@section('content')
<x-page-header :title="$profile['name']" :subtitle="trim(($profile['program'] ?? '').($profile['year_level'] ? ' — '.$profile['year_level'] : ''))">
    <a href="{{ route('employer.applicants.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Applicants</a>
</x-page-header>

<div class="row g-4" data-refresh-on-focus>
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <p class="mb-0"><strong>Applied for:</strong> {{ $application->internship->title }}</p>
                    {{-- Status badge is rewritten by resources/js/app.js after an ajax status update. --}}
                    <span class="badge {{ $application->status->badgeClass() }} fs-6" data-application-status>{{ $application->status->label() }}</span>
                </div>
                <hr>
                <p><strong>Match Score:</strong> <span class="sb-tag sb-tag--brand">{{ $application->match_percentage }}%</span></p>
                <p><strong>Applied:</strong> {{ $application->applied_at?->format('M d, Y h:i A') }}</p>
                @if($application->cover_letter)
                    <h2 class="h6">Cover Letter</h2>
                    <p class="sb-tile">{!! nl2br(e($application->cover_letter)) !!}</p>
                @endif
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h2>Bio-data</h2></div>
            <div class="card-body">
                @if($profile['contact'])
                    @include('employer.applicants.partials.definition-list', ['heading' => 'Contact', 'rows' => $profile['contact']])
                @else
                    <p class="text-muted small"><i class="bi bi-eye-slash" aria-hidden="true"></i> Contact details are hidden because this application was withdrawn.</p>
                @endif
                @include('employer.applicants.partials.definition-list', ['heading' => 'Emergency contact', 'rows' => $profile['emergency_contact'] ?? []])
                @include('employer.applicants.partials.definition-list', ['heading' => 'Personal', 'rows' => $profile['personal']])
                @include('employer.applicants.partials.definition-list', ['heading' => 'Academic', 'rows' => $profile['academic']])
                @if($profile['awards'])
                    <h3 class="h6">Honors / awards</h3>
                    <dl class="row small mb-3">
                        @foreach($profile['awards'] as $group)
                            <dt class="col-sm-4">{{ $group['label'] }}</dt>
                            <dd class="col-sm-8">
                                <ul class="list-unstyled mb-0">
                                    @foreach($group['awards'] as $award)
                                        <li class="mb-1">{!! nl2br(e($award['title'])) !!}@if($award['meta'] !== '')<span class="d-block text-muted">{{ $award['meta'] }}</span>@endif</li>
                                    @endforeach
                                </ul>
                            </dd>
                        @endforeach
                    </dl>
                @endif
                @if($profile['social_links'])
                    <h3 class="h6">Links</h3>
                    <ul class="list-inline small mb-3">
                        @foreach($profile['social_links'] as $label => $url)
                            <li class="list-inline-item"><a href="{{ $url }}" target="_blank" rel="noopener noreferrer nofollow">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                @endif
                @if($profile['career_objectives'])
                    <h3 class="h6">Career objectives</h3>
                    <p class="small mb-0">{{ $profile['career_objectives'] }}</p>
                @endif
            </div>
        </div>

        @if($profile['competencies'] || $profile['certificates'])
        <div class="card mb-4">
            <div class="card-header"><h2>Certificates</h2></div>
            <div class="card-body">
                @if($profile['competencies'])
                    <div class="row g-2">
                        @foreach($profile['competencies'] as $certificate)
                            <div class="col-md-6">
                                <div class="sb-tile">
                                    <strong>{{ $certificate['name'] }}</strong>
                                    @if($certificate['issuer'])<small class="text-muted d-block">{{ $certificate['issuer'] }}</small>@endif
                                    <div class="d-flex flex-wrap gap-1 mt-2">
                                        @if($certificate['type'])<span class="sb-tag">{{ $certificate['type'] }}</span>@endif
                                        @if($certificate['skill_type'])<span class="sb-tag">{{ $certificate['skill_type'] }}</span>@endif
                                        @if($certificate['level'])<span class="sb-tag sb-tag--brand">{{ $certificate['level'] }}</span>@endif
                                    </div>
                                    @if($certificate['skills'])
                                        <small class="d-block mt-2">Skills learned: {{ implode(', ', $certificate['skills']) }}</small>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                @if($profile['certificates'])
                    <h3 class="h6 mt-3">Older certificate files</h3>
                    <ul class="list-unstyled mb-0">
                        @foreach($profile['certificates'] as $file)
                            @include('employer.applicants.partials.file-row', ['fileCategory' => 'Certificate'])
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
        @endif

        @if($profile['skills'])
        <div class="card mb-4">
            <div class="card-header"><h2>Skills</h2></div>
            <div class="card-body">
                <p class="small text-muted">Self-declared by the student. Not part of the match score.</p>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($profile['skills'] as $skill)
                        <span class="sb-tag sb-tag--brand">{{ $skill['name'] }}@if($skill['level']) · {{ $skill['level'] }}@endif @if($skill['has_certificate'])<i class="bi bi-file-earmark-check" aria-hidden="true"></i><span class="visually-hidden">(has certificate)</span>@endif</span>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        @if($profile['experiences'])
        <div class="card mb-4">
            <div class="card-header"><h2>Experience</h2></div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    @foreach($profile['experiences'] as $experience)
                        <li class="border-bottom py-2">
                            <strong>{{ $experience['position'] }}</strong> · {{ $experience['organization'] }}
                            <small class="text-muted d-block">{{ $experience['period'] }}</small>
                            @if($experience['description'])<small class="d-block">{{ $experience['description'] }}</small>@endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        @if($profile['references'])
        <div class="card mb-4">
            <div class="card-header"><h2>Character references</h2></div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    @foreach($profile['references'] as $reference)
                        <li class="border-bottom py-2">
                            <strong>{{ $reference['name'] }}</strong>
                            <small class="text-muted d-block">{{ $reference['position'] }} · {{ $reference['organization'] }} · {{ $reference['contact_number'] }}</small>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        @foreach(['projects' => 'Projects', 'documents' => 'Documents'] as $fileGroup => $fileGroupLabel)
            @if($profile[$fileGroup])
            <div class="card mb-4">
                <div class="card-header"><h2>{{ $fileGroupLabel }}</h2></div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        @foreach($profile[$fileGroup] as $file)
                            @include('employer.applicants.partials.file-row', ['fileCategory' => \Illuminate\Support\Str::singular($fileGroupLabel)])
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
        @endforeach
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-body d-grid gap-2">
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
                    <a href="{{ route('messages.show', $application->student->user) }}" class="btn btn-outline-secondary"><i class="bi bi-chat" aria-hidden="true"></i> Message Student</a>
                @else
                    <small class="text-muted">Mark the application Reviewed to message the student.</small>
                @endcan
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Update Status</h2></div>
            <div class="card-body">
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
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Interview Date</label>
                        <input type="datetime-local" class="form-control" name="interview_at" value="{{ old('interview_at', $application->interview_at?->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="employer_notes" rows="3">{{ old('employer_notes', $application->employer_notes) }}</textarea>
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
