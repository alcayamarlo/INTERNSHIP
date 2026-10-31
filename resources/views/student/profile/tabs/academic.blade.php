<section class="profile-panel" id="panel-academic" role="tabpanel" aria-labelledby="tab-academic" tabindex="0" @if($activeTab !== 'academic') hidden @endif>
    <form method="POST" action="{{ route('student.profile.update') }}" data-profile-section="academic" novalidate>
        @csrf
        @method('PUT')
        <input type="hidden" name="profile_section" value="academic">
        <h2 class="panel-heading">Academic information</h2>
        @if($profileVerified)
            <div class="alert alert-info">
                Your coordinator verified this profile. Student ID, program, and year level are locked. Use “Request change” if any of them need updating.
            </div>
        @endif
        <section class="card mb-4" aria-labelledby="internship-hours-heading">
            <div class="card-body">
                <h3 class="h6" id="internship-hours-heading">Internship hours</h3>
                @if($requiredInternshipHours)
                    <div class="d-flex justify-content-between small mb-2">
                        <span>Rendered hours</span>
                        <strong>{{ number_format($renderedInternshipHours) }} / {{ number_format($requiredInternshipHours) }} hours required</strong>
                    </div>
                    <div class="progress" role="progressbar" aria-label="Internship hours progress" aria-valuemin="0" aria-valuemax="{{ $requiredInternshipHours }}" aria-valuenow="{{ min($renderedInternshipHours, $requiredInternshipHours) }}">
                        <div class="progress-bar bg-info" style="width: {{ $internshipHoursProgress }}%"></div>
                    </div>
                @else
                    <p class="text-muted mb-0">Set your program to see the required internship hours.</p>
                @endif
                <p class="small text-muted mt-2 mb-0">{{ $hoursFromOjt ? 'Read-only. Pulled from your approved OJT attendance records.' : 'Read-only. Rendered hours are maintained by your coordinator.' }}</p>
            </div>
        </section>
        @if($profileVerified)
            <section class="card mb-4" aria-labelledby="change-requests-heading">
                <div class="card-body">
                    <h3 class="h6" id="change-requests-heading">Profile change requests</h3>
                    @if($studentChangeRequests->isEmpty())
                        <p class="text-muted mb-0">You have not submitted any change requests.</p>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($studentChangeRequests as $changeRequest)
                                <div class="list-group-item px-0">
                                    <div class="d-flex flex-wrap justify-content-between gap-2">
                                        <strong>{{ match($changeRequest->field) {'student_id_number' => 'Student ID', 'program_id' => 'Program', 'year_level' => 'Year level', default => 'Profile field'} }}</strong>
                                        <span class="badge bg-{{ $changeRequest->status === 'approved' ? 'success' : ($changeRequest->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($changeRequest->status) }}</span>
                                    </div>
                                    @if($changeRequest->field === 'program_id')
                                        <p class="small mb-1">{{ $programs->firstWhere('id', (int) $changeRequest->requested_value)?->display_name ?? 'Program no longer available' }}</p>
                                    @else
                                        <p class="small mb-1">{{ $changeRequest->requested_value }}</p>
                                    @endif
                                    @if($changeRequest->coordinator_note)
                                        <p class="small text-muted mb-0">Coordinator note: {{ $changeRequest->coordinator_note }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        @endif
        <div class="profile-field-grid">
            <div>
                <label class="form-label" for="student_number">Student ID number</label>
                <input id="student_number" type="text" class="form-control @error('student_number') is-invalid @enderror" name="student_number" value="{{ old('student_number', $student->student_id_number) }}" placeholder="SCC-21-00013240" autocomplete="off" data-student-id-pattern="{{ $studentIdPattern }}" @readonly($profileVerified || $student->student_id_locked)>
                @if($student->student_id_locked)
                    <small class="form-text text-muted">Your student ID was set when your account was created and can't be changed here. Contact your coordinator or administrator if it needs correction.</small>
                @endif
                <small id="studentIdCorrectionNotice" class="form-text text-warning" @if(!$studentIdNeedsCorrection) hidden @endif>This ID doesn't match the SCC-YY-NNNNNNNN format. Enter your correct student ID.</small>
                @if($profileVerified && ! $student->student_id_locked)<button type="button" class="btn btn-sm btn-outline-primary mt-2" data-request-change-field="student_id_number">Request change</button>@endif
                @error('student_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            @include('student.profile.partials.field', ['name' => 'department', 'label' => 'College', 'required' => true, 'value' => $student->department, 'extra' => 'placeholder="College of Computer Studies"'])
            <div>
                <label class="form-label" for="program_id">Course<span class="required-mark" aria-hidden="true">*</span></label>
                <select id="program_id" class="form-select @error('program_id') is-invalid @enderror" @unless($profileVerified) name="program_id" @endunless @disabled($profileVerified) required>
                    <option value="">Select...</option>
                    @foreach($programs as $program)
                        <option value="{{ $program->id }}" data-coordinator="{{ $program->coordinator?->user?->name ?? '' }}" {{ (string) old('program_id', $profileVerified ? $student->program_id : $selectedProgramId) === (string) $program->id ? 'selected' : '' }}>{{ $program->display_name }}</option>
                    @endforeach
                </select>
                @if($profileVerified)
                    <input type="hidden" name="program_id" value="{{ $student->program_id }}">
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" data-request-change-field="program_id">Request change</button>
                @endif
                @error('program_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @error('program')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div id="majorField">
                <label class="form-label" for="major_id">Major</label>
                <select id="major_id" class="form-select @error('major_id') is-invalid @enderror" name="major_id">
                    <option value="">Select...</option>
                    @foreach($programs as $program)
                        @foreach($program->majors as $major)
                            <option value="{{ $major->id }}" data-program-id="{{ $program->id }}" {{ (string) old('major_id', $student->major_id) === (string) $major->id ? 'selected' : '' }}>{{ $major->name }}</option>
                        @endforeach
                    @endforeach
                </select>
                @error('major_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="form-label" for="year_level">Year level<span class="required-mark" aria-hidden="true">*</span></label>
                <select id="year_level" class="form-select @error('year_level') is-invalid @enderror" @unless($profileVerified) name="year_level" @endunless @disabled($profileVerified) required>
                    <option value="">Select...</option>
                    @foreach(['1st Year', '2nd Year', '3rd Year', '4th Year'] as $yearLevel)
                        <option value="{{ $yearLevel }}" {{ old('year_level', $student->year_level) === $yearLevel ? 'selected' : '' }}>{{ $yearLevel }}</option>
                    @endforeach
                </select>
                @if($profileVerified)
                    <input type="hidden" name="year_level" value="{{ $student->year_level }}">
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" data-request-change-field="year_level">Request change</button>
                @endif
                @error('year_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            @include('student.profile.partials.field', ['name' => 'expected_graduation', 'label' => 'Expected graduation', 'type' => 'month', 'required' => true, 'value' => $student->expected_graduation?->format('Y-m')])
            @include('student.profile.partials.field', ['name' => 'section', 'label' => 'Section', 'value' => $student->section])
            <div>
                <label class="form-label" for="school_year">School year</label>
                <select id="school_year" class="form-select @error('school_year') is-invalid @enderror" name="school_year">
                    <option value="">Select...</option>
                    @foreach($academicYears as $schoolYear)
                        <option value="{{ $schoolYear }}" {{ old('school_year', $student->school_year ?: $academicDefaults['school_year']) === $schoolYear ? 'selected' : '' }}>{{ $schoolYear }}</option>
                    @endforeach
                </select>
                @error('school_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="form-label" for="semester">Semester</label>
                <select id="semester" class="form-select @error('semester') is-invalid @enderror" name="semester">
                    <option value="">Select...</option>
                    @foreach($semesterOptions as $semesterKey => $semesterLabel)
                        <option value="{{ $semesterKey }}" {{ old('semester', $student->semester ?: $academicDefaults['semester']) === $semesterKey ? 'selected' : '' }}>{{ $semesterLabel }}</option>
                    @endforeach
                </select>
                @error('semester')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="form-label" for="enrollment_status">Enrollment status</label>
                <select id="enrollment_status" class="form-select @error('enrollment_status') is-invalid @enderror" name="enrollment_status">
                    <option value="">Select...</option>
                    @foreach(['Regular', 'Irregular'] as $enrollmentStatus)
                        <option value="{{ $enrollmentStatus }}" {{ old('enrollment_status', $student->enrollment_status) === $enrollmentStatus ? 'selected' : '' }}>{{ $enrollmentStatus }}</option>
                    @endforeach
                </select>
                @error('enrollment_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            @include('student.profile.partials.field', ['name' => 'gwa', 'label' => 'GWA (optional)', 'type' => 'number', 'value' => $student->gwa, 'extra' => 'step="0.01"'])
            <div>
                <label class="form-label" for="assigned_coordinator">Assigned coordinator</label>
                <input id="assigned_coordinator" type="text" class="form-control" value="{{ $programs->firstWhere('id', (int) old('program_id', $selectedProgramId))?->coordinator?->user?->name ?? 'Not assigned' }}" readonly>
            </div>
        </div>

        <hr class="section-divider">
        <h2 class="panel-heading">Educational background</h2>
        <div class="profile-field-grid">
            @include('student.profile.partials.field', ['name' => 'elementary_school', 'label' => 'Elementary school', 'required' => true, 'value' => $student->elementary_school])
            @include('student.profile.partials.field', ['name' => 'elementary_year_graduated', 'label' => 'Year graduated', 'type' => 'number', 'required' => true, 'value' => $student->elementary_year_graduated, 'id' => 'elementary_year_graduated', 'extra' => 'min="1950" max="'.now()->year.'" inputmode="numeric"'])
            @include('student.profile.partials.field', ['name' => 'high_school', 'label' => 'High school / Senior high school', 'required' => true, 'value' => $student->high_school])
            @include('student.profile.partials.field', ['name' => 'high_school_year_graduated', 'label' => 'Year graduated', 'type' => 'number', 'required' => true, 'value' => $student->high_school_year_graduated, 'extra' => 'min="1950" max="'.now()->year.'" inputmode="numeric"'])
        </div>
        <p class="portfolio-description mt-2"><i class="bi bi-trophy" aria-hidden="true"></i> Add honors and awards one at a time under <a href="#awardsHeading">Honors / awards</a> below.</p>

        <hr class="section-divider">
        <h2 class="panel-heading">Career information</h2>
        <div class="profile-field-grid">
            <div class="field-wide">
                <label class="form-label" for="career_objectives">Career objectives</label>
                <textarea id="career_objectives" class="form-control @error('career_objectives') is-invalid @enderror" name="career_objectives" rows="3" placeholder="Describe your career goals and aspirations">{{ old('career_objectives', $student->career_objectives) }}</textarea>
                @error('career_objectives')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            @include('student.profile.partials.field', ['name' => 'preferred_internship_field', 'label' => 'Preferred internship field', 'value' => $student->preferred_internship_field])
            <div>
                <label class="form-label" for="preferred_work_setup">Work setup</label>
                <select id="preferred_work_setup" class="form-select @error('preferred_work_setup') is-invalid @enderror" name="preferred_work_setup">
                    <option value="">Select...</option>
                    @foreach(['Remote', 'Hybrid', 'Onsite'] as $workSetup)
                        <option value="{{ $workSetup }}" {{ old('preferred_work_setup', $student->preferred_work_setup) === $workSetup ? 'selected' : '' }}>{{ $workSetup }}</option>
                    @endforeach
                </select>
                @error('preferred_work_setup')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            @include('student.profile.partials.field', ['name' => 'preferred_location', 'label' => 'Preferred location', 'value' => $student->preferred_location])
        </div>
        @include('student.profile.partials.save-bar')
    </form>

    <hr class="section-divider">
    <section aria-labelledby="awardsHeading">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h2 class="panel-heading mb-0" id="awardsHeading">Honors / awards <span class="panel-tag">Optional</span></h2>
            <button type="button" class="btn-profile" data-collection-open="awards"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add award</button>
        </div>
        <p class="portfolio-description">Add each honor or award on its own, such as Dean's Lister or With Honors. They show on your resume grouped by school level.</p>

        <div data-collection-list="awards">
            @include('student.profile.partials.award-list')
        </div>

        <form class="collection-form" method="POST" action="{{ route('student.awards.store') }}" data-profile-collection="awards" data-store-url="{{ route('student.awards.store') }}" data-hide-when-idle @if(! $errors->awards->any()) hidden @endif novalidate>
            @csrf
            <input type="hidden" name="_method" value="POST" data-method-field>
            <h3 class="panel-subheading" data-form-title data-add-title="Add award" data-edit-title="Edit award">Add award</h3>
            <div class="profile-field-grid">
                <div>
                    <label class="form-label" for="award_level">Level<span class="required-mark" aria-hidden="true">*</span></label>
                    <select id="award_level" class="form-select @error('level', 'awards') is-invalid @enderror" name="level" required>
                        <option value="">Select...</option>
                        @foreach($awardLevels as $level)
                            <option value="{{ $level->value }}" @selected(old('level') === $level->value)>{{ $level->label() }}</option>
                        @endforeach
                        @if($hasOtherAward)
                            <option value="{{ \App\Enums\AwardLevel::Other->value }}">Other (from your old profile)</option>
                        @endif
                    </select>
                    @error('level', 'awards')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="award_title">Award<span class="required-mark" aria-hidden="true">*</span></label>
                    <input id="award_title" type="text" class="form-control @error('title', 'awards') is-invalid @enderror" name="title" value="{{ old('title') }}" maxlength="150" placeholder="Dean's Lister" required>
                    @error('title', 'awards')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="award_event">Event or school</label>
                    <input id="award_event" type="text" class="form-control @error('event', 'awards') is-invalid @enderror" name="event" value="{{ old('event') }}" maxlength="150" placeholder="St. Cecilia's College-Cebu">
                    @error('event', 'awards')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="award_year">Year</label>
                    <input id="award_year" type="text" class="form-control @error('year', 'awards') is-invalid @enderror" name="year" value="{{ old('year') }}" maxlength="20" placeholder="2024 or 2023-present" aria-describedby="awardYearHint">
                    <small class="form-text text-muted" id="awardYearHint">A year or a range, like 2023-2024 or 2023-present.</small>
                    @error('year', 'awards')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="field-wide">
                    <label class="form-label" for="award_detail">Short detail</label>
                    <input id="award_detail" type="text" class="form-control @error('detail', 'awards') is-invalid @enderror" name="detail" value="{{ old('detail') }}" maxlength="150" placeholder="GWA: 1.45">
                    @error('detail', 'awards')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="collection-actions">
                <button type="button" class="btn-profile" data-collection-cancel>Cancel</button>
                <button type="submit" class="btn-primary-profile">Save award</button>
            </div>
        </form>
    </section>
</section>
