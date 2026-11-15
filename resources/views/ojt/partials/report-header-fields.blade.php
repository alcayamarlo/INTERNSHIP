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
    $savedDefaults = (array) old('save_default', []);
@endphp
<fieldset class="report-header-fields border rounded p-3 mb-3"
          data-report-header
          data-start-monday="{{ $placement->start_date?->copy()->startOfWeek()->toDateString() }}"
          data-period-names='@json($periodNames)'>
    <legend class="float-none w-auto px-1 h6 mb-0">Report details</legend>
    <p class="small text-muted mb-2">Filled in from your profile and OJT record. Use <i class="bi bi-pencil" aria-hidden="true"></i><span class="visually-hidden">the pencil</span> to change a detail on this report only.</p>

    <dl class="row small mb-2">
        @foreach($locked as $field => $label)
            <dt class="col-5 col-md-4 fw-normal text-muted">{{ $label }}</dt>
            <dd class="col-7 col-md-8 mb-1">
                @if($values[$field] !== '')
                    {{ $values[$field] }}
                @else
                    <span class="text-danger">Missing</span>
                @endif
                <i class="bi bi-lock text-muted ms-1" aria-hidden="true" title="Change this on your profile page"></i><span class="visually-hidden">(locked; change it on your profile page)</span>
            </dd>
        @endforeach
    </dl>
    <p class="small mb-3"><a href="{{ route('student.profile.edit') }}">Change locked details on your profile</a></p>

    @foreach($editable as $field => [$label, $default])
        @php
            $oldValue = old('header.'.$field);
            $editing = $oldValue !== null;
            $value = $editing ? $oldValue : $default;
            $inputId = 'hf-'.$field;
        @endphp
        <div class="hf-row mb-2" data-hf="{{ $field }}" data-default="{{ $default }}" @if($editing) data-editing @endif>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted small hf-label" id="{{ $inputId }}-label">{{ $label }}</span>
                <span class="fw-semibold hf-display" data-hf-display @if($editing) hidden @endif>{{ $default !== '' ? $default : '—' }}</span>
                <button type="button" class="btn btn-link btn-sm p-0" data-hf-edit @if($editing) hidden @endif aria-label="Edit {{ $label }}"><i class="bi bi-pencil" aria-hidden="true"></i></button>
                <button type="button" class="btn btn-link btn-sm p-0 small" data-hf-reset @unless($editing) hidden @endunless>Reset to profile value</button>
            </div>
            <div class="mt-1" data-hf-editor @unless($editing) hidden @endunless>
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
</fieldset>
