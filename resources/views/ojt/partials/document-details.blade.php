{{--
    Student: the details printed on every OJT form, what is still missing, and the OJT's Designation
    and supervisor name (used on reports not yet submitted). Expects $placement.
--}}
@php
    use App\Services\OjtDocumentData;
    $docs = app(OjtDocumentData::class);
    $values = $docs->values($placement);
    $missing = collect(OjtDocumentData::DOCUMENTS)->flatten()->unique()
        ->filter(fn ($field) => trim((string) ($values[$field] ?? '')) === '')
        ->mapWithKeys(fn ($field) => [$field => OjtDocumentData::LABELS[$field]]);
    $profileMissing = $missing->keys()->intersect(OjtDocumentData::PROFILE_FIELDS)->isNotEmpty();
    $supervisors = $docs->supervisorOptions($placement);
@endphp
<section class="card mb-4" id="ojtDetails" aria-labelledby="ojtDetailsHeading">
    <div class="card-header"><h2 class="h6 mb-0" id="ojtDetailsHeading"><i class="bi bi-file-earmark-person" aria-hidden="true"></i> Details on your forms</h2></div>
    <div class="card-body">
        @if($missing->isNotEmpty())
            <div class="alert alert-warning" role="alert">
                <strong>Complete your profile first:</strong> {{ $missing->values()->implode(', ') }}.
                Your weekly reports, evaluations and certificate can't be printed or submitted until these are filled in.
                @if($missing->has('designation'))<span class="d-block mt-1">Choose your Designation below and click Save.</span>@endif
                @if($profileMissing)<a href="{{ route('student.profile.edit') }}" class="alert-link d-inline-block ms-1">Go to my profile</a>@endif
            </div>
        @endif

        <dl class="row small mb-3">
            <dt class="col-sm-4 fw-normal text-muted">Name of Trainee</dt><dd class="col-sm-8">{{ $values['full_name'] ?: '—' }}</dd>
            <dt class="col-sm-4 fw-normal text-muted">Student ID</dt><dd class="col-sm-8">{{ $values['student_id'] ?: '—' }}</dd>
            <dt class="col-sm-4 fw-normal text-muted">Course · Year level</dt><dd class="col-sm-8">{{ $values['course'] ?: '—' }} · {{ $values['year_level'] ?: '—' }}</dd>
            <dt class="col-sm-4 fw-normal text-muted">Company / Agency</dt><dd class="col-sm-8">{{ $values['company'] ?: '—' }}</dd>
        </dl>

        @if($placement->isActive())
            <form method="POST" action="{{ route('student.ojt.details.update', $placement) }}" class="row g-2 align-items-end" novalidate>
                @csrf @method('PATCH')
                <div class="col-md-5">
                    <label class="form-label" for="ojtDesignation">Designation (department) <span class="text-danger" aria-hidden="true">*</span></label>
                    <select id="ojtDesignation" name="department" class="form-select @error('department') is-invalid @enderror" required>
                        <option value="">Select...</option>
                        @foreach($docs->designations($placement->department) as $designation)
                            <option value="{{ $designation }}" @selected(old('department', $placement->department) === $designation)>{{ $designation }}</option>
                        @endforeach
                    </select>
                    @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="ojtSupervisorName">Supervisor's name</label>
                    <input id="ojtSupervisorName" name="supervisor_name" maxlength="80" list="ojtSupervisorList" value="{{ old('supervisor_name', $placement->supervisor_name ?: $values['supervisor_name']) }}"
                           class="form-control @error('supervisor_name') is-invalid @enderror" placeholder="e.g. Maria L. Santos">
                    <datalist id="ojtSupervisorList">
                        @foreach($supervisors as $name)<option value="{{ $name }}"></option>@endforeach
                    </datalist>
                    @error('supervisor_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-outline-primary w-100">Save</button></div>
                <p class="form-text col-12 mb-0">Used on reports you submit from now on. Reports already submitted keep the details they were submitted with.</p>
            </form>
        @endif
    </div>
</section>
