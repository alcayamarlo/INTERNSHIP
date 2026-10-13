@extends('layouts.app')

@section('title', $application->student->user->name)

@section('content')
<div class="mb-3">
    <a href="{{ route('employer.applicants.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Applicants</a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="h4">{{ $application->student->user->name }}</h1>
                        <p class="text-muted mb-0">{{ $application->student->user->email }}</p>
                        @if($application->student->program)<p class="small">{{ $application->student->program }} — {{ $application->student->year_level }}</p>@endif
                    </div>
                    <span class="badge {{ $application->status->badgeClass() }} fs-6">{{ $application->status->label() }}</span>
                </div>
                <hr>
                <p><strong>Applied for:</strong> {{ $application->internship->title }}</p>
                <p><strong>Match Score:</strong> <span class="badge bg-primary">{{ $application->match_percentage }}%</span></p>
                <p><strong>Applied:</strong> {{ $application->applied_at?->format('M d, Y h:i A') }}</p>
                @if($application->cover_letter)
                    <h5>Cover Letter</h5>
                    <p class="border rounded p-3 bg-light">{!! nl2br(e($application->cover_letter)) !!}</p>
                @endif
            </div>
        </div>

        @if($application->student->competencies->count())
        <div class="card mb-4">
            <div class="card-header bg-white"><h5 class="mb-0">Competencies</h5></div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach($application->student->competencies as $comp)
                        <div class="col-md-6">
                            <div class="border rounded p-2">
                                <strong>{{ $comp->name }}</strong>
                                <span class="badge bg-light text-dark ms-1">{{ $comp->category->label() }}</span>
                                <span class="badge bg-primary">{{ $comp->proficiency_level->label() }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        @if($application->student->portfolios->count() || $application->student->certificates->count())
        <div class="card">
            <div class="card-header bg-white"><h5 class="mb-0">Portfolio and certificates</h5></div>
            <div class="card-body">
                @if($application->student->portfolios->count())
                    <h6>Projects</h6>
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
                    <h6>Certificates</h6>
                    <ul class="list-unstyled">
                        @foreach($application->student->certificates as $c)
                            @php($certificatePreviewUrl = $c instanceof \App\Models\Certificate
                                ? route('employer.applicants.certificates.preview', [$application, $c], false)
                                : route('employer.applicants.portfolios.preview', [$application, $c], false))
                            <li class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <span>{{ $c->title }}
                            <small class="d-block text-success"><i class="bi bi-patch-check-fill" aria-hidden="true"></i> Verified</small>
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
                        <i class="bi bi-eye"></i> View resume
                    </button>
                    <a href="{{ route('employer.applicants.resume', $application, false) }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-pdf"></i> Download resume</a>
                @endif
                <a href="{{ route('messages.show', $application->student->user) }}" class="btn btn-outline-secondary"><i class="bi bi-chat"></i> Message Student</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white"><h5 class="mb-0">Update Status</h5></div>
            <div class="card-body">
                @if($availableStatuses !== [])
                <form method="POST" action="{{ route('employer.applicants.status', $application) }}">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select @error('status') is-invalid @enderror" name="status" required>
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

<dialog id="employerFilePreview" class="p-0 border rounded" style="width:min(900px, calc(100% - 24px)); max-height:calc(100% - 24px); background:#0f2238; color:#e6f0fa;">
    <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
        <h2 class="h6 mb-0" id="employerPreviewTitle">File preview</h2>
        <button type="button" class="btn-close btn-close-white" id="closeEmployerPreview" aria-label="Close"></button>
    </div>
    <div class="row g-3 p-3">
        <div class="col-lg-8" id="employerPreviewPane"></div>
        <dl class="col-lg-4 small">
            <dt>Category</dt><dd id="employerPreviewCategory"></dd>
            <dt>Issuer</dt><dd id="employerPreviewIssuer"></dd>
            <dt>Date uploaded</dt><dd id="employerPreviewUploaded"></dd>
            <dt>Status</dt><dd id="employerPreviewStatus"></dd>
            <dt>Reviewed by</dt><dd id="employerPreviewReviewer"></dd>
            <dt>Reviewed on</dt><dd id="employerPreviewReviewedAt"></dd>
            <dt>File size</dt><dd id="employerPreviewSize"></dd>
            <a class="btn btn-primary" id="employerPreviewDownload" href hidden>Download</a>
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
                frame.style.cssText = 'width:100%;min-height:60vh;background:#0b1a2e;border:1px solid #1c3250;';
                pane.append(frame);
            } else if (['jpg', 'jpeg', 'png'].includes(data.fileExtension)) {
                const image = document.createElement('img');
                image.src = data.previewUrl;
                image.alt = data.fileName;
                image.style.cssText = 'display:block;max-width:100%;max-height:60vh;margin:auto;';
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
