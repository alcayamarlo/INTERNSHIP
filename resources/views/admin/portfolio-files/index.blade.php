@extends('layouts.app')

@section('title', 'Student files')

@section('content')
<x-page-header :title="'Files for '.$student->user->name" :subtitle="($student->program ?: 'Program not set').' · Student ID '.($student->student_id_number ?: 'Not provided')">
    @if($studentIdNeedsCorrection)<x-status-badge status="warning" label="ID needs correction" />@endif
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> User management</a>
</x-page-header>

<div>
    <div class="d-flex flex-wrap gap-2 mb-4">
        <span class="sb-tag">Total files: {{ $stats['total'] }}</span>
        <span class="sb-tag">Certificates: {{ $stats['certificates'] }}</span>
        <span class="sb-tag">Projects: {{ $stats['projects'] }}</span>
        <span class="sb-tag">Storage used: {{ $stats['storage_used'] }} / 10 MB</span>
    </div>

    <section class="card mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.students.student-id.update', $student) }}" class="row align-items-end g-3">
                @csrf
                @method('PUT')
                <div class="col-md-8">
                    <label class="form-label" for="student_id_number">Student ID number</label>
                    <input id="student_id_number" name="student_id_number" value="{{ old('student_id_number', $student->student_id_number) }}" placeholder="SCC-21-00013240" class="form-control @error('student_id_number') is-invalid @enderror">
                    @error('student_id_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">Update student ID</button>
                </div>
            </form>
        </div>
    </section>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th scope="col">Name</th><th scope="col">Category</th><th scope="col">Status</th><th scope="col">Review details</th><th scope="col">Actions</th></tr></thead>
                <tbody>
                @forelse($files as $file)
                    @php
                        $fileCategory = $file->category;
                        $fileType = $file instanceof \App\Models\Certificate ? 'certificate' : ($file instanceof \App\Models\Resume ? 'resume' : 'portfolio');
                        $previewUrl = $file instanceof \App\Models\Certificate
                            ? route('student.certificates.preview', $file, false)
                            : ($file instanceof \App\Models\Resume ? route('student.resume.view', $file, false) : route('student.portfolio.preview', $file, false));
                        $downloadUrl = $file instanceof \App\Models\Certificate
                            ? route('student.certificates.download', $file, false)
                            : ($file instanceof \App\Models\Resume ? route('student.resume.download', $file, false) : route('student.portfolio.download', $file, false));
                        $fileStatus = $file->verification_status ?? 'available';
                        $extension = strtolower(pathinfo((string) $file->file_path, PATHINFO_EXTENSION));
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $file->title }}</strong>
                            @if($file instanceof \App\Models\Certificate && $file->issuer)
                                <small class="d-block text-muted">{{ $file->issuer }}</small>
                            @elseif($file instanceof \App\Models\Portfolio && $file->description)
                                <small class="d-block text-muted">{{ $file->description }}</small>
                            @endif
                            <small class="d-block text-muted">{{ $file->file_size_display }} · Uploaded {{ $file->created_at?->format('M d, Y') }}</small>
                        </td>
                        <td>{{ $fileCategory?->label() ?? 'File' }}</td>
                        <td><x-status-badge :status="match($fileStatus) { 'evidence_submitted' => 'pending', default => $fileStatus }" /></td>
                        <td>
                            {{ $file->reviewerUser?->name ?? 'Not reviewed' }}
                            <small class="d-block text-muted">{{ $file->reviewed_at?->format('M d, Y') ?? 'Not reviewed' }}</small>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary admin-file-preview"
                                    data-preview-url="{{ $previewUrl }}" data-download-url="{{ $downloadUrl }}"
                                    data-file-name="{{ $file->title }}" data-file-category="{{ $fileCategory?->label() ?? 'File' }}"
                                    data-file-issuer="{{ $file instanceof \App\Models\Certificate ? ($file->issuer ?? '') : '' }}"
                                    data-file-uploaded="{{ $file->created_at?->format('M d, Y') ?? 'Unknown' }}"
                                    data-file-status="{{ ucfirst($fileStatus === 'evidence_submitted' ? 'pending' : $fileStatus) }}"
                                    data-file-reviewer="{{ $file->reviewerUser?->name ?? 'Not reviewed' }}"
                                    data-file-reviewed-at="{{ $file->reviewed_at?->format('M d, Y') ?? 'Not reviewed' }}"
                                    data-file-size="{{ $file->file_size_display }}" data-file-extension="{{ $extension }}">
                                    <i class="bi bi-eye" aria-hidden="true"></i> View
                                </button>
                                <a class="btn btn-sm btn-outline-secondary" href="{{ $downloadUrl }}">Download</a>
                                <form method="POST" action="{{ route('admin.portfolio-files.review', [$fileType, $file->id]) }}" class="d-flex gap-1">
                                    @csrf
                                    <select name="status" aria-label="Review status for {{ $file->title }}" class="form-select form-select-sm">
                                        @if($fileCategory === \App\Enums\PortfolioCategory::DeploymentDocument)
                                            <option value="approved">Approved</option><option value="submitted">Submitted</option><option value="rejected">Rejected</option>
                                        @elseif($fileCategory === \App\Enums\PortfolioCategory::Resume)
                                            <option value="available">Available</option><option value="verified">Verified</option><option value="rejected">Rejected</option>
                                        @else
                                            <option value="verified">Verified</option><option value="pending">Pending</option><option value="rejected">Rejected</option>
                                        @endif
                                    </select>
                                    <input name="remark" class="form-control form-control-sm" aria-label="Review remark" placeholder="Remark">
                                    <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                </form>
                                <form method="POST" action="{{ route('admin.portfolio-files.destroy', [$fileType, $file->id]) }}" data-confirm="Remove this file? This cannot be undone." data-confirm-title="Remove file" data-confirm-label="Remove">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state icon="bi-folder2-open" title="No files available" message="This student has not uploaded any files yet." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<dialog id="adminFilePreview" class="sb-dialog" aria-labelledby="adminPreviewTitle">
    <div class="sb-dialog-header">
        <h2 id="adminPreviewTitle"></h2>
        <button type="button" class="btn-close" id="closeAdminPreview" aria-label="Close"></button>
    </div>
    <div class="row g-3 sb-dialog-body">
        <div class="col-lg-8" id="adminPreviewPane"></div>
        <dl class="col-lg-4">
            <dt>Category</dt><dd id="adminPreviewCategory"></dd><dt>Issuer</dt><dd id="adminPreviewIssuer"></dd>
            <dt>Date uploaded</dt><dd id="adminPreviewUploaded"></dd><dt>Status</dt><dd id="adminPreviewStatus"></dd>
            <dt>Reviewed by</dt><dd id="adminPreviewReviewer"></dd><dt>Reviewed on</dt><dd id="adminPreviewReviewedAt"></dd>
            <dt>File size</dt><dd id="adminPreviewSize"></dd>
            <a class="btn btn-primary mt-3" id="adminPreviewDownload">Download</a>
        </dl>
    </div>
</dialog>
<script>
(() => {
    const modal = document.getElementById('adminFilePreview');
    const pane = document.getElementById('adminPreviewPane');
    document.querySelectorAll('.admin-file-preview').forEach((button) => button.addEventListener('click', () => {
        const data = button.dataset;
        document.getElementById('adminPreviewTitle').textContent = data.fileName;
        document.getElementById('adminPreviewCategory').textContent = data.fileCategory;
        document.getElementById('adminPreviewIssuer').textContent = data.fileIssuer || 'Not provided';
        document.getElementById('adminPreviewUploaded').textContent = data.fileUploaded;
        document.getElementById('adminPreviewStatus').textContent = data.fileStatus;
        document.getElementById('adminPreviewReviewer').textContent = data.fileReviewer;
        document.getElementById('adminPreviewReviewedAt').textContent = data.fileReviewedAt;
        document.getElementById('adminPreviewSize').textContent = data.fileSize;
        document.getElementById('adminPreviewDownload').href = data.downloadUrl;
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
            image.addEventListener('error', () => {
                const message = document.createElement('p');
                message.textContent = 'Preview not available. The file could not be loaded.';
                pane.replaceChildren(message);
            }, { once: true });
            pane.append(image);
        } else {
            const message = document.createElement('p');
            message.textContent = 'This file type cannot be previewed here. Download the file to open it.';
            pane.append(message);
        }
        modal.showModal();
    }));
    document.getElementById('closeAdminPreview').addEventListener('click', () => modal.close());
    modal.addEventListener('click', (event) => { if (event.target === modal) modal.close(); });
})();
</script>
@endsection
