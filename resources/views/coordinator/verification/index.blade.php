@extends('layouts.app')

@section('title', 'Verification Queue')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/coordinator-verification.css') }}">
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
    <header class="verification-heading">
        <div>
            <p class="verification-eyebrow">Student submissions</p>
            <h1>Verification Queue</h1>
            <p>Review each student's submissions. Verify with one click; give a reason only when you reject.</p>
        </div>
    </header>

    <section class="verification-summary" aria-label="Verification summary">
        <article class="summary-card summary-pending">
            <span>Pending</span>
            <strong id="countPending">0</strong>
        </article>
        <article class="summary-card summary-verified">
            <span>Verified</span>
            <strong id="countVerified">0</strong>
        </article>
        <article class="summary-card summary-rejected">
            <span>Rejected</span>
            <strong id="countRejected">0</strong>
        </article>
    </section>

    <section class="verification-filters" aria-label="Filter submissions">
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

        <div class="filter-group" role="group" aria-label="Submission type">
            <button type="button" class="filter-chip is-active" data-filter-group="type" data-filter-value="all" aria-pressed="true">All</button>
            <button type="button" class="filter-chip" data-filter-group="type" data-filter-value="competencies" aria-pressed="false">Competencies</button>
            <button type="button" class="filter-chip" data-filter-group="type" data-filter-value="certificates" aria-pressed="false">Certificates</button>
            <button type="button" class="filter-chip" data-filter-group="type" data-filter-value="portfolio" aria-pressed="false">Portfolio</button>
        </div>

        <div class="filter-group" role="group" aria-label="Verification status">
            <button type="button" class="filter-chip is-active" data-filter-group="status" data-filter-value="pending" aria-pressed="true">Pending</button>
            <button type="button" class="filter-chip" data-filter-group="status" data-filter-value="verified" aria-pressed="false">Verified</button>
            <button type="button" class="filter-chip" data-filter-group="status" data-filter-value="rejected" aria-pressed="false">Rejected</button>
            <button type="button" class="filter-chip" data-filter-group="status" data-filter-value="all" aria-pressed="false">Everything</button>
        </div>
    </section>

    <section id="studentGroups" class="student-groups" aria-live="polite" aria-busy="false"></section>
    <div id="emptyQueue" class="empty-queue" hidden>
        <span class="empty-queue-icon"><i class="bi bi-inbox" aria-hidden="true"></i></span>
        <strong>Nothing to review here.</strong>
        <span>Try another program or status, or clear the search.</span>
        <button type="button" id="clearFilters" class="clear-filters">Clear filters</button>
    </div>

    <div id="verificationToast" class="verification-toast" role="status" aria-live="polite" hidden>
        <span id="toastMessage"></span>
        <button type="button" id="undoAction" hidden>Undo</button>
    </div>
</main>

<dialog id="verificationFilePreview" style="width:min(900px, calc(100% - 24px)); max-height:calc(100% - 24px); padding:0; border:1px solid #1c3250; border-radius:8px; background:#0f2238; color:#e6f0fa;">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid #1c3250;">
        <h2 id="verificationPreviewTitle" style="margin:0;font-size:15px;">File preview</h2>
        <button type="button" id="closeVerificationPreview" aria-label="Close preview" style="border:0;background:transparent;color:#e6f0fa;font-size:22px;">&times;</button>
    </div>
    <div style="display:grid;grid-template-columns:minmax(0,1fr) 230px;gap:18px;padding:18px;">
        <div id="verificationPreviewPane"></div>
        <dl class="small">
            <dt>Category</dt><dd id="verificationPreviewCategory"></dd>
            <dt>Issuer</dt><dd id="verificationPreviewIssuer"></dd>
            <dt>Date uploaded</dt><dd id="verificationPreviewUploaded"></dd>
            <dt>Status</dt><dd id="verificationPreviewStatus"></dd>
            <dt>Reviewed by</dt><dd id="verificationPreviewReviewer"></dd>
            <dt>Reviewed on</dt><dd id="verificationPreviewReviewedAt"></dd>
            <dt>File size</dt><dd id="verificationPreviewSize"></dd>
            <a class="btn btn-primary" id="verificationPreviewDownload" href>Download</a>
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
    <script src="{{ asset('js/coordinator-verification.js') }}" defer></script>
@endpush
