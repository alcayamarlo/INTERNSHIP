@extends('layouts.app')

@section('title', $internship->title)

@section('content')
<div class="mb-3">
    <a href="{{ route('student.internships.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Internships</a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h1 class="h3">{{ $internship->title }}</h1>
                        <p class="text-muted mb-0">{{ $internship->employer->company_name }}</p>
                    </div>
                    <span class="badge bg-{{ $internship->status === 'open' ? 'success' : 'secondary' }} fs-6">{{ ucfirst($internship->status) }}</span>
                </div>

                <div class="d-flex flex-wrap gap-3 mb-4 text-muted small">
                    @if($internship->location)<span><i class="bi bi-geo-alt"></i> {{ $internship->location }}</span>@endif
                    <span><i class="bi bi-laptop"></i> {{ $internship->work_setup->label() }}</span>
                    @if($internship->duration)<span><i class="bi bi-clock"></i> {{ $internship->duration }}</span>@endif
                    @if($internship->allowance)<span><i class="bi bi-cash"></i> ₱{{ number_format($internship->allowance, 2) }}/mo</span>@endif
                </div>

                <h5>Description</h5>
                <p>{!! nl2br(e($internship->description)) !!}</p>

                @if($internship->responsibilities)
                    <h5>Responsibilities</h5>
                    <p>{!! nl2br(e($internship->responsibilities)) !!}</p>
                @endif

                @if($internship->requirements)
                    <h5>General Requirements</h5>
                    <p>{!! nl2br(e($internship->requirements)) !!}</p>
                @endif

                @if($internship->requirementsList->count())
                    <h5>Competency Requirements</h5>
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
                <h5>Your Match Score</h5>
                <div class="display-4 fw-bold {{ $matchPercentage >= 70 ? 'text-success' : ($matchPercentage >= 40 ? 'text-warning' : 'text-secondary') }}">
                    {{ $matchPercentage }}%
                </div>
                <p class="text-muted small">Based on your competencies</p>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5>Recommendation Feedback</h5>
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
                @elseif($internship->status !== 'open')
                    <div class="alert alert-secondary mb-0">This internship is no longer accepting applications.</div>
                @else
                    <h5>Apply Now</h5>
                    <form method="POST" action="{{ route('student.internships.apply', $internship) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Cover Letter <span class="text-muted">(optional)</span></label>
                            <textarea class="form-control" name="cover_letter" rows="5" placeholder="Tell the employer why you're a great fit...">{{ old('cover_letter') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-send"></i> Submit Application</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
