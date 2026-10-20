@extends('layouts.app')

@section('title', $application->student->user->name)

@section('content')
<x-page-header :title="$application->student->user->name" :subtitle="$application->student->user->email">
    <a href="{{ route('employer.applicants.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Applicants</a>
</x-page-header>

<div class="row g-4" data-refresh-on-focus>
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        @if($application->student->program)<p class="mb-0">{{ $application->student->program }} — {{ $application->student->year_level }}</p>@endif
                    </div>
                    {{-- Status badge is rewritten by resources/js/app.js after an ajax status update. --}}
                    <span class="badge {{ $application->status->badgeClass() }} fs-6" data-application-status>{{ $application->status->label() }}</span>
                </div>
                <hr>
                <p><strong>Applied for:</strong> {{ $application->internship->title }}</p>
                <p><strong>Match Score:</strong> <span class="sb-tag sb-tag--brand">{{ $application->match_percentage }}%</span></p>
                <p><strong>Applied:</strong> {{ $application->applied_at?->format('M d, Y h:i A') }}</p>
                @if($application->cover_letter)
                    <h2 class="h6">Cover Letter</h2>
                    <p class="sb-tile">{!! nl2br(e($application->cover_letter)) !!}</p>
                @endif
            </div>
        </div>

        @if($application->student->competencies->count())
        <div class="card mb-4">
            <div class="card-header"><h2>Competencies</h2></div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach($application->student->competencies as $comp)
                        <div class="col-md-6">
                            <div class="sb-tile">
                                <strong>{{ $comp->name }}</strong>
                                <div class="d-flex flex-wrap gap-1 mt-2">
                                    <span class="sb-tag">{{ $comp->category->label() }}</span>
                                    <span class="sb-tag sb-tag--brand">{{ $comp->proficiency_level->label() }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        @if($application->student->portfolios->count() || $application->student->certificates->count())
        <div class="card">
            <div class="card-header"><h2>Portfolio and certificates</h2></div>
            <div class="card-body">
                @if($application->student->portfolios->count())
                    <h3 class="h6">Projects</h3>
                    <ul class="list-unstyled">
                        @foreach($application->student->portfolios as $p)
                            <li class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <span>{{ $p->title }} <small class="text-muted d-block">{{ $p->description }}</small></span>
                                <button type="button" class="btn btn-sm btn-outline-primary employer-file-preview"
                                    data-preview-url="{{ route('employer.applicants.portfolios.preview', [$application, $p], false) }}"
                                    data-file-name="{{ $p->title }}" data-file-category="Project" data-file-issuer=""
                                    data-file-uploaded="{{ $p->created_at?->format('M d, Y') ?? 'Unknown' }}"
                                    data-file-status="{{ ucfirst($p->verification_status ?? 'pending') }}"
                                    data-file-reviewer="{{ $p->reviewerUser?->name ?? 'Not reviewed' }}"
                                    data-file-reviewed-at="{{ $p->reviewed_at?->format('M d, Y') ?? 'Not reviewed' }}"
                                    data-file-size="{{ $p->file_size_display }}" data-file-extension="{{ strtolower(pathinfo($p->file_path, PATHINFO_EXTENSION)) }}">
                                    <i class="bi bi-eye" aria-hidden="true"></i> View
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
                @if($application->student->certificates->count())
                    <h3 class="h6">Certificates</h3>
                    <ul class="list-unstyled">
                        @foreach($application->student->certificates as $c)
                            @php($certificatePreviewUrl = $c instanceof \App\Models\Certificate
                                ? route('employer.applicants.certificates.preview', [$application, $c], false)
                                : route('employer.applicants.portfolios.preview', [$application, $c], false))
                            <li class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <span>{{ $c->title }}
                            <small class="d-block" style="color: var(--sb-success-ink)"><i class="bi bi-patch-check-fill" aria-hidden="true"></i> Verified</small>
                            <small class="text-muted d-block">{{ $c instanceof \App\Models\Certificate ? ($c->issuer ?? 'Issuer not provided') : ($c->description ?: 'Issuer not provided') }}</small>
                        </span>
                        <button type="button" class="btn btn-sm btn-outline-primary employer-file-preview"
                            data-preview-url="{{ $certificatePreviewUrl }}"
                            data-file-name="{{ $c->title }}" data-file-category="Certificate" data-file-issuer="{{ $c instanceof \App\Models\Certificate ? ($c->issuer ?? '') : ($c->description ?? '') }}"
                                    data-file-uploaded="{{ $c->created_at?->format('M d, Y') ?? 'Unknown' }}"
                                    data-file-status="Verified" data-file-reviewer="{{ $c->reviewerUser?->name ?? 'Not reviewed' }}"
                                    data-file-reviewed-at="{{ $c->reviewed_at?->format('M d, Y') ?? 'Not reviewed' }}"
                                    data-file-size="{{ $c->file_size_display }}" data-file-extension="{{ strtolower(pathinfo($c->file_path, PATHINFO_EXTENSION)) }}">
                                    <i class="bi bi-eye" aria-hidden="true"></i> View
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
        @endif
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
                <a href="{{ route('messages.show', $application->student->user) }}" class="btn btn-outline-secondary"><i class="bi bi-chat" aria-hidden="true"></i> Message Student</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Update Status</h2></div>
            <div class="card-body">
                @if($availableStatuses !== [])
                <form method="POST" action="{{ route('employer.applicants.status', $application) }}" data-ajax-form data-application-status-form>
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
