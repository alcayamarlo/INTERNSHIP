@extends('layouts.app')

@section('title', 'Student Profile')

@section('content')
<link rel="stylesheet" href="{{ asset('css/coordinator-student-record.css') }}?v={{ @filemtime(public_path('css/coordinator-student-record.css')) }}">
@php $studentAvatar = \App\Support\Avatar::for($student->user); @endphp
<div class="sb-dash student-record">
<x-profile-header :name="$student->user->name" :avatar="$studentAvatar" eyebrow="Student profile">
    @if($student->profile_verified_at)
        <x-slot:badge><x-status-badge status="verified" label="Profile verified" /></x-slot:badge>
    @endif
    <span class="sb-pheader__strong">{{ ($student->program ?: 'Program not set').' · '.($student->year_level ?: 'Year level not set') }}</span>
    <span><i class="bi bi-building" aria-hidden="true"></i>{{ $student->institution?->name ?? 'Institution not set' }}</span>
    <span>
        <i class="bi bi-person-vcard" aria-hidden="true"></i>School ID {{ $student->student_id_number ?: 'Not provided' }}
        @if($studentIdNeedsCorrection)<span class="sb-tag sb-tag--brand">ID needs correction</span>@endif
        <span class="sb-pheader__sep" aria-hidden="true">·</span><i class="bi bi-envelope" aria-hidden="true"></i>{{ $student->user->email }}
        <span class="sb-pheader__sep" aria-hidden="true">·</span><i class="bi bi-telephone" aria-hidden="true"></i>{{ $student->user->phone ?: 'Phone not provided' }}
    </span>
    <x-slot:actions>
        <a class="btn btn-outline-secondary" href="{{ route('coordinator.students.index') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Students</a>
    </x-slot:actions>
</x-profile-header>

<div class="sb-utabs-bar">
    <div class="sb-utabs" role="tablist" aria-label="Student sections">
        <button type="button" class="sb-utab" role="tab" id="tab-overview" aria-controls="overview" aria-selected="true"><span>Overview</span></button>
        <button type="button" class="sb-utab" role="tab" id="tab-academic" aria-controls="academic" aria-selected="false" tabindex="-1"><span>Academic and eligibility</span></button>
        <button type="button" class="sb-utab" role="tab" id="tab-certificates" aria-controls="certificates" aria-selected="false" tabindex="-1"><span>Certificates and skills</span></button>
        <button type="button" class="sb-utab" role="tab" id="tab-portfolio" aria-controls="portfolio" aria-selected="false" tabindex="-1"><span>Portfolio</span></button>
    </div>
</div>

{{-- ===== Overview ===== --}}
<section class="sb-upanel sb-stack" role="tabpanel" id="overview" aria-labelledby="tab-overview" tabindex="0">
    <section class="sb-kpis student-record__kpis" aria-label="Submission summary">
        <div class="card sb-kpi"><span class="sb-kpi-label">Profile complete</span><span class="sb-kpi-value">{{ $profileCompletion }}%</span></div>
        <div class="card sb-kpi"><span class="sb-kpi-label">Certificates</span><span class="sb-kpi-value">{{ $competencies->count() }}</span></div>
        <div class="card sb-kpi"><span class="sb-kpi-label">Older certificate files</span><span class="sb-kpi-value">{{ $certificates->count() }}</span></div>
        <div class="card sb-kpi"><span class="sb-kpi-label">Portfolio items</span><span class="sb-kpi-value">{{ $portfolios->count() }}</span></div>
        <div class="card sb-kpi">
            <span class="sb-kpi-label">Verification progress</span>
            @if($verificationCounts['total'])
                <span class="sb-kpi-value">{{ $verificationCounts['verified'].'/'.$verificationCounts['total'].' verified' }}</span>
                @if($verificationCounts['pending'] > 0)<span class="sb-note">· {{ $verificationCounts['pending'] }} pending</span>@endif
            @else
                <span class="sb-kpi-value">—</span>
            @endif
        </div>
    </section>

    <div class="sb-grid sb-grid--2">
        <div class="sb-stack">
            <section class="card">
                <div class="card-header"><h2>Student details</h2></div>
                <div class="card-body">
                    <dl class="sb-fields">
                        <div><dt>Program</dt><dd>{{ $student->program ?: 'Not set' }}</dd></div>
                        <div><dt>Year level</dt><dd>{{ $student->year_level ?: 'Not set' }}</dd></div>
                        <div>
                            <dt>School ID</dt>
                            <dd>
                                {{ $student->student_id_number ?: 'Not provided' }}
                                @if($studentIdNeedsCorrection) <span class="sb-tag sb-tag--brand">ID needs correction</span>@endif
                            </dd>
                        </div>
                        <div><dt>Phone</dt><dd>{{ $student->user->phone ?: 'Not provided' }}</dd></div>
                        <div><dt>Last active</dt><dd>{{ $student->user->last_login_at?->diffForHumans() ?? 'No activity recorded' }}</dd></div>
                    </dl>
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h2>About me</h2></div>
                <div class="card-body"><p class="mb-0">{{ $student->about_me ?: 'Not added yet.' }}</p></div>
            </section>
        </div>

        <section class="card">
            <div class="card-header"><h2>Recent activity</h2></div>
            <div class="card-body">
                @if($recentActivity->isEmpty())
                    <p class="sb-note">No recent activity recorded.</p>
                @else
                    <ol class="sb-timeline">
                        @foreach($recentActivity as $activity)
                            <li class="{{ $activity['is_verification'] ? 'is-done' : '' }}">
                                <p><strong>{{ $activity['actor'] }}</strong> {{ $activity['action'] }} <strong>{{ $activity['item'] }}</strong></p>
                                @if($activity['note'])
                                    <p class="sb-timeline-note">{{ $activity['note'] }}</p>
                                @endif
                                <time>{{ $activity['created_at'] }}</time>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </section>
    </div>
</section>

{{-- ===== Academic and eligibility ===== --}}
<section class="sb-upanel" role="tabpanel" id="academic" aria-labelledby="tab-academic" tabindex="0" hidden>
    <div class="sb-grid sb-grid--2">
        <div class="sb-stack">
            <section class="card">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h2>Academic progress and profile verification</h2>
                    @if($student->profile_verified_at)
                        <x-status-badge status="verified" label="Profile verified" />
                    @else
                        <form method="POST" action="{{ route('coordinator.students.verify-profile', $student) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">Verify profile</button>
                        </form>
                    @endif
                </div>
                <div class="card-body sb-stack sb-stack--tight">
                    <p>Required internship hours: {{ $requiredInternshipHours ?? 'Not set for this program' }}</p>
                    <div>
                        <div class="progress progress--thin" role="img" aria-label="{{ $renderedInternshipHours }} of {{ $requiredInternshipHours ?? 0 }} internship hours rendered">
                            <div class="progress-bar" style="width: {{ $internshipHoursProgress }}%"></div>
                        </div>
                        <p class="sb-note mt-2">{{ $renderedInternshipHours }} rendered{{ $requiredInternshipHours ? ' / '.$requiredInternshipHours.' required hours ('.$internshipHoursProgress.'%)' : ' hours' }}</p>
                    </div>
                    <form method="POST" action="{{ route('coordinator.students.academic-readiness.update', $student) }}" class="sb-stack sb-stack--tight">
                        @csrf
                        @method('PUT')
                        <label class="sb-field">
                            <span>Rendered internship hours</span>
                            <input type="number" class="form-control" name="rendered_internship_hours" min="0" max="10000" step="1" value="{{ old('rendered_internship_hours', $renderedInternshipHours) }}" required>
                        </label>
                        <label class="sb-field">
                            <span>GWA (from the registrar)</span>
                            <input type="number" class="form-control" name="gwa" min="1" max="5" step="0.01" value="{{ old('gwa', $student->gwa) }}" placeholder="e.g. 1.75">
                        </label>
                        <div class="form-check">
                            <input type="hidden" name="major_subjects_passed" value="0">
                            <input class="form-check-input" type="checkbox" id="majorSubjectsPassed" name="major_subjects_passed" value="1" @checked(old('major_subjects_passed', $student->major_subjects_passed))>
                            <label class="form-check-label" for="majorSubjectsPassed">Student has passed all major subjects</label>
                        </div>
                        <div><button type="submit" class="btn btn-primary">Save academic progress</button></div>
                    </form>
                </div>
            </section>

            @if($changeRequests->isNotEmpty())
                <section class="card">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h2>Profile change requests</h2>
                        <span class="sb-tag">{{ $changeRequests->where('status', 'pending')->count() }} pending</span>
                    </div>
                    <div class="card-body">
                        <div class="sb-review-list">
                            @foreach($changeRequests as $changeRequest)
                                <article class="sb-review-row">
                                    <div class="sb-review-row-text">
                                        <h3 class="sb-row-title">{{ match($changeRequest->field) { 'student_id_number' => 'Student ID', 'program_id' => 'Program', 'year_level' => 'Year level', default => ucfirst(str_replace('_', ' ', $changeRequest->field)) } }}</h3>
                                        <p class="sb-row-sub mb-0">Current: {{ $changeRequest->current_value_label }} <span aria-hidden="true">→</span> Requested: {{ $changeRequest->requested_value_label }}</p>
                                        @if($changeRequest->student_note)
                                            <p class="small mt-2 mb-0">{{ $changeRequest->student_note }}</p>
                                        @endif
                                        @if($changeRequest->coordinator_note)
                                            <p class="sb-row-sub mb-0">Coordinator note: {{ $changeRequest->coordinator_note }}</p>
                                        @endif
                                    </div>
                                    <x-status-badge :status="$changeRequest->status" :label="ucfirst($changeRequest->status)" />
                                    @if($changeRequest->status === 'pending')
                                        <div class="sb-inline-panel">
                                            <form method="POST" action="{{ route('coordinator.students.change-requests.review', [$student, $changeRequest]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="decision" value="approved">
                                                <button type="submit" class="btn btn-primary btn-sm">Approve</button>
                                            </form>
                                            <form method="POST" action="{{ route('coordinator.students.change-requests.review', [$student, $changeRequest]) }}" class="d-flex flex-wrap gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="decision" value="rejected">
                                                <label class="flex-grow-1">
                                                    <span class="visually-hidden">Required rejection note</span>
                                                    <input type="text" class="form-control form-control-sm" name="coordinator_note" maxlength="2000" placeholder="Reason for rejection" required>
                                                </label>
                                                <button type="submit" class="btn btn-outline-secondary btn-sm">Reject</button>
                                            </form>
                                        </div>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif
        </div>

        <section class="card" aria-labelledby="eligibilityHeading" data-eligibility>
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h2 id="eligibilityHeading">Internship eligibility</h2>
                <x-status-badge :status="$eligibility['eligible'] ? 'approved' : 'pending'" :label="$eligibility['eligible'] ? 'Eligible to apply' : 'Not eligible yet'" />
            </div>
            <div class="card-body sb-stack sb-stack--tight">
                <p class="sb-note">{{ $eligibility['source'] === 'program' ? 'Checked against the program criteria for this term.' : 'Checked against the school-wide rules (no program criteria this term).' }}</p>
                @if($eligibility['rules'])
                    <ul class="sb-checks">
                        @foreach($eligibility['rules'] as $rule)
                            <li class="{{ $rule['passed'] ? 'is-pass' : 'is-fail' }}" data-rule="{{ $rule['key'] }}">
                                <i class="bi {{ $rule['passed'] ? 'bi-check-circle-fill' : 'bi-x-circle' }}" aria-hidden="true"></i>
                                <span><strong>{{ $rule['label'] }}</strong>: {{ $rule['detail'] }}</span>
                                <span class="visually-hidden">{{ $rule['passed'] ? '(passed)' : '(failed)' }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p>No eligibility rules apply this term.</p>
                @endif

                {{-- One form per decision: the decision travels in a hidden field, not on the (auto-disabled) button. --}}
                <div class="sb-inline-panel">
                    <form method="POST" action="{{ route('coordinator.students.eligibility.store', $student) }}" class="d-grid gap-2">
                        @csrf
                        <input type="hidden" name="decision" value="rejected">
                        <div>
                            <label class="form-label" for="eligibilityReason">Reason <span class="text-muted small">(required to mark not eligible; the student sees it)</span></label>
                            <input id="eligibilityReason" type="text" name="reason" maxlength="1000" class="form-control @error('reason') is-invalid @enderror" value="{{ old('reason') }}" required>
                            @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-outline-primary">Mark not eligible</button>
                    </form>
                    <form method="POST" action="{{ route('coordinator.students.eligibility.store', $student) }}">
                        @csrf
                        <input type="hidden" name="decision" value="approved">
                        <button type="submit" class="btn btn-primary w-100">Approve</button>
                    </form>
                </div>

                @if($eligibilityHistory->isNotEmpty())
                    <div>
                        <h3 class="sb-block-title">Decision history</h3>
                        <ul class="sb-rows">
                            @foreach($eligibilityHistory as $decision)
                                <li class="small">
                                    <x-status-badge :status="$decision->isRejected() ? 'rejected' : 'approved'" :label="$decision->isRejected() ? 'Not eligible' : 'Approved'" />
                                    <span>{{ $decision->created_at->format('M d, Y h:i A') }} · {{ $decision->decidedBy?->name ?? 'Coordinator' }}@if($decision->reason) · {{ $decision->reason }}@endif</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </section>
    </div>
</section>

{{-- ===== Certificates and skills ===== --}}
<section class="sb-upanel" role="tabpanel" id="certificates" aria-labelledby="tab-certificates" tabindex="0" hidden>
    <div class="sb-grid sb-grid--2">
        <div class="sb-stack">
            <section class="card">
                <div class="card-header d-flex align-items-center justify-content-between gap-2"><h2>Certificates</h2><span class="sb-tag">{{ $competencies->count() }}</span></div>
                @if($competencies->isEmpty())
                    <div class="card-body"><p class="sb-note">No certificates added yet.</p></div>
                @else
                    <ul class="sb-rows sb-rows--card">
                        @foreach($competencies as $competency)
                            <li>
                                <div class="sb-grow">
                                    <span class="sb-row-title">{{ $competency['name'] }}</span>
                                    <span class="sb-row-sub">{{ $competency['category'] }}@if(! empty($competency['issuer'])) · {{ $competency['issuer'] }}@endif</span>
                                    @if(! empty($competency['skills']))<span class="sb-row-sub">Skills learned: {{ implode(', ', $competency['skills']) }}</span>@endif
                                </div>
                                <span class="sb-tag sb-tag--brand" aria-label="{{ $competency['level_label'] }} proficiency">{{ $competency['level_label'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="card">
                <div class="card-header d-flex align-items-center justify-content-between gap-2"><h2>Older certificate files</h2><span class="sb-tag">{{ $certificates->count() }}</span></div>
                @if($certificates->isEmpty())
                    <div class="card-body"><p class="sb-note">No certificates uploaded yet.</p></div>
                @else
                    <ul class="sb-rows sb-rows--card">
                        @foreach($certificates as $certificate)
                            <li>
                                <div class="sb-grow">
                                    <span class="sb-row-title">{{ $certificate['title'] }}</span>
                                    <span class="sb-row-sub">{{ $certificate['issuer'] ?: 'Issuer not specified' }}</span>
                                    @if($certificate['has_file'])
                                        <a href="{{ route('coordinator.verification.evidence', ['certificates', $certificate['id']]) }}" target="_blank" rel="noopener" class="small">View file</a>
                                    @endif
                                </div>
                                <x-status-badge :status="$certificate['status']" :label="ucfirst($certificate['status'])" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <section class="card">
            <div class="card-header d-flex align-items-center justify-content-between gap-2"><h2>Skills</h2><span class="sb-tag">{{ $skills->count() }}</span></div>
            <div class="card-body">
                @if($skills->isEmpty())
                    <p class="sb-note">No skills added yet.</p>
                @else
                    <ul class="sb-tags" aria-label="Self-declared skills">
                        @foreach($skills as $skill)
                            <li>{{ $skill['name'] }}@if($skill['level_label']) · {{ $skill['level_label'] }}@endif @if($skill['has_certificate'])<i class="bi bi-file-earmark-check" aria-hidden="true" title="Has certificate"></i><span class="visually-hidden">(has certificate)</span>@endif</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    </div>
</section>

{{-- ===== Portfolio ===== --}}
<section class="sb-upanel" role="tabpanel" id="portfolio" aria-labelledby="tab-portfolio" tabindex="0" hidden>
    <section class="card">
        <div class="card-header d-flex align-items-center justify-content-between gap-2"><h2>Portfolio</h2><span class="sb-tag">{{ $portfolios->count() }}</span></div>
        @if($portfolios->isEmpty())
            <div class="card-body"><p class="sb-note">No portfolio items yet.</p></div>
        @else
            <ul class="sb-rows sb-rows--card">
                @foreach($portfolios as $portfolio)
                    <li>
                        <div class="sb-grow">
                            <span class="sb-row-title">{{ $portfolio['title'] }}</span>
                            <span class="sb-row-sub">{{ $portfolio['description'] ?: 'No description provided.' }}</span>
                        </div>
                        <x-status-badge :status="$portfolio['status']" :label="ucfirst($portfolio['status'])" />
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</section>
</div>
<script src="{{ asset('js/sb-underline-tabs.js') }}?v={{ @filemtime(public_path('js/sb-underline-tabs.js')) }}"></script>
@endsection
