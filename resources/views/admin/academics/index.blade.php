@extends('layouts.app')

@section('title', 'Academic settings')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-academics.css') }}">
@endpush

@section('content')
<div class="admin-academics-page">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Academic settings</h1>
        <p class="text-muted mb-0">Manage school programs, majors, internship hours, and academic calendar defaults.</p>
    </div>
    <a href="{{ route('admin.academics.programs.create') }}" class="btn btn-primary">Add program</a>
</div>

@if($errors->any())
    <div class="alert alert-danger" role="alert" aria-labelledby="academic-settings-errors-title">
        <h2 class="h6 mb-2" id="academic-settings-errors-title">Please correct the following settings:</h2>
        <ul class="mb-0">
            @foreach($errors->getMessages() as $field => $messages)
                @foreach($messages as $message)
                    <li><strong>{{ \Illuminate\Support\Str::headline($field) }}:</strong> {{ $message }}</li>
                @endforeach
            @endforeach
        </ul>
    </div>
@endif

<section class="card mb-4">
    <div class="card-header"><h2 class="h5 mb-0">Programs</h2></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Program</th><th>Majors</th><th>Required hours</th><th>Assigned coordinator</th><th>Students</th><th></th></tr></thead>
            <tbody>
                @forelse($programs as $program)
                    <tr>
                        <td>{{ $program->display_name }}</td>
                        <td>{{ $program->majors->pluck('name')->join(', ') ?: 'No majors' }}</td>
                        <td>
                            {{ number_format($program->required_internship_hours) }}
                            @unless($program->required_hours_confirmed)
                                <span class="badge text-bg-warning">To be confirmed</span>
                            @endunless
                        </td>
                        <td>{{ $program->coordinator?->user?->name ?? 'Not assigned' }}</td>
                        <td>{{ $program->students_count }}</td>
                        <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.academics.programs.edit', $program) }}">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No programs are configured.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="card mb-4">
    <div class="card-header"><h2 class="h5 mb-0">Deployment requirements</h2></div>
    <div class="card-body">
        <p class="text-muted">Manage the documents students need before deployment. Requirements with uploaded files cannot be deleted; deactivate them to hide them from new submissions.</p>
        <form method="POST" action="{{ route('admin.academics.deployment-requirements.store') }}" class="row g-3 align-items-end mb-4">
            @csrf
            <div class="col-md-6">
                <label class="form-label" for="new_requirement_name">Document name</label>
                <input class="form-control" id="new_requirement_name" name="name" value="{{ old('name') }}" maxlength="255" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="new_requirement_source">Uploaded by</label>
                <select class="form-select" id="new_requirement_source" name="upload_source" required>
                    <option value="student">Student</option>
                    <option value="coordinator">Coordinator</option>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Add requirement</button></div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Document</th><th>Uploaded by</th><th>Order</th><th>Active</th><th></th></tr></thead>
                <tbody>
                    @forelse($deploymentRequirements as $requirement)
                        <tr>
                            <td>
                                @php($requirementFormId = 'deployment-requirement-'.$requirement->id)
                                <form id="{{ $requirementFormId }}" method="POST" action="{{ route('admin.academics.deployment-requirements.update', $requirement) }}" class="row g-2 align-items-center">
                                    @csrf
                                    @method('PATCH')
                                    <div class="col-lg-5"><input class="form-control" name="name" value="{{ $requirement->name }}" maxlength="255" aria-label="Document name" required></div>
                                    <div class="col-lg-4">
                                        <select class="form-select" name="upload_source" aria-label="Upload source" @disabled($requirement->portfolios()->exists() || $requirement->code === 'resume')>
                                            <option value="student" @selected($requirement->upload_source === 'student')>Student</option>
                                            <option value="coordinator" @selected($requirement->upload_source === 'coordinator')>Coordinator</option>
                                            @if($requirement->code === 'resume')
                                                <option value="system" @selected($requirement->upload_source === 'system')>System generated</option>
                                            @endif
                                        </select>
                                        @if($requirement->portfolios()->exists() || $requirement->code === 'resume')
                                            <input type="hidden" name="upload_source" value="{{ $requirement->upload_source }}">
                                        @endif
                                    </div>
                                    <div class="col-lg-2"><input class="form-control" type="number" name="sort_order" min="1" max="65535" value="{{ $requirement->sort_order }}" aria-label="Display order" required></div>
                                </form>
                            </td>
                            <td>{{ \Illuminate\Support\Str::headline($requirement->upload_source) }}</td>
                            <td>{{ $requirement->sort_order }}</td>
                            <td>
                                <input type="hidden" form="{{ $requirementFormId }}" name="is_active" value="0">
                                <input class="form-check-input" form="{{ $requirementFormId }}" type="checkbox" id="{{ $requirementFormId }}-active" name="is_active" value="1" @checked($requirement->is_active)>
                                <label class="form-check-label" for="{{ $requirementFormId }}-active">{{ $requirement->is_active ? 'Active' : 'Inactive' }}</label>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" type="submit" form="{{ $requirementFormId }}">Save</button>
                                @if(!$requirement->portfolios()->exists())
                                    <form method="POST" action="{{ route('admin.academics.deployment-requirements.destroy', $requirement) }}" onsubmit="return confirm('Delete this requirement?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                    </form>
                                @else
                                    <span class="text-muted small">In use</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No deployment requirements are configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="card">
    <div class="card-header"><h2 class="h5 mb-0">Academic calendar and student ID settings</h2></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.academics.settings.update') }}">
            @csrf
            <div class="row g-3">
                <div class="col-12">
                    <h3 class="h6 mb-0">Internship eligibility</h3>
                    <p class="academic-section-subtitle">Eligibility checks apply when students submit internship applications.</p>
                </div>
                <div class="col-md-6">
                    <input type="hidden" name="require_fourth_year_standing" value="0">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="require_fourth_year_standing" name="require_fourth_year_standing" value="1" @checked(old('require_fourth_year_standing', $settings->require_fourth_year_standing))>
                        <label class="form-check-label" for="require_fourth_year_standing">Require 4th-year standing</label>
                    </div>
                    @error('require_fourth_year_standing')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <input type="hidden" name="require_major_subjects_passed" value="0">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="require_major_subjects_passed" name="require_major_subjects_passed" value="1" @checked(old('require_major_subjects_passed', $settings->require_major_subjects_passed))>
                        <label class="form-check-label" for="require_major_subjects_passed">Require all major subjects passed</label>
                    </div>
                    @error('require_major_subjects_passed')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="student_id_pattern">Student ID format</label>
                    <input id="student_id_pattern" name="student_id_pattern" value="{{ old('student_id_pattern', $settings->student_id_pattern) }}" class="form-control @error('student_id_pattern') is-invalid @enderror" required>
                    @error('student_id_pattern')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="form-text">Enter a regular-expression pattern without delimiters. Example: SCC-21-00013240.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="academic_year_start_month">Academic year start month</label>
                    <select id="academic_year_start_month" name="academic_year_start_month" class="form-select @error('academic_year_start_month') is-invalid @enderror">
                        @foreach($monthOptions as $month => $label)
                            <option value="{{ $month }}" @selected((int) old('academic_year_start_month', $settings->academic_year_start_month) === $month)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('academic_year_start_month')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="form-text">The 1st semester start month must match.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="current_term_override">Current term override</label>
                    <select id="current_term_override" name="current_term_override" class="form-select @error('current_term_override') is-invalid @enderror">
                        <option value="">Use date-based term</option>
                        @foreach($termOptions as $key => $label)
                            <option value="{{ $key }}" @selected(old('current_term_override', $settings->current_term_override) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('current_term_override')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input type="hidden" name="summer_enabled" value="0">
                        <input class="form-check-input" type="checkbox" id="summer_enabled" name="summer_enabled" value="1" @checked(old('summer_enabled', $settings->summer_enabled))>
                        <label class="form-check-label" for="summer_enabled">Enable Summer term</label>
                    </div>
                    @error('summer_enabled')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
            </div>

            <hr>
            <h3 class="h6">Term date ranges</h3>
            <p class="academic-section-subtitle mb-3">Defaults follow typical Cebu school calendars. Confirm with the registrar.</p>
            @error('term_ranges')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
            <div class="row g-3">
                <?php foreach ($termOptions as $termKey => $termHeading):
                    $prefix = ['first' => 'first_semester', 'second' => 'second_semester', 'summer' => 'summer'][$termKey];
                ?>
                    <div class="col-12"><h4 class="h6 mb-0">{{ $termHeading }}</h4></div>
                    @foreach(['start' => 'Start', 'end' => 'End'] as $edge => $edgeLabel)
                        <div class="col-md-6">
                            <label class="form-label" for="{{ $prefix }}_{{ $edge }}_month">{{ $edgeLabel }} month</label>
                            <select id="{{ $prefix }}_{{ $edge }}_month" name="{{ $prefix }}_{{ $edge }}_month" class="form-select @error($prefix.'_'.$edge.'_month') is-invalid @enderror">
                                @foreach($monthOptions as $month => $monthLabel)
                                    <option value="{{ $month }}" @selected((int) old($prefix.'_'.$edge.'_month', $settings->{$prefix.'_'.$edge.'_month'}) === $month)>{{ $monthLabel }}</option>
                                @endforeach
                            </select>
                            @error($prefix.'_'.$edge.'_month')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="{{ $prefix }}_{{ $edge }}_day">{{ $edgeLabel }} day</label>
                            <input id="{{ $prefix }}_{{ $edge }}_day" name="{{ $prefix }}_{{ $edge }}_day" type="number" min="1" max="31" value="{{ old($prefix.'_'.$edge.'_day', $settings->{$prefix.'_'.$edge.'_day'}) }}" class="form-control @error($prefix.'_'.$edge.'_day') is-invalid @enderror">
                            @error($prefix.'_'.$edge.'_day')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary mt-4">Save academic settings</button>
        </form>
    </div>
</section>
</div>
@endsection
