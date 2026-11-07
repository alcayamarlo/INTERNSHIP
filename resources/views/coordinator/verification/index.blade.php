@extends('layouts.app')

@section('title', 'Verification Queue')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/coordinator-verification.css') }}?v={{ @filemtime(public_path('css/coordinator-verification.css')) }}">
@endpush

@section('content')
<main
    class="verification-page"
    id="verificationPage"
    data-base-url="{{ $verificationBaseUrl }}"
    data-students-url="{{ route('coordinator.verification.students.show', ['student' => '__student__'], false) }}"
    data-verify-all-url="{{ route('coordinator.verification.students.verify-all', ['student' => '__student__'], false) }}"
    data-evidence-url="{{ route('coordinator.verification.evidence', ['type' => '__type__', 'id' => '__id__'], false) }}"
    data-download-url="{{ route('coordinator.verification.download', ['type' => '__type__', 'id' => '__id__'], false) }}"
>
    <x-page-header
        title="Verification Queue"
        subtitle="Review each student's submissions. Verify with one click; give a reason only when you reject."
        eyebrow="Student submissions"
    >
        <p class="vq-summary" aria-label="Verification summary">
            <span><strong id="countPending">0</strong> pending</span>
            <span aria-hidden="true">·</span>
            <span><strong id="countVerified">0</strong> verified</span>
            <span aria-hidden="true">·</span>
            <span><strong id="countRejected">0</strong> rejected</span>
        </p>
    </x-page-header>

    <section class="card vq-card" aria-label="Student submissions">
        <div class="verification-filters" role="search" aria-label="Filter submissions">
            <label class="verification-search">
                <span class="visually-hidden">Search by student name or student ID</span>
                <i class="bi bi-search" aria-hidden="true"></i>
                <input id="studentSearch" type="search" placeholder="Search student name or ID…" autocomplete="off">
            </label>

            <label class="verification-program">
                <span class="visually-hidden">Filter by program</span>
                <select id="programFilter">
                    <option value="">All programs</option>
                    @foreach($programs as $program)
                        <option value="{{ $program }}">{{ $program }}</option>
                    @endforeach
                </select>
            </label>

            <div class="filter-group" role="group" aria-label="Verification status">
                <button type="button" class="filter-chip is-active" data-filter-group="status" data-filter-value="pending" aria-pressed="true">Pending <span class="vq-chip-count" data-status-count="pending"></span></button>
                <button type="button" class="filter-chip" data-filter-group="status" data-filter-value="verified" aria-pressed="false">Verified <span class="vq-chip-count" data-status-count="verified"></span></button>
                <button type="button" class="filter-chip" data-filter-group="status" data-filter-value="rejected" aria-pressed="false">Rejected <span class="vq-chip-count" data-status-count="rejected"></span></button>
                <button type="button" class="filter-chip" data-filter-group="status" data-filter-value="all" aria-pressed="false">Everything <span class="vq-chip-count" data-status-count="all"></span></button>
            </div>

            <div class="filter-group" role="group" aria-label="Submission type">
                <button type="button" class="filter-chip is-active" data-filter-group="type" data-filter-value="all" aria-pressed="true">All</button>
                <button type="button" class="filter-chip" data-filter-group="type" data-filter-value="portfolio" aria-pressed="false">Portfolio</button>
            </div>
        </div>

        <div class="vq-split">
            <div class="vq-list-col">
                <p class="vq-list-head" id="studentListLabel" aria-live="polite">Students · <span id="studentCount">0</span></p>
                <ul class="vq-list" id="studentList" role="listbox" aria-labelledby="studentListLabel" aria-controls="studentPanel"></ul>
                <p class="vq-list-empty" id="studentListEmpty" hidden></p>
            </div>

            <section class="vq-panel" id="studentPanel" aria-label="Selected student">
                <div class="vq-panel-content" id="studentPanelContent"></div>
                <div id="emptyQueue" class="empty-queue" hidden>
                    <span class="empty-queue-icon"><i class="bi bi-inbox" aria-hidden="true"></i></span>
                    <strong id="emptyQueueTitle">Nothing to review here right now.</strong>
                    <span>Try another program or status, or clear the search.</span>
                    <button type="button" id="clearFilters" class="clear-filters">Clear filters</button>
                </div>
            </section>
        </div>
    </section>

    <div id="verificationToast" class="verification-toast" role="status" aria-live="polite" hidden>
        <span id="toastMessage"></span>
        <button type="button" id="undoAction" hidden>Undo</button>
    </div>
</main>

<dialog id="verificationFilePreview" class="sb-dialog" aria-labelledby="verificationPreviewTitle">
    <div class="sb-dialog-header">
        <h2 id="verificationPreviewTitle">File preview</h2>
        <button type="button" class="btn-close" id="closeVerificationPreview" aria-label="Close preview"></button>
    </div>
    <div class="row g-3 sb-dialog-body">
        <div class="col-lg-8" id="verificationPreviewPane"></div>
        <dl class="col-lg-4">
            <dt>Category</dt><dd id="verificationPreviewCategory"></dd>
            <dt>Issuer</dt><dd id="verificationPreviewIssuer"></dd>
            <dt>Date uploaded</dt><dd id="verificationPreviewUploaded"></dd>
            <dt>Status</dt><dd id="verificationPreviewStatus"></dd>
            <dt>Reviewed by</dt><dd id="verificationPreviewReviewer"></dd>
            <dt>Reviewed on</dt><dd id="verificationPreviewReviewedAt"></dd>
            <dt>File size</dt><dd id="verificationPreviewSize"></dd>
            <a class="btn btn-primary mt-3" id="verificationPreviewDownload" href>Download</a>
        </dl>
    </div>
</dialog>

<div class="drawer-backdrop" id="studentDrawerBackdrop" hidden></div>
<aside
    class="student-drawer"
    id="studentDrawer"
    role="dialog"
    aria-modal="true"
    aria-labelledby="drawerName"
    aria-hidden="true"
    tabindex="-1"
>
    <div class="drawer-header">
        <div class="drawer-avatar" id="drawerAvatar"></div>
        <div class="drawer-identity">
            <h2 id="drawerName"></h2>
            <p id="drawerStudentId"></p>
        </div>
        <button type="button" class="drawer-close" id="closeStudentDrawer" aria-label="Close student details">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>
    <div class="drawer-body" id="drawerContent">
        <section class="drawer-section">
            <h3>Student profile</h3>
            <dl class="drawer-details" id="drawerDetails"></dl>
        </section>
        <section class="drawer-section">
            <h3 id="drawerProgressTitle">Verification progress</h3>
            <div class="drawer-progress-track"><span id="drawerProgressBar"></span></div>
            <p class="drawer-progress-text" id="drawerProgressText"></p>
        </section>
        <section class="drawer-section">
            <h3>Portfolio summary</h3>
            <dl class="drawer-details" id="drawerPortfolioStats"></dl>
        </section>
        <section class="drawer-section">
            <h3>Submissions</h3>
            <ul class="drawer-item-list" id="drawerItems"></ul>
        </section>
        <section class="drawer-section" id="deploymentDocumentUploadSection">
            <h3>Upload deployment document</h3>
            <form method="POST" enctype="multipart/form-data" id="deploymentDocumentUploadForm">
                @csrf
                <label class="form-label" for="deploymentDocumentType">Document type</label>
                <select class="form-select" id="deploymentDocumentType" name="deployment_requirement_id" required></select>
                <label class="form-label mt-2" for="deploymentDocumentFile">File</label>
                <input class="form-control" id="deploymentDocumentFile" type="file" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                <button class="btn btn-primary mt-2" type="submit">Upload document</button>
                <p class="small mt-2 mb-0" id="deploymentDocumentFeedback" role="status" aria-live="polite"></p>
            </form>
        </section>
        <section class="drawer-section">
            <h3>Recent activity</h3>
            <ol class="drawer-activity-list" id="drawerActivity"></ol>
        </section>
    </div>
</aside>

<script id="verificationData" type="application/json">{!! json_encode($students, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endsection

@push('scripts')
    <script src="{{ asset('js/coordinator-verification.js') }}?v={{ @filemtime(public_path('js/coordinator-verification.js')) }}" defer></script>
@endpush
