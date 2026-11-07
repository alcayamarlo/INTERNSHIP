@extends('layouts.app')

@section('title', 'Student Profile')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/coordinator-student-profile.css') }}?v={{ @filemtime(public_path('css/coordinator-student-profile.css')) }}">
@endpush

@section('content')
<main class="student-profile-page">
    <a class="profile-back-link" href="{{ route('coordinator.students.index') }}">
        <span aria-hidden="true">←</span> Back to Students
    </a>

    <section class="profile-hero" aria-labelledby="student-profile-name">
        <div class="profile-ring" style="--profile-progress: {{ $profileCompletion }}%; --profile-color: {{ $profileCompletion >= 50 ? 'var(--sb-success-ink)' : 'var(--sb-warning-ink)' }};" role="img" aria-label="Profile {{ $profileCompletion }} percent complete">
            <div class="profile-ring-inner">{{ $initials }}</div>
        </div>

        <div class="profile-hero-main">
            <h1 id="student-profile-name">{{ $student->user->name }}</h1>
            <p class="profile-program-line">
                {{ $student->program ?: 'Program not set' }}<span aria-hidden="true"> · </span>{{ $student->year_level ?: 'Year level not set' }}
            </p>
            <p class="profile-institution">{{ $student->institution?->name ?? 'Institution not set' }}</p>
            <div class="profile-chips">
                <span class="profile-chip profile-chip-id">School ID {{ $student->student_id_number ?: 'Not provided' }}</span>
                @if($studentIdNeedsCorrection)
                    <span class="profile-chip profile-chip-warning">ID needs correction</span>
                @endif
                <span class="profile-chip">{{ $student->user->email }}</span>
                <span class="profile-chip">{{ $student->user->phone ?: 'Phone not provided' }}</span>
            </div>
        </div>

        <div class="profile-completion">
            <strong>{{ $profileCompletion }}%</strong>
            <span>Profile complete</span>
        </div>
    </section>

    <section class="profile-stats" aria-label="Submission summary">
        <article class="profile-stat-card">
            <span class="profile-stat-label">Certificates</span>
            <strong>{{ $competencies->count() }}</strong>
        </article>
        <article class="profile-stat-card">
            <span class="profile-stat-label">Older certificate files</span>
            <strong>{{ $certificates->count() }}</strong>
        </article>
        <article class="profile-stat-card">
            <span class="profile-stat-label">Portfolio items</span>
            <strong>{{ $portfolios->count() }}</strong>
        </article>
        <article class="profile-stat-card">
            <span class="profile-stat-label">Verification progress</span>
            <strong>{{ $verificationCounts['total'] ? $verificationCounts['verified'].'/'.$verificationCounts['total'].' verified' : '—' }}</strong>
            @if ($verificationCounts['pending'] > 0)
                <span class="profile-stat-caption">· {{ $verificationCounts['pending'] }} pending</span>
            @endif
        </article>
    </section>

    {{-- Three columns: details (left), review actions (middle), collections (right). --}}
    <div class="profile-grid">

        {{-- ===== Left: who the student is ===== --}}
        <div class="profile-col profile-col-left">
            <section class="profile-card">
                <header class="profile-section-heading profile-card-head"><h2>Student details</h2></header>
                <div class="profile-card-body">
                    <dl class="profile-details">
                        <div><dt>Program</dt><dd>{{ $student->program ?: 'Not set' }}</dd></div>
                        <div><dt>Year level</dt><dd>{{ $student->year_level ?: 'Not set' }}</dd></div>
                        <div>
                            <dt>School ID</dt>
                            <dd>
                                {{ $student->student_id_number ?: 'Not provided' }}
                                @if($studentIdNeedsCorrection)<span class="profile-chip profile-chip-warning">ID needs correction</span>@endif
                            </dd>
                        </div>
                        <div><dt>Phone</dt><dd>{{ $student->user->phone ?: 'Not provided' }}</dd></div>
                        <div><dt>Last active</dt><dd>{{ $student->user->last_login_at?->diffForHumans() ?? 'No activity recorded' }}</dd></div>
                    </dl>
                </div>
            </section>

            <section class="profile-card">
                <header class="profile-section-heading profile-card-head"><h2>Career objective</h2></header>
                <div class="profile-card-body">
                    <p class="profile-prose">{{ $student->career_objectives ?: 'Not added yet.' }}</p>
                </div>
            </section>

            <section class="profile-card">
                <header class="profile-section-heading profile-card-head"><h2>Internship preference</h2></header>
                <div class="profile-card-body">
                    <dl class="profile-details">
                        <div><dt>Field</dt><dd>{{ $student->preferred_internship_field ?: 'Not specified' }}</dd></div>
                        <div><dt>Work setup</dt><dd>{{ $student->preferred_work_setup ?: 'Not specified' }}</dd></div>
                        <div><dt>Location</dt><dd>{{ $student->preferred_location ?: 'Not specified' }}</dd></div>
                    </dl>
                </div>
            </section>
        </div>

        {{-- ===== Middle: review actions ===== --}}
        <div class="profile-col profile-col-middle">
            <section class="profile-card profile-review-controls">
                <header class="profile-section-heading profile-card-head">
                    <h2>Academic progress and profile verification</h2>
                    @if($student->profile_verified_at)
                        <span class="profile-status profile-status-verified">Profile verified</span>
                    @else
                        <form method="POST" action="{{ route('coordinator.students.verify-profile', $student) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">Verify profile</button>
                        </form>
                    @endif
                </header>
                <div class="profile-card-body">
                    <p class="profile-prose">Required internship hours: {{ $requiredInternshipHours ?? 'Not set for this program' }}</p>
                    <div class="profile-hours-progress" role="img" aria-label="{{ $renderedInternshipHours }} of {{ $requiredInternshipHours ?? 0 }} internship hours rendered">
                        <div class="profile-hours-progress-track"><span style="width: {{ $internshipHoursProgress }}%"></span></div>
                        <p>{{ $renderedInternshipHours }} rendered{{ $requiredInternshipHours ? ' / '.$requiredInternshipHours.' required hours ('.$internshipHoursProgress.'%)' : ' hours' }}</p>
                    </div>
                    <form method="POST" action="{{ route('coordinator.students.academic-readiness.update', $student) }}" class="profile-readiness-form">
                        @csrf
                        @method('PUT')
                        <label>
                            <span>Rendered internship hours</span>
                            <input type="number" name="rendered_internship_hours" min="0" max="10000" step="1" value="{{ old('rendered_internship_hours', $renderedInternshipHours) }}" required>
                        </label>
                        <label>
                            <span>GWA (from the registrar)</span>
                            <input type="number" name="gwa" min="1" max="5" step="0.01" value="{{ old('gwa', $student->gwa) }}" placeholder="e.g. 1.75">
                        </label>
                        <label class="profile-checkbox-label">
                            <input type="hidden" name="major_subjects_passed" value="0">
                            <input type="checkbox" name="major_subjects_passed" value="1" @checked(old('major_subjects_passed', $student->major_subjects_passed))>
                            Student has passed all major subjects
                        </label>
                        <button type="submit" class="btn btn-primary">Save academic progress</button>
                    </form>
                </div>
            </section>

            <section class="profile-card" aria-labelledby="eligibilityHeading" data-eligibility>
                <header class="profile-section-heading profile-card-head">
                    <h2 id="eligibilityHeading">Internship eligibility</h2>
                    <span class="profile-status {{ $eligibility['eligible'] ? 'profile-status-verified' : 'profile-status-pending' }}">{{ $eligibility['eligible'] ? 'Eligible to apply' : 'Not eligible yet' }}</span>
                </header>
                <div class="profile-card-body">
                    <p class="profile-prose">{{ $eligibility['source'] === 'program' ? 'Checked against the program criteria for this term.' : 'Checked against the school-wide rules (no program criteria this term).' }}</p>
                    @if($eligibility['rules'])
                        <ul class="list-unstyled profile-rules">
                            @foreach($eligibility['rules'] as $rule)
                                <li data-rule="{{ $rule['key'] }}">
                                    <i class="bi {{ $rule['passed'] ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' }}" aria-hidden="true"></i>
                                    <span><strong>{{ $rule['label'] }}</strong>: {{ $rule['detail'] }}</span>
                                    <span class="visually-hidden">{{ $rule['passed'] ? '(passed)' : '(failed)' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="profile-prose">No eligibility rules apply this term.</p>
                    @endif

                    {{-- One form per decision: the decision travels in a hidden field, not on the (auto-disabled) button.
                         The reject form uses display: contents so its reason field sits on its own line and
                         both buttons share the next line, while each control stays in its own form. --}}
                    <div class="profile-eligibility-actions">
                        <form method="POST" action="{{ route('coordinator.students.eligibility.store', $student) }}" class="profile-eligibility-reject">
                            @csrf
                            <input type="hidden" name="decision" value="rejected">
                            <div class="profile-eligibility-reason">
                                <label class="form-label" for="eligibilityReason">Reason <span class="text-muted small">(required to mark not eligible; the student sees it)</span></label>
                                <input id="eligibilityReason" type="text" name="reason" maxlength="1000" class="form-control @error('reason') is-invalid @enderror" value="{{ old('reason') }}" required>
                                @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <button type="submit" class="btn btn-outline-danger">Mark not eligible</button>
                        </form>
                        <form method="POST" action="{{ route('coordinator.students.eligibility.store', $student) }}">
                            @csrf
                            <input type="hidden" name="decision" value="approved">
                            <button type="submit" class="btn btn-success w-100">Approve</button>
                        </form>
                    </div>

                    @if($eligibilityHistory->isNotEmpty())
                        <h3 class="h6 mt-4">Decision history</h3>
                        <ul class="list-unstyled small mb-0">
                            @foreach($eligibilityHistory as $decision)
                                <li class="mb-1">
                                    <span class="badge {{ $decision->isRejected() ? 'text-bg-danger' : 'text-bg-success' }}">{{ $decision->isRejected() ? 'Not eligible' : 'Approved' }}</span>
                                    {{ $decision->created_at->format('M d, Y h:i A') }} · {{ $decision->decidedBy?->name ?? 'Coordinator' }}
                                    @if($decision->reason) · {{ $decision->reason }}@endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>

            @if($changeRequests->isNotEmpty())
                <section class="profile-card">
                    <header class="profile-section-heading profile-card-head">
                        <h2>Profile change requests</h2>
                        <span class="profile-count">{{ $changeRequests->where('status', 'pending')->count() }} pending</span>
                    </header>
                    <div class="profile-card-body profile-change-requests">
                        @foreach($changeRequests as $changeRequest)
                            <article class="profile-change-request">
                                <div class="profile-change-request-heading">
                                    <div>
                                        <h3>{{ match($changeRequest->field) { 'student_id_number' => 'Student ID', 'program_id' => 'Program', 'year_level' => 'Year level', default => ucfirst(str_replace('_', ' ', $changeRequest->field)) } }}</h3>
                                        <p>Current: {{ $changeRequest->current_value_label }} <span aria-hidden="true">→</span> Requested: {{ $changeRequest->requested_value_label }}</p>
                                    </div>
                                    <span class="profile-status profile-status-{{ $changeRequest->status }}">{{ ucfirst($changeRequest->status) }}</span>
                                </div>
                                @if($changeRequest->student_note)
                                    <p class="profile-prose">{{ $changeRequest->student_note }}</p>
                                @endif
                                @if($changeRequest->coordinator_note)
                                    <p class="profile-activity-note">Coordinator note: {{ $changeRequest->coordinator_note }}</p>
                                @endif
                                @if($changeRequest->status === 'pending')
                                    <div class="profile-change-request-actions">
                                        <form method="POST" action="{{ route('coordinator.students.change-requests.review', [$student, $changeRequest]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="decision" value="approved">
                                            <button type="submit" class="btn btn-primary">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('coordinator.students.change-requests.review', [$student, $changeRequest]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="decision" value="rejected">
                                            <label>
                                                <span class="visually-hidden">Required rejection note</span>
                                                <input type="text" name="coordinator_note" maxlength="2000" placeholder="Reason for rejection" required>
                                            </label>
                                            <button type="submit" class="btn btn-outline-secondary">Reject</button>
                                        </form>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        {{-- ===== Right: collections and activity ===== --}}
        <div class="profile-col profile-col-right">
            <section class="profile-card">
                <header class="profile-section-heading profile-card-head">
                    <h2>Certificates</h2>
                    <span class="profile-count">{{ $competencies->count() }}</span>
                </header>
                <div class="profile-card-body">
                    @if ($competencies->isEmpty())
                        <p class="profile-empty">No certificates added yet.</p>
                    @else
                        <div class="profile-skill-grid">
                            @foreach ($competencies as $competency)
                                @php
                                    $filledSegments = match ($competency['level']) {
                                        'beginner' => 1,
                                        'intermediate' => 2,
                                        default => 3,
                                    };
                                @endphp
                                <article class="profile-item-card">
                                    <div class="profile-item-heading">
                                        <div>
                                            <h3>{{ $competency['name'] }}</h3>
                                            <p>{{ $competency['category'] }}@if(! empty($competency['issuer'])) · {{ $competency['issuer'] }}@endif</p>
                                            @if(! empty($competency['skills']))<p>Skills learned: {{ implode(', ', $competency['skills']) }}</p>@endif
                                        </div>
                                    </div>
                                    <div class="profile-level" aria-label="{{ $competency['level_label'] }} proficiency">
                                        <div class="profile-level-segments" aria-hidden="true">
                                            @for ($segment = 1; $segment <= 3; $segment++)
                                                <span class="{{ $segment <= $filledSegments ? 'is-filled' : '' }}"></span>
                                            @endfor
                                        </div>
                                        <div class="profile-level-labels"><span>Beginner</span><span>Intermediate</span><span>Advanced</span></div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            <section class="profile-card">
                <header class="profile-section-heading profile-card-head">
                    <h2>Skills</h2>
                    <span class="profile-count">{{ $skills->count() }}</span>
                </header>
                <div class="profile-card-body">
                    @if ($skills->isEmpty())
                        <p class="profile-empty">No skills added yet.</p>
                    @else
                        <div class="profile-chips" aria-label="Self-declared skills">
                            @foreach ($skills as $skill)
                                <span class="profile-chip">{{ $skill['name'] }}@if($skill['level_label']) · {{ $skill['level_label'] }}@endif @if($skill['has_certificate'])<i class="bi bi-file-earmark-check" aria-hidden="true" title="Has certificate"></i><span class="visually-hidden">(has certificate)</span>@endif</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            <section class="profile-card">
                <header class="profile-section-heading profile-card-head">
                    <h2>Older certificate files</h2>
                    <span class="profile-count">{{ $certificates->count() }}</span>
                </header>
                <div class="profile-card-body">
                    @if ($certificates->isEmpty())
                        <p class="profile-empty">No certificates uploaded yet.</p>
                    @else
                        <div class="profile-item-list">
                            @foreach ($certificates as $certificate)
                                <article class="profile-item-card profile-certificate-card">
                                    <div>
                                        <h3>{{ $certificate['title'] }}</h3>
                                        <p>{{ $certificate['issuer'] ?: 'Issuer not specified' }}</p>
                                        @if($certificate['has_file'])
                                            <a href="{{ route('coordinator.verification.evidence', ['certificates', $certificate['id']]) }}" target="_blank" rel="noopener" class="small">View file</a>
                                        @endif
                                    </div>
                                    <span class="profile-status profile-status-{{ $certificate['status'] }}">{{ ucfirst($certificate['status']) }}</span>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            <section class="profile-card">
                <header class="profile-section-heading profile-card-head">
                    <h2>Portfolio</h2>
                    <span class="profile-count">{{ $portfolios->count() }}</span>
                </header>
                <div class="profile-card-body">
                    @if ($portfolios->isEmpty())
                        <p class="profile-empty">No portfolio items yet.</p>
                    @else
                        <div class="profile-item-list">
                            @foreach ($portfolios as $portfolio)
                                <article class="profile-item-card profile-certificate-card">
                                    <div>
                                        <h3>{{ $portfolio['title'] }}</h3>
                                        <p>{{ $portfolio['description'] ?: 'No description provided.' }}</p>
                                    </div>
                                    <span class="profile-status profile-status-{{ $portfolio['status'] }}">{{ ucfirst($portfolio['status']) }}</span>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            <section class="profile-card">
                <header class="profile-section-heading profile-card-head"><h2>Recent activity</h2></header>
                <div class="profile-card-body">
                    @if ($recentActivity->isEmpty())
                        <p class="profile-empty">No recent activity recorded.</p>
                    @else
                        <ol class="profile-timeline">
                            @foreach ($recentActivity as $activity)
                                <li class="{{ $activity['is_verification'] ? 'is-verification' : '' }}">
                                    <span class="profile-timeline-dot" aria-hidden="true"></span>
                                    <div>
                                        <p><strong>{{ $activity['actor'] }}</strong> {{ $activity['action'] }} <strong>{{ $activity['item'] }}</strong></p>
                                        @if ($activity['note'])
                                            <p class="profile-activity-note">{{ $activity['note'] }}</p>
                                        @endif
                                        <time>{{ $activity['created_at'] }}</time>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            </section>
        </div>
    </div>
</main>
@endsection
