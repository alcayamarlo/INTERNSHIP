@extends('layouts.app')

@section('title', 'Verification Queue')

@section('content')
<main
    class="sb-dash sbx"
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
        eyebrow="Student submissions" />

    <section class="sb-kpis" aria-label="Verification summary">
        <div class="card sb-kpi">
            <i class="bi bi-hourglass-split" aria-hidden="true"></i>
            <strong class="sb-kpi-value" id="countPending">0</strong>
            <span class="sb-kpi-label">Pending</span>
        </div>
        <div class="card sb-kpi">
            <i class="bi bi-patch-check" aria-hidden="true"></i>
            <strong class="sb-kpi-value" id="countVerified">0</strong>
            <span class="sb-kpi-label">Verified</span>
        </div>
        <div class="card sb-kpi">
            <i class="bi bi-x-circle" aria-hidden="true"></i>
            <strong class="sb-kpi-value" id="countRejected">0</strong>
            <span class="sb-kpi-label">Rejected</span>
        </div>
    </section>

    <div class="card sb-filterbar" role="search" aria-label="Filter submissions">
        <div class="sb-filter-row">
            <div class="sb-filter-field">
                <label class="form-label" for="studentSearch">Search</label>
                <input id="studentSearch" class="form-control" type="search" placeholder="Search student name or ID…" aria-describedby="studentSearchHelp" autocomplete="off">
                <span id="studentSearchHelp" class="visually-hidden">Search by student name or student ID</span>
            </div>
            <div class="sb-filter-field">
                <label class="form-label" for="programFilter">Program</label>
                <select id="programFilter" class="form-select">
                    <option value="">All programs</option>
                    @foreach($programs as $program)
                        <option value="{{ $program }}">{{ $program }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="sb-filter-extra">
            <div class="sb-pill-tabs" role="group" aria-label="Verification status">
                <button type="button" class="sb-pill-tab is-active" data-filter-group="status" data-filter-value="pending" aria-pressed="true">Pending <span class="sb-tab-count" data-status-count="pending"></span></button>
                <button type="button" class="sb-pill-tab" data-filter-group="status" data-filter-value="verified" aria-pressed="false">Verified <span class="sb-tab-count" data-status-count="verified"></span></button>
                <button type="button" class="sb-pill-tab" data-filter-group="status" data-filter-value="rejected" aria-pressed="false">Rejected <span class="sb-tab-count" data-status-count="rejected"></span></button>
            </div>
            <div class="sb-pill-tabs" role="group" aria-label="Submission type">
                <button type="button" class="sb-pill-tab is-active" data-filter-group="type" data-filter-value="all" aria-pressed="true">All</button>
                <button type="button" class="sb-pill-tab" data-filter-group="type" data-filter-value="portfolio" aria-pressed="false">Portfolio</button>
            </div>
        </div>
    </div>

    <div class="sbx-split sb-layout-2">
        <section class="card sbx-list-card" aria-labelledby="studentListLabel">
            <p class="sb-block-title" id="studentListLabel" aria-live="polite">Students · <span id="studentCount">0</span></p>
            <ul class="sbx-list" id="studentList" role="listbox" aria-labelledby="studentListLabel" aria-controls="studentPanel"></ul>
            <p class="sbx-empty" id="studentListEmpty" hidden></p>
        </section>

        <section class="card sbx-detail" id="studentPanel" aria-label="Selected student">
            <div class="sb-detail" id="studentPanelContent"></div>
            <div id="emptyQueue" class="sb-empty" hidden>
                <i class="bi bi-inbox" aria-hidden="true"></i>
                <strong class="sb-empty-title" id="emptyQueueTitle">Nothing to review here right now.</strong>
                <p>Try another program or status, or clear the search.</p>
                <button type="button" id="clearFilters" class="btn btn-outline-secondary btn-sm">Clear filters</button>
            </div>
        </section>
    </div>

    <div id="verificationToast" class="toast show position-fixed bottom-0 end-0 m-3 z-3" role="status" aria-live="polite" hidden>
        <div class="toast-body d-flex align-items-center gap-3">
            <span id="toastMessage"></span>
            <button type="button" id="undoAction" class="btn btn-link btn-sm p-0 ms-auto" hidden>Undo</button>
        </div>
    </div>
</main>

<dialog id="verificationFilePreview" class="sb-dialog" aria-labelledby="verificationPreviewTitle">
    <div class="sb-dialog-header">
        <h2 id="verificationPreviewTitle">File preview</h2>
        <button type="button" class="btn-close" id="closeVerificationPreview" aria-label="Close preview"></button>
    </div>
    <div class="row g-3 sb-dialog-body">
        <div class="col-lg-8 sb-file-preview" id="verificationPreviewPane"></div>
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

<div class="offcanvas-backdrop fade" id="studentDrawerBackdrop" hidden></div>
<aside
    class="offcanvas offcanvas-end sb-drawer"
    id="studentDrawer"
    role="dialog"
    aria-modal="true"
    aria-labelledby="drawerName"
    aria-hidden="true"
    tabindex="-1"
>
    <div class="offcanvas-header">
        <div class="sb-person">
            <span class="sb-initials sb-initials--lg" id="drawerAvatar" aria-hidden="true"></span>
            <div class="sb-person-text">
                <h2 id="drawerName" class="h5 mb-0"></h2>
                <small id="drawerStudentId"></small>
            </div>
        </div>
        <button type="button" class="btn-close" id="closeStudentDrawer" aria-label="Close student details"></button>
    </div>
    <div class="offcanvas-body" id="drawerContent">
        <section class="sb-block mt-0">
            <h3 class="h6 mb-2">Student profile</h3>
            <dl class="sb-fields" id="drawerDetails"></dl>
        </section>
        <section class="sb-block">
            <h3 class="h6 mb-2" id="drawerProgressTitle">Verification progress</h3>
            <div class="progress progress--thin" role="progressbar" aria-labelledby="drawerProgressText"><div class="progress-bar" id="drawerProgressBar"></div></div>
            <p class="sb-row-sub mt-2 mb-0" id="drawerProgressText"></p>
        </section>
        <section class="sb-block">
            <h3 class="h6 mb-2">Portfolio summary</h3>
            <dl class="sb-fields" id="drawerPortfolioStats"></dl>
        </section>
        <section class="sb-block">
            <h3 class="h6 mb-2">Submissions</h3>
            <ul class="sb-rows" id="drawerItems"></ul>
        </section>
        <section class="sb-block" id="deploymentDocumentUploadSection">
            <h3 class="h6 mb-2">Upload deployment document</h3>
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
        <section class="sb-block">
            <h3 class="h6 mb-2">Recent activity</h3>
            <ol class="sb-rows" id="drawerActivity"></ol>
        </section>
    </div>
</aside>

<script id="verificationData" type="application/json">{!! json_encode($students, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endsection

@push('scripts')
    <script src="{{ asset('js/coordinator-verification.js') }}?v={{ @filemtime(public_path('js/coordinator-verification.js')) }}" defer></script>
@endpush
