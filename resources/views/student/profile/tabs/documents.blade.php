@php
    $requiredDocuments = $deploymentRequirements->reject(fn ($row) => $row['requirement']->on_request
        || ($row['requirement']->code === 'resume' && $row['requirement']->upload_source === 'system'));
    $requestedIds = $deploymentRequirements->filter(fn ($row) => $row['requirement']->on_request);
@endphp
<section class="profile-panel" id="panel-documents" role="tabpanel" aria-labelledby="tab-documents" tabindex="0" @if($activeTab !== 'documents') hidden @endif>
    <h2 class="panel-heading">Documents</h2>
    <p class="portfolio-description"><i class="bi bi-lock" aria-hidden="true"></i> Only you, your OJT coordinator, and companies you apply to can see your resume, transcript, and requested IDs. Consent forms, COR, medical certificates, and MOA documents stay between you and your coordinator.</p>

    <div class="stat-grid">
        <article class="stat-card"><span class="stat-label">Total files</span><strong class="stat-value" data-total-files="{{ $documentItems->count() }}">{{ $documentItems->count() }}</strong></article>
        <article class="stat-card"><span class="stat-label">Storage used</span><strong class="stat-value">{{ $portfolioStats['storage_usage'] }}</strong><span class="portfolio-description">10 MB limit</span></article>
    </div>

    <section class="deployment-requirements" aria-labelledby="resumeHeading">
        <h3 class="panel-subheading" id="resumeHeading">Resume</h3>
        <div class="deployment-requirement">
            <div>
                <span class="deployment-requirement-name">Resume</span>
                <span class="deployment-requirement-status {{ $latestResume ? 'approved' : 'missing' }}">{{ $latestResume ? 'Ready' : 'Missing' }}</span>
                <span class="deployment-requirement-note">{{ $latestResume ? 'Generated '.$latestResume->generated_at?->format('M d, Y h:i A') : 'Generate it from your profile details.' }}</span>
                <span class="deployment-requirement-note">Only your 3 latest resumes are kept. Generating a new one removes the oldest.</span>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('student.resume.index') }}" class="btn-profile">View resume</a>
                <form method="POST" action="{{ route('student.resume.generate') }}">
                    @csrf
                    <button type="submit" class="btn-primary-profile">{{ $latestResume ? 'Regenerate' : 'Generate' }}</button>
                </form>
            </div>
        </div>
    </section>

    @php($idPhotoUrl = \App\Http\Controllers\Student\IdPhotoController::url($student))
    <section class="deployment-requirements" aria-labelledby="idPhotoHeading">
        <h3 class="panel-subheading" id="idPhotoHeading">2x2 picture <span class="panel-tag">Optional</span></h3>
        <div class="id-photo-card">
            <div class="id-photo-preview" data-id-photo-preview>
                @if($idPhotoUrl)
                    <img src="{{ $idPhotoUrl }}" alt="Your 2x2 picture">
                @else
                    <span class="id-photo-empty">No 2x2 picture yet</span>
                @endif
            </div>
            <div>
                <p class="portfolio-description mb-2">A formal photo with a plain background. JPG, PNG, or WebP, up to 5 MB. You can position your face before saving. Only you, your OJT coordinator, and companies you apply to can see it.</p>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn-primary-profile" data-id-photo-pick>{{ $idPhotoUrl ? 'Replace' : 'Upload 2x2 picture' }}</button>
                    <form method="POST" action="{{ route('student.profile.id-photo.destroy') }}" data-id-photo-remove @unless($idPhotoUrl) hidden @endunless
                        data-confirm="Remove your 2x2 picture?" data-confirm-title="Remove 2x2 picture" data-confirm-label="Remove">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-profile">Remove</button>
                    </form>
                </div>
                <p class="portfolio-description mt-2 mb-0">Only your 2x2 picture appears on your resume. Generate your resume again after uploading it.</p>
                <div class="invalid-feedback d-block" data-id-photo-error role="alert">@error('id_photo'){{ $message }}@enderror</div>
            </div>
        </div>
        <form method="POST" action="{{ route('student.profile.id-photo.store') }}" enctype="multipart/form-data" class="visually-hidden" data-id-photo-form>
            @csrf
            <input type="file" name="id_photo" accept="image/jpeg,image/png,image/webp" data-id-photo-input aria-label="2x2 picture">
            <input type="hidden" name="crop_x" data-crop-field="x">
            <input type="hidden" name="crop_y" data-crop-field="y">
            <input type="hidden" name="crop_size" data-crop-field="size">
        </form>
        <dialog class="id-photo-dialog" data-id-photo-dialog aria-labelledby="idPhotoCropTitle">
            <h3 class="panel-subheading" id="idPhotoCropTitle">Position your face</h3>
            <p class="portfolio-description">Drag the photo so your face is centered, then zoom in or out. The square is what will be saved.</p>
            <div class="id-photo-crop-frame" data-crop-frame>
                <img alt="Photo to position" data-crop-image draggable="false">
            </div>
            <label class="form-label mt-3" for="idPhotoZoom">Zoom</label>
            <input type="range" class="form-range" id="idPhotoZoom" min="1" max="3" step="0.01" value="1" data-crop-zoom>
            <div class="invalid-feedback d-block" data-crop-error role="alert"></div>
            <div class="collection-actions">
                <button type="button" class="btn-profile" data-crop-cancel>Cancel</button>
                <button type="button" class="btn-primary-profile" data-crop-save>Save 2x2 picture</button>
            </div>
        </dialog>
    </section>

    <section class="deployment-requirements" aria-labelledby="deploymentRequirementsHeading">
        <h3 class="panel-subheading" id="deploymentRequirementsHeading">Required documents</h3>
        <p class="portfolio-description">PDF, JPG, or PNG up to 5 MB. Rejected documents include your coordinator's note so you can replace the file.</p>
        @error('file')<div class="invalid-feedback d-block mb-2">{{ $message }}</div>@enderror
        @foreach($requiredDocuments as $deploymentDocument)
            @include('student.profile.partials.document-requirement', $deploymentDocument)
        @endforeach
    </section>

    @if($requestedIds->isNotEmpty())
        <section class="deployment-requirements" aria-labelledby="requestedIdsHeading">
            <h3 class="panel-subheading" id="requestedIdsHeading">IDs requested by internships</h3>
            <p class="portfolio-description">Upload a scan or photo of each ID. We don't store your ID numbers, only the file you upload.</p>
            @foreach($requestedIds as $deploymentDocument)
                @include('student.profile.partials.document-requirement', $deploymentDocument)
            @endforeach
        </section>
    @endif

    <section class="deployment-requirements" aria-labelledby="certificatesMovedHeading">
        <h3 class="panel-subheading" id="certificatesMovedHeading">Certificates</h3>
        <div class="deployment-requirement">
            <div>
                <span class="deployment-requirement-name">Certificates have moved to My Certificates</span>
                <span class="deployment-requirement-note">Add certificates there with the skills you learned. They show on your profile, resume, and to companies you apply to.</span>
            </div>
            <a href="{{ route('student.competencies.index') }}" class="btn-primary-profile">Go to My Certificates</a>
        </div>
    </section>

    <section aria-labelledby="documentFilesHeading">
        <h3 class="panel-subheading" id="documentFilesHeading">Your files</h3>
        @if($documentItems->isNotEmpty())
            <div class="table-responsive">
                <table class="portfolio-table">
                    <thead><tr><th scope="col">Name</th><th scope="col">Type</th><th scope="col">Status</th><th scope="col" class="text-end">Actions</th></tr></thead>
                    <tbody id="portfolioRows">
                        @foreach($documentItems as $item)
                            <tr data-portfolio-row data-type="{{ $item['type_key'] }}" data-search="{{ $item['search'] }}">
                                <td data-label="Name">
                                    <span class="portfolio-name">{{ $item['title'] }}</span>
                                    @if($item['description'] !== '')
                                        <span class="portfolio-description">{{ $item['description'] }}</span>
                                    @endif
                                </td>
                                <td data-label="Type">{{ $item['type_label'] }}</td>
                                <td data-label="Status">
                                    @unless($item['is_certificate'])
                                        <span class="status-badge {{ $item['status_class'] }}">{{ $item['status_label'] }}</span>
                                    @endunless
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
                                            <form method="POST" action="{{ $item['delete_url'] }}" data-confirm="{{ $item['delete_confirmation'] }}" data-confirm-title="Delete file">
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
        @else
            <p class="portfolio-description" id="portfolioEmptyState">No files yet. Your uploaded documents will appear here.</p>
        @endif
    </section>
</section>
