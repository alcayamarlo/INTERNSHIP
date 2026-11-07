@extends('layouts.app')

@section('title', $internship->title)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/student-internships.css') }}?v={{ @filemtime(public_path('css/student-internships.css')) }}">
@endpush

@php
    // Competency table: status and evidence come from the match breakdown ("why this score").
    $matchedByName = collect($matchDetails['matched_requirements'])->keyBy('name');
    $missingByName = collect($matchDetails['missing_requirements'])->keyBy('name');
    $levelLabel = fn ($level) => $level instanceof \App\Enums\ProficiencyLevel ? $level->label() : ucfirst((string) $level);

    // Responsibilities as bullets: one per line, without typed "-", "*" or "•" markers.
    $responsibilities = collect(preg_split('/\r\n|\r|\n/', (string) $internship->responsibilities))
        ->map(fn ($line) => trim(preg_replace('/^\s*[-*•]\s*/u', '', $line)))
        ->filter()
        ->values();

    $programLabel = $internship->programs->isEmpty() ? 'All programs' : $internship->programs->pluck('code')->implode(', ');
    $facts = [
        ['bi-geo-alt', 'Location', $internship->location ?: 'Not stated'],
        ['bi-laptop', 'Work mode', $internship->work_setup->label()],
        ['bi-clock', 'Duration', $internship->duration ?: 'Not stated'],
        ['bi-cash-coin', 'Allowance', $internship->allowance ? '₱'.number_format($internship->allowance, 2).'/mo' : 'Not stated'],
        ['bi-mortarboard', 'Program', $programLabel],
    ];

    // Feedback form is collapsed; it opens on its own when it came back with errors.
    $feedbackOpen = $errors->hasAny(['feedback', 'comment']);
    $canApply = ! $hasApplied && $internship->isAcceptingApplications() && $eligibilityReasons === [] && ! $needsCharacterReference && $resumes->isNotEmpty();
@endphp

@section('content')
<div class="id-page">

<x-breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('student.dashboard')], ['label' => 'Internships', 'url' => route('student.internships.index')], ['label' => $internship->title]]" />

{{-- =========================================================
     HEADER
========================================================== --}}
<header class="id-head">
    <div class="id-head-main">
        <div class="id-title-row">
            <h1>{{ $internship->title }}</h1>
            <x-status-badge :status="$internship->status" :label="ucfirst($internship->status)" />
            <x-verified-badge :employer="$internship->employer" class="id-verified" />
        </div>
        <p class="id-company"><i class="bi bi-building" aria-hidden="true"></i> {{ $internship->employer->company_name }}</p>
    </div>
    <div class="id-head-actions">
        @include('partials.report-button', ['type' => 'internship', 'id' => $internship->id, 'label' => 'this posting'])
        <a href="{{ route('student.internships.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to internships</a>
    </div>
</header>

<div class="id-layout">

    {{-- =========================================================
         LEFT: facts and details
    ========================================================== --}}
    <div class="id-main">

        <dl class="ib-card id-facts" aria-label="Internship facts">
            @foreach($facts as [$icon, $label, $value])
                <div class="id-fact">
                    <i class="bi {{ $icon }}" aria-hidden="true"></i>
                    <div>
                        <dt>{{ $label }}</dt>
                        <dd>{{ $value }}</dd>
                    </div>
                </div>
            @endforeach
        </dl>

        <article class="ib-card id-content" id="applicationCard">
            <div class="id-two-col {{ $internship->requirements ? '' : 'is-single' }}">
                <section>
                    <h2>Description</h2>
                    <p>{!! nl2br(e($internship->description)) !!}</p>
                </section>
                @if($internship->requirements)
                    <section>
                        <h2>General requirements</h2>
                        <p>{!! nl2br(e($internship->requirements)) !!}</p>
                    </section>
                @endif
            </div>

            @if($responsibilities->isNotEmpty())
                <section class="id-section">
                    <h2>Responsibilities</h2>
                    <ul class="id-bullets">
                        @foreach($responsibilities as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if($internship->requirementsList->count())
                <section class="id-section">
                    <h2>Competency requirements</h2>
                    <div class="table-responsive">
                        <table class="table align-middle id-table">
                            <thead>
                                <tr><th scope="col">Skill</th><th scope="col">Required level</th><th scope="col">Your evidence</th><th scope="col">Status</th></tr>
                            </thead>
                            <tbody>
                                @foreach($internship->requirementsList as $req)
                                    @php
                                        $met = $matchedByName->has($req->requirement_name);
                                        $missing = $missingByName->get($req->requirement_name);
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">{{ $req->requirement_name }}</td>
                                        <td>{{ $levelLabel($req->required_level) }}</td>
                                        <td class="id-evidence">{{ $met ? 'Meets the required level.' : ($missing['reason_label'] ?? 'No evidence added for this skill yet.') }}</td>
                                        <td>
                                            @if($met)
                                                <span class="id-pill id-pill--ok"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Met</span>
                                            @else
                                                <span class="id-pill id-pill--no"><i class="bi bi-x-circle" aria-hidden="true"></i> Not met</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </article>
    </div>

    {{-- =========================================================
         RIGHT: match score, application, feedback (sticky)
    ========================================================== --}}
    <aside class="id-side" aria-label="Your match and application">

        {{-- Match score --}}
        <section class="ib-card id-card" aria-labelledby="idMatchTitle" id="matchBreakdown">
            <h2 class="id-card-label" id="idMatchTitle">Your match score</h2>
            {{-- data-count-up: the app's existing number count-up (resources/js/app.js), skipped with reduced motion --}}
            <div class="id-score"><span data-count-up="{{ $matchPercentage }}">{{ $matchPercentage }}</span>%</div>
            <div class="progress id-progress" role="progressbar" aria-label="Match score" aria-valuenow="{{ $matchPercentage }}" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width: {{ $matchPercentage }}%"></div>
            </div>
            <p class="id-why"><strong>Why this score:</strong> {{ $matchDetails['explanation'] }}</p>

            @if(count($matchDetails['missing_requirements']))
                <h3 class="id-subhead">Missing competencies</h3>
                <ul class="id-missing">
                    @foreach($matchDetails['missing_requirements'] as $item)
                        <li><i class="bi bi-x-circle" aria-hidden="true"></i> <span>{{ $item['name'] }} <small>({{ $item['required_level'] }} required)</small></span></li>
                    @endforeach
                </ul>
            @endif
            @if($matchDetails['total_requirements'] > 0 && count($matchDetails['missing_requirements']))
                <a href="{{ route('student.competencies.index', ['add' => 1]) }}" class="btn btn-outline-primary w-100"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add or update a certificate</a>
            @endif
        </section>

        {{-- Application status --}}
        <section class="ib-card id-card" aria-labelledby="idApplyTitle" data-apply-card>
            <h2 class="id-card-label" id="idApplyTitle">Application</h2>
            @if($requestedDocumentNames->isNotEmpty())
                <p class="id-note"><i class="bi bi-person-vcard" aria-hidden="true"></i> This company asks applicants for: {{ $requestedDocumentNames->implode(', ') }}. {{ $hasApplied ? 'Upload them' : 'After you apply, upload them' }} on your <a href="{{ route('student.profile.edit') }}#documents">Documents tab</a>.</p>
            @endif

            @if($hasApplied)
                <div class="id-notice id-notice--ok"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> You have already applied to this internship.</div>
                <a href="{{ route('student.applications.index') }}" class="btn btn-primary w-100">View my applications</a>
            @elseif(! $internship->isAcceptingApplications())
                <div class="id-notice">{{ $internship->expires_at && $internship->expires_at->isPast() ? 'This internship posting has expired and is no longer accepting applications.' : 'This internship is no longer accepting applications.' }}</div>
            @elseif($eligibilityReasons !== [])
                <div class="id-notice id-notice--warn">
                    <strong>You are not yet eligible to apply.</strong>
                    <ul>
                        @foreach($eligibilityReasons as $reason)
                            <li>{{ $reason }}</li>
                        @endforeach
                    </ul>
                </div>
            @elseif($needsCharacterReference)
                <div class="id-notice id-notice--warn">This internship requires at least one character reference. <a href="{{ route('student.profile.edit') }}#references">Add a reference</a>, then come back to apply.</div>
            @elseif($resumes->isEmpty())
                <p class="id-muted">Create a resume before applying.</p>
                <a href="{{ route('student.resume.index') }}" class="btn btn-outline-primary w-100">Manage my resumes</a>
            @else
                <p class="id-muted">Pick a resume and add an optional cover letter.</p>
                <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#idApplyModal"><i class="bi bi-send" aria-hidden="true"></i> Apply</button>
            @endif
        </section>

        {{-- Recommendation feedback (collapsed once saved) --}}
        <section class="ib-card id-card" aria-labelledby="idFeedbackTitle">
            <h2 class="id-card-label" id="idFeedbackTitle">Recommendation feedback</h2>
            @if($recommendationFeedback)
                <span class="id-pill {{ $recommendationFeedback->feedback === 'helpful' ? 'id-pill--ok' : 'id-pill--no' }}">
                    <i class="bi {{ $recommendationFeedback->feedback === 'helpful' ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-down-fill' }}" aria-hidden="true"></i>
                    Marked {{ $recommendationFeedback->feedback === 'helpful' ? 'helpful' : 'not helpful' }}
                </span>
                @if($recommendationFeedback->comment)
                    <p class="id-muted id-comment">“{{ $recommendationFeedback->comment }}”</p>
                @endif
            @endif

            <details class="id-feedback" @if($feedbackOpen) open @endif>
                <summary>{{ $recommendationFeedback ? 'Edit feedback' : 'Rate this recommendation' }}</summary>
                <form method="POST" action="{{ route('student.internships.recommendation-feedback', $internship) }}">
                    @csrf
                    <fieldset class="mb-3">
                        <legend class="form-label">Was this recommendation helpful?</legend>
                        <div class="id-choice">
                            <input type="radio" class="btn-check" name="feedback" id="idFeedbackHelpful" value="helpful" @checked(old('feedback', $recommendationFeedback?->feedback) === 'helpful')>
                            <label class="btn btn-outline-success" for="idFeedbackHelpful"><i class="bi bi-hand-thumbs-up" aria-hidden="true"></i> Helpful</label>
                            <input type="radio" class="btn-check" name="feedback" id="idFeedbackNot" value="not_helpful" @checked(old('feedback', $recommendationFeedback?->feedback) === 'not_helpful')>
                            <label class="btn btn-outline-danger" for="idFeedbackNot"><i class="bi bi-hand-thumbs-down" aria-hidden="true"></i> Not helpful</label>
                        </div>
                        @error('feedback')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </fieldset>
                    <div class="mb-3">
                        <label class="form-label" for="idFeedbackComment">Comment <span class="id-muted">(optional)</span></label>
                        <textarea class="form-control @error('comment') is-invalid @enderror" id="idFeedbackComment" name="comment" rows="3" placeholder="Share why this recommendation was helpful or not helpful...">{{ old('comment', $recommendationFeedback?->comment) }}</textarea>
                        @error('comment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-star" aria-hidden="true"></i> Save feedback</button>
                </form>
            </details>
        </section>
    </aside>
</div>

{{-- =========================================================
     APPLY: resume + optional cover letter (shared AJAX handler in resources/js/app.js)
========================================================== --}}
@if($canApply)
    <div class="modal fade" id="idApplyModal" tabindex="-1" aria-labelledby="idApplyModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('student.internships.apply', $internship) }}" class="modal-content" data-ajax-application>
                @csrf
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title h5" id="idApplyModalTitle">Apply to {{ $internship->title }}</h2>
                        <p class="id-muted mb-0">{{ $internship->employer->company_name }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="application-resume">Resume <span class="text-danger" aria-hidden="true">*</span></label>
                        <select class="form-select" id="application-resume" name="resume_id" required>
                            <option value="">Choose a resume</option>
                            @foreach($resumes as $resume)
                                <option value="{{ $resume->id }}">{{ $resume->category?->label() ?? 'Resume' }}{{ $resume->generated_at ? ' · '.$resume->generated_at->format('M Y') : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="application-cover-letter">Cover letter <span class="id-muted">(optional)</span></label>
                        <textarea class="form-control" id="application-cover-letter" name="cover_letter" rows="5" placeholder="Tell the employer why you're a great fit...">{{ old('cover_letter') }}</textarea>
                    </div>
                    <div data-application-feedback class="small mt-2" role="status" aria-live="polite"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send" aria-hidden="true"></i> Submit application</button>
                </div>
            </form>
        </div>
    </div>
@endif

</div>
@endsection

@push('scripts')
<script>
    // Applied from the dialog: the Application card switches to the "already applied" state.
    document.getElementById('idApplyModal')?.addEventListener('sb:application-submitted', (event) => {
        const card = document.querySelector('[data-apply-card]');
        if (!card) return;
        card.querySelectorAll('.id-muted, [data-bs-target="#idApplyModal"]').forEach((element) => element.remove());
        const notice = document.createElement('div');
        notice.className = 'id-notice id-notice--ok';
        notice.innerHTML = '<i class="bi bi-check-circle-fill" aria-hidden="true"></i> You have already applied to this internship.';
        const link = document.createElement('a');
        link.className = 'btn btn-primary w-100';
        link.href = event.detail?.applications_url || @json(route('student.applications.index'));
        link.textContent = 'View my applications';
        card.append(notice, link);
    });
</script>
@endpush
