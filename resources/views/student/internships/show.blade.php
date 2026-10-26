@extends('layouts.app')

@section('title', $internship->title)

@section('content')
<x-breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('student.dashboard')], ['label' => 'Internships', 'url' => route('student.internships.index')], ['label' => $internship->title]]" />
<x-page-header :title="$internship->title" :subtitle="$internship->employer->company_name">
    <x-status-badge :status="$internship->status" :label="ucfirst($internship->status)" />
    <a href="{{ route('student.internships.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Internships</a>
</x-page-header>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card" id="applicationCard">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-3 mb-4 text-muted small">
                    @if($internship->location)<span><i class="bi bi-geo-alt"></i> {{ $internship->location }}</span>@endif
                    <span><i class="bi bi-laptop"></i> {{ $internship->work_setup->label() }}</span>
                    @if($internship->duration)<span><i class="bi bi-clock"></i> {{ $internship->duration }}</span>@endif
                    @if($internship->allowance)<span><i class="bi bi-cash"></i> ₱{{ number_format($internship->allowance, 2) }}/mo</span>@endif
                </div>

                <h2 class="h6">Description</h2>
                <p>{!! nl2br(e($internship->description)) !!}</p>

                @if($internship->responsibilities)
                    <h2 class="h6">Responsibilities</h2>
                    <p>{!! nl2br(e($internship->responsibilities)) !!}</p>
                @endif

                @if($internship->requirements)
                    <h2 class="h6">General Requirements</h2>
                    <p>{!! nl2br(e($internship->requirements)) !!}</p>
                @endif

                @if($internship->requirementsList->count())
                    <h2 class="h6">Competency Requirements</h2>
                    <ul>
                        @foreach($internship->requirementsList as $req)
                            <li>{{ $req->requirement_name }} — <strong>{{ $req->required_level instanceof \App\Enums\ProficiencyLevel ? $req->required_level->label() : ucfirst($req->required_level) }}</strong></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <h2 class="h6">Your Match Score</h2>
                <div class="display-4 fw-bold {{ $matchPercentage >= 70 ? 'text-success' : ($matchPercentage >= 40 ? 'text-warning' : 'text-secondary') }}">
                    {{ $matchPercentage }}%
                </div>
                <p class="text-muted small">Based on your competencies</p>
            </div>

            @if($matchDetails['total_requirements'] > 0)
                <div class="card-body border-top pt-3" id="matchBreakdown">
                    <h3 class="h6">Why this score</h3>
                    <p class="text-muted small mb-3">{{ $matchDetails['explanation'] }}</p>

                    @if(count($matchDetails['matched_requirements']))
                        <ul class="list-unstyled small mb-3">
                            @foreach($matchDetails['matched_requirements'] as $item)
                                <li class="mb-1 text-success"><i class="bi bi-check-circle-fill"></i> {{ $item['name'] }} <span class="text-muted">({{ $item['required_level'] }} required — met)</span></li>
                            @endforeach
                        </ul>
                    @endif

                    @if(count($matchDetails['missing_requirements']))
                        <ul class="list-unstyled small mb-3">
                            @foreach($matchDetails['missing_requirements'] as $item)
                                <li class="mb-1 text-secondary"><i class="bi bi-x-circle"></i> {{ $item['name'] }} <span class="text-muted">({{ $item['required_level'] }} required)</span><br><span class="ms-4 text-muted">{{ $item['reason_label'] }}</span></li>
                            @endforeach
                        </ul>
                        <a href="{{ route('student.competencies.create') }}" class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-plus-lg"></i> Add or update a competency</a>
                    @endif
                </div>
            @endif
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h6">Recommendation Feedback</h2>
                @if($recommendationFeedback)
                    <div class="alert alert-success mb-3">
                        <i class="bi bi-check-circle"></i> You previously marked this recommendation as <strong>{{ $recommendationFeedback->feedback === 'helpful' ? 'helpful' : 'not helpful' }}</strong>.
                    </div>
                @endif

                <form method="POST" action="{{ route('student.internships.recommendation-feedback', $internship) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Was this recommendation helpful?</label>
                        <div class="d-flex gap-2">
                            <label class="btn btn-outline-success flex-fill @if(old('feedback', $recommendationFeedback?->feedback) === 'helpful') active @endif">
                                <input type="radio" class="form-check-input me-1" name="feedback" value="helpful" @checked(old('feedback', $recommendationFeedback?->feedback) === 'helpful')>
                                Helpful
                            </label>
                            <label class="btn btn-outline-danger flex-fill @if(old('feedback', $recommendationFeedback?->feedback) === 'not_helpful') active @endif">
                                <input type="radio" class="form-check-input me-1" name="feedback" value="not_helpful" @checked(old('feedback', $recommendationFeedback?->feedback) === 'not_helpful')>
                                Not helpful
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Comment <span class="text-muted">(optional)</span></label>
                        <textarea class="form-control" name="comment" rows="3" placeholder="Share why this recommendation was helpful or not helpful...">{{ old('comment', $recommendationFeedback?->comment) }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-star"></i> Save Feedback</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                @if($hasApplied)
                    <div class="alert alert-info mb-0"><i class="bi bi-check-circle"></i> You have already applied to this internship.</div>
                    <a href="{{ route('student.applications.index') }}" class="btn btn-outline-primary w-100 mt-3">View My Applications</a>
                @elseif(! $internship->isAcceptingApplications())
                    <div class="alert alert-secondary mb-0">{{ $internship->expires_at && $internship->expires_at->isPast() ? 'This internship posting has expired and is no longer accepting applications.' : 'This internship is no longer accepting applications.' }}</div>
                @elseif($eligibilityReasons !== [])
                    <div class="alert alert-warning mb-0">
                        <strong>You are not yet eligible to apply.</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($eligibilityReasons as $reason)
                                <li>{{ $reason }}</li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <h2 class="h6">Apply Now</h2>
                    @if($resumes->isEmpty())
                        <p class="text-muted">Create a resume before applying. Archived resumes cannot be submitted.</p>
                        <a href="{{ route('student.resume.index') }}" class="btn btn-outline-primary w-100">Manage My Resumes</a>
                    @else
                    <form method="POST" action="{{ route('student.internships.apply', $internship) }}" data-ajax-application>
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="application-resume">Resume</label>
                            <select class="form-select" id="application-resume" name="resume_id" required>
                                <option value="">Choose a resume</option>
                                @foreach($resumes as $resume)
                                    <option value="{{ $resume->id }}">{{ $resume->category?->label() ?? 'Resume' }}{{ $resume->generated_at ? ' · '.$resume->generated_at->format('M Y') : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cover Letter <span class="text-muted">(optional)</span></label>
                            <textarea class="form-control" name="cover_letter" rows="5" placeholder="Tell the employer why you're a great fit...">{{ old('cover_letter') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-send"></i> Submit Application</button>
                        <div data-application-feedback class="small mt-2" role="status" aria-live="polite"></div>
                    </form>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
