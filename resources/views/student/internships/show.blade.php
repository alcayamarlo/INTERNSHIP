@extends('layouts.app')

@section('title', $internship->title)

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
        ['bi-people', 'Slots', $internship->slots ? $internship->slots.' '.\Illuminate\Support\Str::plural('slot', $internship->slots) : 'Not stated'],
        ['bi-clock', 'Duration', $internship->duration ?: 'Not stated'],
        ['bi-cash-coin', 'Allowance', $internship->allowance ? '₱'.number_format($internship->allowance, 2).'/mo' : 'Not stated'],
        ['bi-mortarboard', 'Program', $programLabel],
        ['bi-calendar-event', 'Apply by', $internship->expires_at ? $internship->expires_at->format('M d, Y') : 'No deadline'],
    ];

    // Feedback form is collapsed; it opens on its own when it came back with errors.
    $feedbackOpen = $errors->hasAny(['feedback', 'comment']);
    $hasOjt = $hasOjt ?? false;
    $canApply = ! $hasOjt && ! $hasApplied && $internship->isAcceptingApplications() && $eligibilityReasons === [] && ! $needsCharacterReference && $resumes->isNotEmpty();
@endphp

@section('content')
<x-breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('student.dashboard')], ['label' => 'Internships', 'url' => route('student.internships.index')], ['label' => $internship->title]]" />

<x-page-header :title="$internship->title" :subtitle="$internship->employer->company_name">
    <x-status-badge :status="$internship->status" :label="ucfirst($internship->status)" />
    <x-verified-badge :employer="$internship->employer" />
    @include('partials.report-button', ['type' => 'internship', 'id' => $internship->id, 'label' => 'this posting'])
    <a href="{{ route('student.internships.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to internships</a>
</x-page-header>

<div class="sb-layout-2">
    <div class="sb-stack">
        <section class="card" aria-label="Internship facts">
            <div class="card-body">
                <dl class="sb-fields sb-fields--tiles">
                    @foreach($facts as [$icon, $label, $value])
                        <div><dt><i class="bi {{ $icon }}" aria-hidden="true"></i>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                    @endforeach
                </dl>
            </div>
        </section>

        <article class="card" id="applicationCard">
            <div class="card-body">
                <div class="sb-grid {{ $internship->qualifications ? 'sb-grid--2' : '' }}">
                    <section class="sb-block">
                        <h2>Description</h2>
                        <p>{!! nl2br(e($internship->description)) !!}</p>
                    </section>
                    @if($internship->qualifications)
                        <section class="sb-block">
                            <h2>Qualifications</h2>
                            <p>{!! nl2br(e($internship->qualifications)) !!}</p>
                        </section>
                    @endif
                </div>

                @if($responsibilities->isNotEmpty())
                    <section class="sb-block">
                        <h2>Responsibilities</h2>
                        <ul>
                            @foreach($responsibilities as $line)
                                <li>{{ $line }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if($internship->requirementsList->count())
                    <section class="sb-block">
                        <h2>Competency requirements</h2>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr><th scope="col">Skill</th><th scope="col">Required level</th><th scope="col">Your level</th><th scope="col">Status</th></tr>
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
                                            <td>{{ ($met ? $matchedByName->get($req->requirement_name)['student_level'] ?? null : $missing['student_level'] ?? null) ?? 'None yet' }}<small class="d-block text-muted">{{ $met ? 'Meets the required level.' : ($missing['reason_label'] ?? 'No evidence added for this skill yet.') }}</small></td>
                                            <td>
                                                @if($met)
                                                    <span class="sb-status sb-status--success"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Met</span>
                                                @elseif(($missing['score'] ?? 0) > 0)
                                                    <span class="sb-status sb-status--warning">Partial · {{ $missing['score'] }}%</span>
                                                @else
                                                    <span class="sb-status sb-status--neutral"><i class="bi bi-x-circle" aria-hidden="true"></i> Not met</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif
            </div>
        </article>
    </div>

    <aside class="sb-stack" aria-label="Your match and application">
        <section class="card" aria-labelledby="idMatchTitle" id="matchBreakdown">
            <div class="card-header"><h2 id="idMatchTitle">Your match score</h2></div>
            <div class="card-body sb-stack sb-stack--tight">
                {{-- data-count-up: the app's existing number count-up (resources/js/app.js), skipped with reduced motion --}}
                <div class="sb-stat-value"><span data-count-up="{{ $matchPercentage }}">{{ $matchPercentage }}</span>%</div>
                <div class="progress progress--thin" role="progressbar" aria-label="Match score" aria-valuenow="{{ $matchPercentage }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar" style="width: {{ $matchPercentage }}%"></div>
                </div>
                <p class="sb-note"><strong>Why this score:</strong> {{ $matchDetails['explanation'] }}</p>

                @if(count($matchDetails['missing_requirements']))
                    <div>
                        <h3 class="sb-block-title">Missing competencies</h3>
                        <ul class="sb-checks">
                            @foreach($matchDetails['missing_requirements'] as $item)
                                <li><i class="bi bi-x-circle" aria-hidden="true"></i> <span>{{ $item['name'] }} <small class="text-muted">({{ $item['required_level'] }} required)</small></span></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if($matchDetails['total_requirements'] > 0 && count($matchDetails['missing_requirements']))
                    <a href="{{ route('student.competencies.index', ['add' => 1]) }}" class="btn btn-outline-primary w-100"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add or update a certificate</a>
                @endif
            </div>
        </section>

        <section class="card" aria-labelledby="idApplyTitle" data-apply-card>
            <div class="card-header"><h2 id="idApplyTitle">Application</h2></div>
            <div class="card-body sb-stack sb-stack--tight" data-apply-body>
                @if($requestedDocumentNames->isNotEmpty())
                    <p class="sb-note"><i class="bi bi-person-vcard" aria-hidden="true"></i> This company asks applicants for: {{ $requestedDocumentNames->implode(', ') }}. {{ $hasApplied ? 'Upload them' : 'After you apply, upload them' }} on your <a href="{{ route('student.profile.edit') }}#documents">Documents tab</a>.</p>
                @endif

                @if($hasApplied)
                    <div class="sb-callout"><p><i class="bi bi-check-circle-fill" aria-hidden="true"></i> You have already applied to this internship.</p></div>
                    <a href="{{ route('student.applications.index') }}" class="btn btn-primary w-100">View my applications</a>
                @elseif($hasOjt)
                    <div class="sb-callout" data-has-ojt><p><i class="bi bi-lock-fill" aria-hidden="true"></i> {{ \App\Services\ApplicationService::HAS_OJT_MESSAGE }}</p></div>
                    <a href="{{ route('student.ojt.index') }}" class="btn btn-outline-primary w-100">Go to My OJT</a>
                @elseif(! $internship->isAcceptingApplications())
                    <div class="sb-callout"><p>{{ $internship->expires_at && $internship->expires_at->isPast() ? 'This internship posting has expired and is no longer accepting applications.' : 'This internship is no longer accepting applications.' }}</p></div>
                @elseif($eligibilityReasons !== [])
                    <div class="sb-callout">
                        <h3>You are not yet eligible to apply.</h3>
                        <ul>
                            @foreach($eligibilityReasons as $reason)
                                <li>{{ $reason }}</li>
                            @endforeach
                        </ul>
                    </div>
                @elseif($needsCharacterReference)
                    <div class="sb-callout"><p>This internship requires at least one character reference. <a href="{{ route('student.profile.edit') }}#references">Add a reference</a>, then come back to apply.</p></div>
                @elseif($resumes->isEmpty())
                    <p class="sb-note">Create a resume before applying.</p>
                    <a href="{{ route('student.resume.index') }}" class="btn btn-outline-primary w-100">Manage my resumes</a>
                @else
                    <p class="sb-note" data-apply-hint>Pick a resume and add an optional cover letter.</p>
                    <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#idApplyModal"><i class="bi bi-send" aria-hidden="true"></i> Apply</button>
                @endif
            </div>
        </section>

        <section class="card" aria-labelledby="idFeedbackTitle">
            <div class="card-header"><h2 id="idFeedbackTitle">Recommendation feedback</h2></div>
            <div class="card-body sb-stack sb-stack--tight">
                @if($recommendationFeedback)
                    @php $helpful = $recommendationFeedback->feedback === 'helpful'; @endphp
                    <p>
                        <span class="sb-status {{ $helpful ? 'sb-status--success' : 'sb-status--neutral' }}">
                            <i class="bi {{ $helpful ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-down-fill' }}" aria-hidden="true"></i>
                            Marked {{ $helpful ? 'helpful' : 'not helpful' }}
                        </span>
                    </p>
                    @if($recommendationFeedback->comment)
                        <p class="sb-note">“{{ $recommendationFeedback->comment }}”</p>
                    @endif
                @endif

                <details class="sb-disclosure" @if($feedbackOpen) open @endif>
                    <summary>{{ $recommendationFeedback ? 'Edit feedback' : 'Rate this recommendation' }}</summary>
                    <form method="POST" action="{{ route('student.internships.recommendation-feedback', $internship) }}">
                        @csrf
                        <fieldset class="mb-3">
                            <legend class="form-label">Was this recommendation helpful?</legend>
                            <div class="sb-choice-pills">
                                <label class="sb-choice-pill"><input type="radio" name="feedback" id="idFeedbackHelpful" value="helpful" @checked(old('feedback', $recommendationFeedback?->feedback) === 'helpful')><span><i class="bi bi-hand-thumbs-up" aria-hidden="true"></i>&nbsp;Helpful</span></label>
                                <label class="sb-choice-pill"><input type="radio" name="feedback" id="idFeedbackNot" value="not_helpful" @checked(old('feedback', $recommendationFeedback?->feedback) === 'not_helpful')><span><i class="bi bi-hand-thumbs-down" aria-hidden="true"></i>&nbsp;Not helpful</span></label>
                            </div>
                            @error('feedback')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </fieldset>
                        <div class="mb-3">
                            <label class="form-label" for="idFeedbackComment">Comment <span class="text-muted">(optional)</span></label>
                            <textarea class="form-control @error('comment') is-invalid @enderror" id="idFeedbackComment" name="comment" rows="3" placeholder="Share why this recommendation was helpful or not helpful...">{{ old('comment', $recommendationFeedback?->comment) }}</textarea>
                            @error('comment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-star" aria-hidden="true"></i> Save feedback</button>
                    </form>
                </details>
            </div>
        </section>
    </aside>
</div>

{{-- Apply: resume + optional cover letter (shared AJAX handler in resources/js/app.js) --}}
@if($canApply)
    <div class="modal fade" id="idApplyModal" tabindex="-1" aria-labelledby="idApplyModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('student.internships.apply', $internship) }}" class="modal-content" data-ajax-application>
                @csrf
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title h5" id="idApplyModalTitle">Apply to {{ $internship->title }}</h2>
                        <p class="sb-note mb-0">{{ $internship->employer->company_name }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="application-resume">Resume <span class="sb-req" aria-hidden="true">*</span></label>
                        <select class="form-select" id="application-resume" name="resume_id" required>
                            <option value="">Choose a resume</option>
                            @foreach($resumes as $resume)
                                <option value="{{ $resume->id }}" @selected($resumes->count() === 1)>{{ $resume->category?->label() ?? 'Resume' }}{{ $resume->generated_at ? ' · '.$resume->generated_at->format('M Y') : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="application-cover-letter">Cover letter <span class="text-muted">(optional)</span></label>
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
@endsection

@push('scripts')
<script>
    // Applied from the dialog: the Application card switches to the "already applied" state.
    document.getElementById('idApplyModal')?.addEventListener('sb:application-submitted', (event) => {
        const body = document.querySelector('[data-apply-body]');
        if (!body) return;
        body.querySelectorAll('[data-apply-hint], [data-bs-target="#idApplyModal"]').forEach((element) => element.remove());
        const notice = document.createElement('div');
        notice.className = 'sb-callout';
        notice.innerHTML = '<p><i class="bi bi-check-circle-fill" aria-hidden="true"></i> You have already applied to this internship.</p>';
        const link = document.createElement('a');
        link.className = 'btn btn-primary w-100';
        link.href = event.detail?.applications_url || @json(route('student.applications.index'));
        link.textContent = 'View my applications';
        body.append(notice, link);
    });
</script>
@endpush
