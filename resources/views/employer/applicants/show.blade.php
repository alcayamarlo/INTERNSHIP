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
            <div class="card-header bg-white"><h5 class="mb-0">Portfolio & Certificates</h5></div>
            <div class="card-body">
                @if($application->student->portfolios->count())
                    <h6>Portfolio Items</h6>
                    <ul>@foreach($application->student->portfolios as $p)<li>{{ $p->title }} ({{ $p->type->label() }})</li>@endforeach</ul>
                @endif
                @if($application->student->certificates->count())
                    <h6>Certificates</h6>
                    <ul>@foreach($application->student->certificates as $c)<li>{{ $c->title }} — {{ $c->issuer ?? 'N/A' }}</li>@endforeach</ul>
                @endif
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-body d-grid gap-2">
                <a href="{{ route('employer.applicants.resume', $application) }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-pdf"></i> Download Resume</a>
                <a href="{{ route('messages.show', $application->student->user) }}" class="btn btn-outline-secondary"><i class="bi bi-chat"></i> Message Student</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white"><h5 class="mb-0">Update Status</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('employer.applicants.status', $application) }}">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                            @foreach(\App\Enums\ApplicationStatus::cases() as $status)
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
            </div>
        </div>
    </div>
</div>
@endsection
