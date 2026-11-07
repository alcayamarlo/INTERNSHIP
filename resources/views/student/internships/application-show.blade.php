@extends('layouts.app')

@section('title', 'Application Details')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/student-internships.css') }}?v={{ @filemtime(public_path('css/student-internships.css')) }}">
@endpush

@php
    use App\Enums\ApplicationStatus as Status;

    $internship = $application->internship;
    $employer = $internship->employer;
    $companyUser = $employer?->user;
    $status = $application->status;
    $canMessage = $companyUser && auth()->user()->can('message.send', $companyUser);
    $editable = in_array($status, [Status::Applied, Status::Submitted], true); // same rule as the controller
    $notProvided = 'Not provided';

    // ---- History, oldest first ----
    $history = $application->statusHistory->sortBy('created_at')->values();
    $firstOf = fn (array $statuses) => $history->first(fn ($entry) => in_array($entry->status, $statuses, true));
    $lastOf = fn (array $statuses) => $history->last(fn ($entry) => in_array($entry->status, $statuses, true));
    $reviewedEntry = $firstOf(['reviewed']);
    $interviewEntry = $lastOf(['interview']);
    $decisionEntry = $lastOf(['accepted', 'rejected', 'completed', 'withdrawn']);

    // ---- Stepper: Applied > Reviewed > Interview > Decision ----
    $stage = match ($status) {
        Status::Applied, Status::Submitted => 0,
        Status::Reviewed => 1,
        Status::Interview => 2,
        default => 3,
    };
    $negative = in_array($status, [Status::Rejected, Status::Withdrawn], true);
    $fmt = fn ($date) => $date?->format('M j, Y');
    $steps = [
        ['label' => 'Applied', 'state' => 'done', 'note' => $fmt($application->applied_at) ?? '—'],
        ['label' => 'Reviewed', 'state' => $stage >= 1 ? 'done' : 'pending', 'note' => $stage >= 1 ? ($fmt($reviewedEntry?->created_at) ?? '—') : 'Pending'],
        match (true) {
            $stage >= 2 && ($interviewEntry || $application->interview_at) => ['label' => 'Interview', 'state' => 'done', 'note' => $fmt($application->interview_at ?? $interviewEntry?->created_at)],
            $stage === 3 => ['label' => 'Interview', 'state' => 'skipped', 'note' => 'Skipped'],
            $application->interview_at !== null => ['label' => 'Interview', 'state' => 'pending', 'note' => $fmt($application->interview_at)],
            default => ['label' => 'Interview', 'state' => 'pending', 'note' => 'Not scheduled'],
        },
        $stage === 3
            ? ['label' => $status->label(), 'state' => $negative ? 'negative' : 'done', 'note' => $fmt($decisionEntry?->created_at) ?? '—']
            : ['label' => 'Decision', 'state' => 'pending', 'note' => 'Pending'],
    ];
    $progress = round($stage / 3, 3); // 0 to 1: how far the stepper line is filled

    // ---- Timeline: "Application submitted" first, then each status change ----
    $timeline = $history->reject(fn ($entry) => in_array($entry->status, ['applied', 'submitted'], true))->values();

    // ---- Interview message drafts (open the chat with the text filled in; nothing is sent automatically) ----
    $interviewWhen = $application->interview_at?->format('l, M j, Y \a\t g:i A');
    $confirmDraft = $interviewWhen ? "Hi! I confirm my interview for {$internship->title} on {$interviewWhen}. Thank you." : null;
    $rescheduleDraft = $interviewWhen ? "Hi! About my interview for {$internship->title} on {$interviewWhen}: could we please move it to another time? I am available on " : null;

    $resume = $application->resume;
    $resumeName = $resume ? ($resume->category?->label() ?? 'Resume').($resume->file_path ? ' · '.basename($resume->file_path) : '') : null;
    $certificateCount = $application->student->competencies()->count();
    $logo = \App\Support\Avatar::url($employer?->logo);
@endphp

@section('content')
<div class="ap-page">

<x-breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('student.dashboard')], ['label' => 'Internships', 'url' => route('student.internships.index')], ['label' => 'My Applications', 'url' => route('student.applications.index')], ['label' => $internship->title]]" />

{{-- =========================================================
     HEADER
========================================================== --}}
<header class="ap-head">
    <div class="ap-head-main">
        <div class="ap-title-row">
            <h1>{{ $internship->title }}</h1>
            <span class="ap-status ap-status--{{ $status->value }}">{{ $status->label() }}</span>
        </div>
        <p class="ap-subtitle">{{ $employer->company_name }} · {{ $internship->location ?: 'Location not provided' }} · Application #{{ $application->id }}</p>
    </div>
    <div class="ap-head-actions">
        <a href="{{ route('student.applications.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to applications</a>
    </div>
</header>

{{-- =========================================================
     PROGRESS STEPPER
========================================================== --}}
<section class="ib-card ap-stepper" aria-label="Application progress">
    <ol class="ap-steps" style="--ap-progress: {{ $progress }}">
        @foreach($steps as $index => $step)
            @php $current = $index === $stage; @endphp
            <li class="ap-step is-{{ $step['state'] }} {{ $current ? 'is-current' : '' }}" @if($current) aria-current="step" @endif>
                <span class="ap-step-dot" aria-hidden="true">
                    @if($step['state'] === 'done')
                        <i class="bi bi-check-lg"></i>
                    @elseif($step['state'] === 'negative')
                        <i class="bi bi-x-lg"></i>
                    @else
                        {{ $index + 1 }}
                    @endif
                </span>
                <span class="ap-step-label">{{ $step['label'] }}</span>
                <span class="ap-step-note">{{ $step['note'] }}</span>
            </li>
        @endforeach
    </ol>
</section>

<div class="ap-layout">

    {{-- =========================================================
         LEFT
    ========================================================== --}}
    <div class="ap-main">

        {{-- Application summary --}}
        <section class="ib-card ap-card" aria-labelledby="apSummaryTitle">
            <h2 class="ap-card-title" id="apSummaryTitle">Application summary</h2>
            <dl class="ap-summary">
                <div><dt>Company</dt><dd>{{ $employer->company_name ?: '—' }}</dd></div>
                <div><dt>Location</dt><dd>{{ $internship->location ?: '—' }}</dd></div>
                <div><dt>Status</dt><dd><span class="ap-status ap-status--{{ $status->value }}">{{ $status->label() }}</span></dd></div>
                <div><dt>Applied on</dt><dd>{{ $application->applied_at?->format('M j, Y · g:i A') ?? '—' }}</dd></div>
                <div><dt>Match score</dt><dd><span class="ib-match"><span class="ib-match-dot" aria-hidden="true"></span>{{ $application->match_percentage ?? 0 }}% match</span></dd></div>
                <div><dt>Program</dt><dd>{{ $internship->programs->isEmpty() ? 'All programs' : $internship->programs->pluck('code')->implode(', ') }}</dd></div>
            </dl>
        </section>

        {{-- Interview schedule (only when an interview exists) --}}
        @if($application->interview_at)
            <section class="ib-card ap-card ap-interview" id="apInterview" aria-labelledby="apInterviewTitle">
                <h2 class="ap-card-title" id="apInterviewTitle"><i class="bi bi-calendar-event" aria-hidden="true"></i> Interview schedule</h2>
                <dl class="ap-summary">
                    <div><dt>Date and time</dt><dd>{{ $application->interview_at->format('l, M j, Y · g:i A') }}</dd></div>
                    <div><dt>Mode</dt><dd class="ap-muted-value">{{ $notProvided }}</dd></div>
                    <div><dt>Venue or link</dt><dd class="ap-muted-value">{{ $notProvided }}</dd></div>
                    <div><dt>Contact person</dt><dd>{{ $employer->contact_person ?: $notProvided }}</dd></div>
                    <div class="ap-summary-wide"><dt>Notes from the employer</dt><dd>{{ $interviewEntry?->notes ?: $notProvided }}</dd></div>
                </dl>
                <div class="ap-actions-row">
                    @if($canMessage)
                        <a href="{{ route('messages.show', ['user' => $companyUser, 'draft' => $confirmDraft]) }}" class="btn btn-primary"><i class="bi bi-check2-circle" aria-hidden="true"></i> Confirm</a>
                        <a href="{{ route('messages.show', ['user' => $companyUser, 'draft' => $rescheduleDraft]) }}" class="btn btn-outline-primary"><i class="bi bi-calendar2-week" aria-hidden="true"></i> Request reschedule</a>
                        <span class="ap-hint">Opens a message to {{ $employer->company_name }} for you to send.</span>
                    @else
                        <span class="ap-hint">Messaging this company is not available yet.</span>
                    @endif
                </div>
            </section>
        @endif

        {{-- Cover letter --}}
        <section class="ib-card ap-card" aria-labelledby="apCoverTitle">
            <div class="ap-card-head">
                <h2 class="ap-card-title" id="apCoverTitle">Cover letter</h2>
                @if($editable)
                    <a href="{{ route('student.applications.edit', $application) }}" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#apEditModal"><i class="bi bi-pencil" aria-hidden="true"></i> Edit</a>
                @endif
            </div>
            @if($application->cover_letter)
                <p class="ap-letter">{!! nl2br(e($application->cover_letter)) !!}</p>
            @else
                <p class="ap-empty">No cover letter provided.</p>
            @endif
        </section>

        {{-- Application timeline --}}
        <section class="ib-card ap-card" aria-labelledby="apTimelineTitle">
            <h2 class="ap-card-title" id="apTimelineTitle">Application timeline</h2>
            <ol class="ap-timeline">
                <li class="ap-tl-item">
                    <span class="ap-tl-dot" aria-hidden="true"></span>
                    <div class="ap-tl-body">
                        <div class="ap-tl-top"><strong>Application submitted</strong><time datetime="{{ $application->applied_at?->toIso8601String() }}">{{ $application->applied_at?->format('M j, Y · g:i A') ?? '—' }}</time></div>
                        <p class="ap-tl-note">Sent to {{ $employer->company_name }}{{ $resume ? ' with your '.($resume->category?->label() ?? 'resume') : '' }}.</p>
                    </div>
                </li>
                @foreach($timeline as $entry)
                    @php $entryStatus = Status::tryFrom($entry->status); @endphp
                    <li class="ap-tl-item">
                        <span class="ap-tl-dot ap-tl-dot--{{ $entry->status }}" aria-hidden="true"></span>
                        <div class="ap-tl-body">
                            <div class="ap-tl-top">
                                <strong>{{ $entryStatus?->label() ?? \Illuminate\Support\Str::headline($entry->status) }}</strong>
                                <time datetime="{{ $entry->created_at?->toIso8601String() }}">{{ $entry->created_at?->format('M j, Y · g:i A') ?? '—' }}</time>
                            </div>
                            <p class="ap-tl-meta">Updated by {{ $entry->actor?->name ?? 'System' }}</p>
                            @if($entry->interview_at)
                                <p class="ap-tl-meta"><i class="bi bi-calendar-event" aria-hidden="true"></i> Interview: {{ $entry->interview_at->format('M j, Y · g:i A') }}</p>
                            @endif
                            @if($entry->notes)
                                <p class="ap-tl-note">{{ $entry->notes }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
            @if($timeline->isEmpty())
                <p class="ap-empty mb-0">Status updates from the employer will appear here.</p>
            @endif
        </section>
    </div>

    {{-- =========================================================
         RIGHT (sticky)
    ========================================================== --}}
    <aside class="ap-side" aria-label="Next step and actions">

        {{-- Next step: tint and text follow the status --}}
        <section class="ib-card ap-card ap-next ap-next--{{ $status->value }}" aria-labelledby="apNextTitle">
            <h2 class="ap-card-label" id="apNextTitle">Next step</h2>
            @switch($status)
                @case(Status::Applied)
                @case(Status::Submitted)
                    <p class="ap-next-title"><i class="bi bi-hourglass-split" aria-hidden="true"></i> Waiting for employer review</p>
                    <p class="ap-next-text">{{ $employer->company_name }} has your application. You will be notified when they review it.</p>
                    @break
                @case(Status::Reviewed)
                    <p class="ap-next-title"><i class="bi bi-eye" aria-hidden="true"></i> Reviewed by the employer</p>
                    <p class="ap-next-text">Your application was reviewed. Watch for an interview invitation or a decision.</p>
                    @break
                @case(Status::Interview)
                    <p class="ap-next-title"><i class="bi bi-calendar-event" aria-hidden="true"></i> Interview</p>
                    <p class="ap-next-text">{{ $application->interview_at ? $application->interview_at->format('l, M j, Y · g:i A') : 'The schedule has not been set yet.' }}</p>
                    @if($application->interview_at)
                        <a href="#apInterview" class="ap-next-link">See interview details <i class="bi bi-arrow-down" aria-hidden="true"></i></a>
                    @endif
                    @break
                @case(Status::Accepted)
                @case(Status::Completed)
                    <p class="ap-next-title"><i class="bi bi-trophy" aria-hidden="true"></i> {{ $status === Status::Completed ? 'OJT completed' : 'You were accepted' }}</p>
                    <p class="ap-next-text">{{ $status === Status::Completed ? 'Your OJT for this internship is complete.' : 'Your OJT record is ready. Log your hours and track your progress there.' }}</p>
                    <a href="{{ route('student.ojt.index') }}" class="btn btn-sm btn-primary">Open my OJT record</a>
                    @break
                @case(Status::Rejected)
                    <p class="ap-next-title"><i class="bi bi-x-circle" aria-hidden="true"></i> Not selected</p>
                    <p class="ap-next-text">{{ $decisionEntry?->notes ?: 'The employer did not leave feedback.' }}</p>
                    <a href="{{ route('student.internships.index') }}" class="ap-next-link">Browse other internships <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    @break
                @default
                    <p class="ap-next-title"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Application withdrawn</p>
                    <p class="ap-next-text">This application is no longer active.</p>
            @endswitch
        </section>

        {{-- Submitted documents --}}
        <section class="ib-card ap-card" aria-labelledby="apDocsTitle">
            <h2 class="ap-card-label" id="apDocsTitle">Submitted documents</h2>
            <ul class="ap-docs">
                <li>
                    <i class="bi bi-file-earmark-person" aria-hidden="true"></i>
                    <div><span class="ap-doc-label">Resume</span><span class="ap-doc-value">{{ $resumeName ?? $notProvided }}</span></div>
                    @if($resume)
                        <a href="{{ route('student.resume.view', $resume) }}" class="ap-doc-link" target="_blank" rel="noopener">View</a>
                    @endif
                </li>
                <li>
                    <i class="bi bi-envelope-paper" aria-hidden="true"></i>
                    <div><span class="ap-doc-label">Cover letter</span><span class="ap-doc-value">{{ $application->cover_letter ? 'Included' : $notProvided }}</span></div>
                </li>
                <li>
                    <i class="bi bi-award" aria-hidden="true"></i>
                    <div><span class="ap-doc-label">Certificates on your profile</span><span class="ap-doc-value">{{ $certificateCount }}</span></div>
                    <a href="{{ route('student.competencies.index') }}" class="ap-doc-link">Manage</a>
                </li>
            </ul>
        </section>

        {{-- Employer --}}
        <section class="ib-card ap-card" aria-labelledby="apEmployerTitle">
            <h2 class="ap-card-label" id="apEmployerTitle">Employer</h2>
            <div class="ap-employer">
                <span class="ap-logo" aria-hidden="true">
                    @if($logo)
                        <img src="{{ $logo }}" alt="" onerror="this.remove()">
                    @endif
                    <i class="bi bi-building"></i>
                </span>
                <div>
                    <p class="ap-employer-name">{{ $employer->company_name }}</p>
                    <p class="ap-employer-meta"><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $employer->address ?: ($internship->location ?: $notProvided) }}</p>
                </div>
            </div>
            <div class="ap-actions-row">
                <a href="{{ route('student.internships.show', $internship) }}" class="btn btn-sm btn-outline-secondary">View internship</a>
                @if($canMessage)
                    <a href="{{ route('messages.show', $companyUser) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-chat-dots" aria-hidden="true"></i> Message</a>
                @endif
            </div>
        </section>

        {{-- Actions --}}
        <section class="ib-card ap-card" aria-labelledby="apActionsTitle">
            <h2 class="ap-card-label" id="apActionsTitle">Actions</h2>
            @if($editable)
                <div class="ap-actions-stack">
                    <a href="{{ route('student.applications.edit', $application) }}" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#apEditModal"><i class="bi bi-pencil" aria-hidden="true"></i> Edit application</a>
                    {{-- Uses the app's shared confirmation dialog (data-confirm) before withdrawing --}}
                    <form method="POST" action="{{ route('student.applications.destroy', $application) }}"
                          data-confirm="Withdraw your application to {{ $internship->title }} at {{ $employer->company_name }}? The company will no longer see it."
                          data-confirm-title="Withdraw application" data-confirm-label="Withdraw">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-x-circle" aria-hidden="true"></i> Withdraw application</button>
                    </form>
                </div>
            @else
                <p class="ap-hint mb-0">Applications can only be edited or withdrawn before the employer reviews them.</p>
            @endif
        </section>
    </aside>
</div>

{{-- =========================================================
     EDIT: cover letter (same endpoint as the Edit application page)
========================================================== --}}
@if($editable)
    <div class="modal fade" id="apEditModal" tabindex="-1" aria-labelledby="apEditTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form method="POST" action="{{ route('student.applications.update', $application) }}" class="modal-content">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title h5" id="apEditTitle">Edit application</h2>
                        <p class="ap-hint mb-0">{{ $internship->title }} · {{ $employer->company_name }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="apCoverLetter">Cover letter</label>
                    <textarea id="apCoverLetter" name="cover_letter" rows="9" maxlength="5000" class="form-control @error('cover_letter') is-invalid @enderror">{{ old('cover_letter', $application->cover_letter) }}</textarea>
                    @error('cover_letter')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
@endif

</div>
@endsection

@if($editable && $errors->has('cover_letter'))
    @push('scripts')
    <script>
        // The cover letter came back with an error: reopen the edit dialog.
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('apEditModal');
            if (modal && window.bootstrap) bootstrap.Modal.getOrCreateInstance(modal).show();
        });
    </script>
    @endpush
@endif
