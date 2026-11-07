@extends('layouts.app')

@section('title', 'Academic settings')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-academics.css') }}?v={{ @filemtime(public_path('css/admin-academics.css')) }}">
@endpush

@php
    // Tabs: [hash/id, label, icon]. The hash keeps the open tab across refreshes.
    $academicTabs = [
        'programs' => ['Programs', 'bi-mortarboard'],
        'requirements' => ['Deployment requirements', 'bi-file-earmark-check'],
        'calendar' => ['Calendar and student ID', 'bi-calendar3'],
    ];

    // Without a hash, open the tab whose form failed validation.
    $settingsFields = ['require_fourth_year_standing', 'require_major_subjects_passed', 'student_id_pattern', 'required_ojt_hours', 'academic_year_start_month', 'current_term_override', 'summer_enabled', 'term_ranges'];
    $defaultTab = match (true) {
        collect($settingsFields)->contains(fn ($field) => $errors->has($field)) || collect($errors->keys())->contains(fn ($key) => preg_match('/^(first_semester|second_semester|summer)_/', $key)) => 'calendar',
        $errors->hasAny(['name', 'upload_source', 'sort_order', 'is_active']) => 'requirements',
        default => 'programs',
    };
@endphp

@section('content')
<div class="admin-academics-page">
<div class="aa-tabbed">
<x-page-header title="Academic settings" subtitle="Manage school programs, majors, internship hours, and academic calendar defaults.">
    <a href="{{ route('admin.academics.programs.create') }}" class="btn btn-primary aa-add-btn"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add program</a>
</x-page-header>

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

{{-- =========================================================
     TABS
========================================================== --}}
<div class="aa-tabs" role="tablist" aria-label="Academic settings sections" data-default-tab="{{ $defaultTab }}">
    @foreach($academicTabs as $tabId => [$tabLabel, $tabIcon])
        <button type="button" class="aa-tab" role="tab" id="aa-tab-{{ $tabId }}" aria-controls="aa-panel-{{ $tabId }}"
                data-tab="{{ $tabId }}" aria-selected="{{ $tabId === $defaultTab ? 'true' : 'false' }}" tabindex="{{ $tabId === $defaultTab ? '0' : '-1' }}">
            <i class="bi {{ $tabIcon }}" aria-hidden="true"></i> {{ $tabLabel }}
        </button>
    @endforeach
</div>

{{-- =========================================================
     PROGRAMS
========================================================== --}}
<div class="aa-panel" role="tabpanel" id="aa-panel-programs" aria-labelledby="aa-tab-programs" tabindex="0" @if($defaultTab !== 'programs') hidden @endif>
    <section class="card aa-card">
        <div class="card-header"><h2><i class="bi bi-mortarboard" aria-hidden="true"></i> Programs</h2></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle aa-table">
                <thead><tr><th scope="col">Program</th><th scope="col">Majors</th><th scope="col">Required hours</th><th scope="col">Assigned coordinator</th><th scope="col">Students</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse($programs as $program)
                        <tr>
                            <td>{{ $program->display_name }}</td>
                            <td>{{ $program->majors->pluck('name')->join(', ') ?: 'No majors' }}</td>
                            <td>
                                {{ number_format($program->required_internship_hours) }}
                                @unless($program->required_hours_confirmed)
                                    <span class="badge bg-warning">To be confirmed</span>
                                @endunless
                            </td>
                            <td>{{ $program->coordinator?->user?->name ?? 'Not assigned' }}</td>
                            <td>{{ $program->students_count }}</td>
                            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.academics.programs.edit', $program) }}">Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state icon="bi-mortarboard" title="No programs configured" message="Add a program so students can register against it." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

{{-- =========================================================
     DEPLOYMENT REQUIREMENTS
     Form actions end in #requirements: the redirect back keeps that hash, so this tab stays open.
========================================================== --}}
<div class="aa-panel" role="tabpanel" id="aa-panel-requirements" aria-labelledby="aa-tab-requirements" tabindex="0" @if($defaultTab !== 'requirements') hidden @endif>
    <div class="aa-columns aa-columns--wide-left">

        <section class="card aa-card">
            <div class="card-header"><h2><i class="bi bi-file-earmark-check" aria-hidden="true"></i> Deployment requirements</h2></div>
            <div class="card-body">
                <p class="text-muted">Manage the documents students need before deployment. Requirements with uploaded files cannot be deleted; deactivate them to hide them from new submissions.</p>
                <div class="table-responsive">
                    <table class="table table-hover align-middle aa-table aa-requirements-table">
                        <thead><tr><th scope="col">Document</th><th scope="col">Uploaded by</th><th scope="col">Order</th><th scope="col">Active</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead>
                        <tbody>
                            @forelse($deploymentRequirements as $requirement)
                                <tr>
                                    {{-- One row = one PATCH form; fields in other cells join it through the form="" attribute. --}}
                                    @php($requirementFormId = 'deployment-requirement-'.$requirement->id)
                                    @php($sourceLocked = $requirement->portfolios()->exists() || $requirement->code === 'resume')
                                    <td>
                                        <form id="{{ $requirementFormId }}" method="POST" action="{{ route('admin.academics.deployment-requirements.update', $requirement) }}#requirements" class="m-0">
                                            @csrf
                                            @method('PATCH')
                                            <input class="form-control" name="name" value="{{ $requirement->name }}" maxlength="255" aria-label="Document name" title="{{ $requirement->name }}" required>
                                        </form>
                                    </td>
                                    <td>
                                        <select class="form-select" form="{{ $requirementFormId }}" name="upload_source" aria-label="Uploaded by" @disabled($sourceLocked)>
                                            <option value="student" @selected($requirement->upload_source === 'student')>Student</option>
                                            <option value="coordinator" @selected($requirement->upload_source === 'coordinator')>Coordinator</option>
                                            @if($requirement->code === 'resume')
                                                <option value="system" @selected($requirement->upload_source === 'system')>System generated</option>
                                            @endif
                                        </select>
                                        @if($sourceLocked)
                                            <input type="hidden" form="{{ $requirementFormId }}" name="upload_source" value="{{ $requirement->upload_source }}">
                                        @endif
                                    </td>
                                    <td>
                                        <input class="form-control" form="{{ $requirementFormId }}" type="number" name="sort_order" min="1" max="65535" value="{{ $requirement->sort_order }}" aria-label="Display order" required>
                                    </td>
                                    <td>
                                        <div class="form-check mb-0">
                                            <input type="hidden" form="{{ $requirementFormId }}" name="is_active" value="0">
                                            <input class="form-check-input" form="{{ $requirementFormId }}" type="checkbox" id="{{ $requirementFormId }}-active" name="is_active" value="1" @checked($requirement->is_active)>
                                            <label class="form-check-label" for="{{ $requirementFormId }}-active">{{ $requirement->is_active ? 'Active' : 'Inactive' }}</label>
                                        </div>
                                    </td>
                                    <td class="text-nowrap text-end">
                                        <button class="btn btn-sm btn-outline-primary" type="submit" form="{{ $requirementFormId }}">Save</button>
                                        @if(!$requirement->portfolios()->exists())
                                            <form method="POST" action="{{ route('admin.academics.deployment-requirements.destroy', $requirement) }}#requirements" class="d-inline" data-confirm="Delete this requirement?" data-confirm-title="Delete requirement">
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
                                <tr><td colspan="5"><x-empty-state icon="bi-file-earmark-check" title="No deployment requirements" message="Add the documents students must submit before deployment." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="card aa-card aa-card--second">
            <div class="card-header"><h2><i class="bi bi-plus-circle" aria-hidden="true"></i> Add requirement</h2></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.academics.deployment-requirements.store') }}#requirements" class="d-grid gap-3">
                    @csrf
                    <div>
                        <label class="form-label" for="new_requirement_name">Document name <span class="text-danger" aria-hidden="true">*</span></label>
                        <input class="form-control" id="new_requirement_name" name="name" value="{{ old('name') }}" maxlength="255" required>
                    </div>
                    <div>
                        <label class="form-label" for="new_requirement_source">Uploaded by <span class="text-danger" aria-hidden="true">*</span></label>
                        <select class="form-select" id="new_requirement_source" name="upload_source" required>
                            <option value="student">Student</option>
                            <option value="coordinator">Coordinator</option>
                        </select>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Add requirement</button>
                </form>
            </div>
        </section>

    </div>
</div>

{{-- =========================================================
     CALENDAR AND STUDENT ID (one form, two cards)
========================================================== --}}
<div class="aa-panel" role="tabpanel" id="aa-panel-calendar" aria-labelledby="aa-tab-calendar" tabindex="0" @if($defaultTab !== 'calendar') hidden @endif>
    <form method="POST" action="{{ route('admin.academics.settings.update') }}#calendar">
        @csrf
        <div class="aa-columns aa-columns--calendar">

            <section class="card aa-card">
                <div class="card-header"><h2><i class="bi bi-calendar3" aria-hidden="true"></i> Term date ranges</h2></div>
                <div class="card-body">
                    <p class="academic-section-subtitle mb-3">Defaults follow typical Cebu school calendars. Confirm with the registrar.</p>
                    @error('term_ranges')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror

                    <div class="aa-term-grid" role="group" aria-label="Term date ranges">
                        <div class="aa-term-head" aria-hidden="true">
                            <span>Term</span><span>Start month</span><span>Start day</span><span>End month</span><span>End day</span>
                        </div>
                        <?php foreach ($termOptions as $termKey => $termHeading):
                            $prefix = ['first' => 'first_semester', 'second' => 'second_semester', 'summer' => 'summer'][$termKey];
                        ?>
                            <div class="aa-term-row">
                                <h3 class="aa-term-name">{{ $termHeading }}</h3>
                                @foreach(['start' => 'Start', 'end' => 'End'] as $edge => $edgeLabel)
                                    <div class="aa-term-cell">
                                        <label class="aa-term-label" for="{{ $prefix }}_{{ $edge }}_month"><span class="visually-hidden">{{ $termHeading }} </span>{{ $edgeLabel }} month</label>
                                        <select id="{{ $prefix }}_{{ $edge }}_month" name="{{ $prefix }}_{{ $edge }}_month" class="form-select @error($prefix.'_'.$edge.'_month') is-invalid @enderror">
                                            @foreach($monthOptions as $month => $monthLabel)
                                                <option value="{{ $month }}" @selected((int) old($prefix.'_'.$edge.'_month', $settings->{$prefix.'_'.$edge.'_month'}) === $month)>{{ $monthLabel }}</option>
                                            @endforeach
                                        </select>
                                        @error($prefix.'_'.$edge.'_month')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="aa-term-cell">
                                        <label class="aa-term-label" for="{{ $prefix }}_{{ $edge }}_day"><span class="visually-hidden">{{ $termHeading }} </span>{{ $edgeLabel }} day</label>
                                        <input id="{{ $prefix }}_{{ $edge }}_day" name="{{ $prefix }}_{{ $edge }}_day" type="number" min="1" max="31" value="{{ old($prefix.'_'.$edge.'_day', $settings->{$prefix.'_'.$edge.'_day'}) }}" class="form-control @error($prefix.'_'.$edge.'_day') is-invalid @enderror">
                                        @error($prefix.'_'.$edge.'_day')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                @endforeach
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-check mt-3">
                        <input type="hidden" name="summer_enabled" value="0">
                        <input class="form-check-input" type="checkbox" id="summer_enabled" name="summer_enabled" value="1" @checked(old('summer_enabled', $settings->summer_enabled))>
                        <label class="form-check-label" for="summer_enabled">Enable Summer term</label>
                    </div>
                    @error('summer_enabled')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

                    <hr>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="academic_year_start_month">Academic year start month</label>
                            <select id="academic_year_start_month" name="academic_year_start_month" class="form-select @error('academic_year_start_month') is-invalid @enderror">
                                @foreach($monthOptions as $month => $label)
                                    <option value="{{ $month }}" @selected((int) old('academic_year_start_month', $settings->academic_year_start_month) === $month)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('academic_year_start_month')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">The 1st semester start month must match.</div>
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
                    </div>
                </div>
            </section>

            <section class="card aa-card aa-card--second">
                <div class="card-header"><h2><i class="bi bi-person-check" aria-hidden="true"></i> Internship eligibility</h2></div>
                <div class="card-body d-grid gap-3">
                    <p class="academic-section-subtitle mb-0">Eligibility checks apply when students submit internship applications.</p>
                    <div>
                        <input type="hidden" name="require_fourth_year_standing" value="0">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="require_fourth_year_standing" name="require_fourth_year_standing" value="1" @checked(old('require_fourth_year_standing', $settings->require_fourth_year_standing))>
                            <label class="form-check-label" for="require_fourth_year_standing">Require 4th-year standing</label>
                        </div>
                        @error('require_fourth_year_standing')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <input type="hidden" name="require_major_subjects_passed" value="0">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="require_major_subjects_passed" name="require_major_subjects_passed" value="1" @checked(old('require_major_subjects_passed', $settings->require_major_subjects_passed))>
                            <label class="form-check-label" for="require_major_subjects_passed">Require all major subjects passed</label>
                        </div>
                        @error('require_major_subjects_passed')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label" for="student_id_pattern">Student ID format <span class="text-danger" aria-hidden="true">*</span></label>
                        <input id="student_id_pattern" name="student_id_pattern" value="{{ old('student_id_pattern', $settings->student_id_pattern) }}" class="form-control @error('student_id_pattern') is-invalid @enderror" required>
                        @error('student_id_pattern')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Enter a regular-expression pattern without delimiters. Example: SCC-21-00013240.</div>
                    </div>
                    <div>
                        <label class="form-label" for="required_ojt_hours">Required OJT hours <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="number" min="1" max="2000" id="required_ojt_hours" name="required_ojt_hours" value="{{ old('required_ojt_hours', $settings->required_ojt_hours ?? 300) }}" class="form-control @error('required_ojt_hours') is-invalid @enderror" required>
                        @error('required_ojt_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Fallback for new placements when the student's program has no required internship hours. Existing placements keep their target.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Save academic settings</button>
                </div>
            </section>

        </div>
    </form>
</div>

{{-- Tab logic (plain JS). Runs right after the markup so a saved hash opens its tab before first paint. --}}
<script>
    (function () {
        var tablist = document.querySelector('.aa-tabs');
        if (!tablist) return;
        var tabs = Array.prototype.slice.call(tablist.querySelectorAll('[role="tab"]'));
        var ids = tabs.map(function (tab) { return tab.dataset.tab; });

        function show(id, options) {
            options = options || {};
            if (ids.indexOf(id) === -1) return;
            tabs.forEach(function (tab) {
                var active = tab.dataset.tab === id;
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
                tab.tabIndex = active ? 0 : -1;
                document.getElementById(tab.getAttribute('aria-controls')).hidden = !active;
            });
            if (options.updateHash && location.hash !== '#' + id) {
                history.replaceState(null, '', '#' + id);
            }
            if (options.focus) {
                tabs[ids.indexOf(id)].focus();
            }
        }

        tabs.forEach(function (tab, index) {
            tab.addEventListener('click', function () {
                show(tab.dataset.tab, { updateHash: true });
            });
            // Arrow keys, Home and End move between tabs (WAI-ARIA tabs pattern).
            tab.addEventListener('keydown', function (event) {
                var next = { ArrowRight: index + 1, ArrowLeft: index - 1, Home: 0, End: tabs.length - 1 }[event.key];
                if (next === undefined) return;
                event.preventDefault();
                next = (next + tabs.length) % tabs.length;
                show(ids[next], { updateHash: true, focus: true });
            });
        });

        window.addEventListener('hashchange', function () {
            show(location.hash.slice(1));
        });

        var fromHash = location.hash.slice(1);
        show(ids.indexOf(fromHash) !== -1 ? fromHash : tablist.dataset.defaultTab);
    })();
</script>
</div>
</div>
@endsection
