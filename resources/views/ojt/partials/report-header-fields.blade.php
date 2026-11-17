{{--
    Header of a new weekly Activity Narrative Report, filled in from the profile and the OJT.
    Locked: Name of Trainee, Student ID, Course, Year Level (changed only on the profile page).
    Editable on this report: Company, Supervisor's Name, Designation, Period of Evaluation, Inclusive Dates.
    An editable field's input stays disabled (so it is not sent) until the student clicks its pencil;
    "Reset to profile value" disables it again. Expects $placement.
--}}
@php
    use App\Services\OjtDocumentData;
    $docs = app(OjtDocumentData::class);
    $values = $docs->values($placement);
    $weekDay = old('week_start') ? \Carbon\CarbonImmutable::parse(old('week_start')) : now();
    $week = $docs->weekValues($placement, $weekDay);
    $supervisors = $docs->supervisorOptions($placement);
    $designations = $docs->designations($placement->department);
    $periodNames = collect(\App\Models\AcademicSetting::current()->evaluationPeriodLabels())
        ->map(fn ($label, $month) => mb_strtoupper($label ?: \Carbon\Carbon::create(2000, $month, 1)->format('F')));
    $locked = [
        'full_name' => 'Name of Trainee',
        'student_id' => 'Student ID',
        'course' => 'Course',
        'year_level' => 'Year Level',
    ];
    $editable = [
        'company' => ['Company / Agency', $values['company']],
        'supervisor_name' => ["Supervisor's Name", $values['supervisor_name']],
        'designation' => ['Designation', $values['designation']],
        'period_of_evaluation' => ['Period of Evaluation', $week['period_of_evaluation']],
        'inclusive_dates' => ['Inclusive Dates', $week['inclusive_dates']],
    ];
    // A supervisor assigned by the company decides the name printed on the forms: it is shown, not editable.
    if ($placement->activeSupervisor()) {
        $locked['supervisor_name'] = "Supervisor's Name";
        unset($editable['supervisor_name']);
    }
    $savedDefaults = (array) old('save_default', []);
    // Weekend days with a time record: the page uses them, with the OJT dates, to show Inclusive Dates.
    $loggedWeekend = $placement->attendanceLogs()->where('status', '!=', \App\Models\AttendanceLog::STATUS_REJECTED)->pluck('work_date')
        ->map(fn ($date) => \Carbon\CarbonImmutable::parse($date))->filter->isWeekend()->map->toDateString()->values();
@endphp
<section class="wr-section report-header-fields"
          data-report-header
          data-start-monday="{{ $placement->start_date?->copy()->startOfWeek()->toDateString() }}"
          data-start-date="{{ $placement->start_date?->toDateString() }}"
          data-end-date="{{ $placement->end_date?->toDateString() }}"
          data-logged-weekend='@json($loggedWeekend)'
          data-period-names='@json($periodNames)'
          aria-labelledby="wr-details-title">
    <div class="wr-section-head">
        <span class="wr-step" aria-hidden="true">1</span>
        <div>
            <h3 class="wr-section-title" id="wr-details-title">Report details</h3>
            <p class="wr-section-hint">Filled in from your profile and OJT record.</p>
        </div>
        <a class="wr-link" href="{{ route('student.profile.edit') }}"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Edit profile</a>
    </div>

    <p class="wr-group-label">From your profile <span class="text-lowercase fw-normal">(locked)</span></p>
    <div class="wr-locked">
        @foreach($locked as $field => $label)
            <div class="wr-tile">
                <span class="wr-tile-label"><i class="bi bi-lock" aria-hidden="true" title="Change this on your profile page"></i>{{ $label }}<span class="visually-hidden">(locked; change it on your profile page)</span></span>
                <span class="wr-tile-value">
                    @if($values[$field] !== '')
                        {{ $values[$field] }}
                    @else
                        <span class="sb-req">Missing</span>
                    @endif
                </span>
            </div>
        @endforeach
    </div>

    <p class="wr-group-label">For this report <span class="text-lowercase fw-normal">(use <i class="bi bi-pencil" aria-hidden="true"></i><span class="visually-hidden">the pencil</span> to change)</span></p>
    <div class="wr-editable">
    @foreach($editable as $field => [$label, $default])
        @php
            $oldValue = old('header.'.$field);
            $editing = $oldValue !== null;
            $value = $editing ? $oldValue : $default;
            $inputId = 'hf-'.$field;
        @endphp
        <div class="hf-row wr-field @if(in_array($field, ['company', 'inclusive_dates'], true)) wr-field--wide @endif" data-hf="{{ $field }}" data-default="{{ $default }}" @if($editing) data-editing @endif>
            <div class="wr-field-top">
                <span class="wr-field-label hf-label" id="{{ $inputId }}-label">{{ $label }}</span>
                <button type="button" class="wr-reset" data-hf-reset @unless($editing) hidden @endunless>Reset to profile value</button>
                <button type="button" class="wr-icon-btn" data-hf-edit @if($editing) hidden @endif aria-label="Edit {{ $label }}"><i class="bi bi-pencil" aria-hidden="true"></i></button>
            </div>
            <span class="wr-field-value hf-display" data-hf-display @if($editing) hidden @endif>{{ $default !== '' ? $default : '—' }}</span>
            <div data-hf-editor @unless($editing) hidden @endunless>
                @if($field === 'designation')
                    <select id="{{ $inputId }}" name="header[designation]" class="form-select form-select-sm @error('header.designation') is-invalid @enderror" aria-labelledby="{{ $inputId }}-label" @disabled(! $editing)>
                        <option value="">Select...</option>
                        @foreach($designations as $designation)
                            <option value="{{ $designation }}" @selected($value === $designation)>{{ $designation }}</option>
                        @endforeach
                    </select>
                @elseif($field === 'supervisor_name')
                    @php $isOther = $value !== '' && ! in_array($value, $supervisors, true); @endphp
                    <select class="form-select form-select-sm mb-1" data-hf-supervisor-choice aria-labelledby="{{ $inputId }}-label" @disabled(! $editing)>
                        @foreach($supervisors as $name)
                            <option value="{{ $name }}" @selected(! $isOther && $value === $name)>{{ $name }}</option>
                        @endforeach
                        <option value="__other" @selected($isOther || $supervisors === [])>Other (type name)</option>
                    </select>
                    <input id="{{ $inputId }}" name="header[supervisor_name]" maxlength="80" value="{{ $value }}" placeholder="Type the supervisor's name"
                           class="form-control form-control-sm @error('header.supervisor_name') is-invalid @enderror" data-hf-supervisor-text
                           @if(! $isOther && $supervisors !== []) hidden @endif @disabled(! $editing) aria-labelledby="{{ $inputId }}-label">
                @else
                    <input id="{{ $inputId }}" name="header[{{ $field }}]" value="{{ $value }}" maxlength="{{ $field === 'company' ? 150 : 60 }}"
                           class="form-control form-control-sm @error('header.'.$field) is-invalid @enderror" aria-labelledby="{{ $inputId }}-label" @disabled(! $editing)>
                @endif
                @error('header.'.$field)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @if(array_key_exists($field, OjtDocumentData::SAVE_AS_DEFAULT))
                    <div class="form-check small mt-1">
                        <input class="form-check-input" type="checkbox" name="save_default[]" value="{{ $field }}" id="{{ $inputId }}-default" @checked(in_array($field, $savedDefaults, true)) @disabled(! $editing)>
                        <label class="form-check-label" for="{{ $inputId }}-default">Also update my profile / placement (use it on my next reports)</label>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
    </div>
</section>
