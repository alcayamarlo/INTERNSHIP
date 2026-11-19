@extends('layouts.app')

@section('title', 'Academic settings')

@php
    // Tabs: [hash/id, label, icon]. The hash keeps the open tab across refreshes.
    $academicTabs = [
        'programs' => ['Programs', 'bi-mortarboard'],
        'requirements' => ['Deployment requirements', 'bi-file-earmark-check'],
        'calendar' => ['Calendar and student ID', 'bi-calendar3'],
    ];

    // Without a hash, open the tab whose form failed validation.
    $settingsFields = ['require_fourth_year_standing', 'require_major_subjects_passed', 'student_id_pattern', 'required_ojt_hours', 'academic_year_start_month', 'current_term_override', 'summer_enabled', 'term_ranges', 'journal_due_days'];
    $defaultTab = match (true) {
        collect($settingsFields)->contains(fn ($field) => $errors->has($field)) || collect($errors->keys())->contains(fn ($key) => preg_match('/^(first_semester|second_semester|summer)_|^evaluation_period_labels/', $key)) => 'calendar',
        $errors->hasAny(['name', 'upload_source', 'sort_order', 'is_active']) => 'requirements',
        default => 'programs',
    };
@endphp

@section('content')
<div>
<div class="sb-dash">
<x-page-header title="Academic settings" subtitle="Manage school programs, majors, internship hours, and academic calendar defaults.">
    @if($canManagePrograms)
        <a href="{{ route($academicsRoute.'programs.create') }}" class="btn btn-primary" data-open="program-modal" aria-haspopup="dialog"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add program</a>
    @endif
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
<div class="sb-section-tabs" role="tablist" aria-label="Academic settings sections" data-default-tab="{{ $defaultTab }}">
    @foreach($academicTabs as $tabId => [$tabLabel, $tabIcon])
        <button type="button" class="sb-section-tab" role="tab" id="aa-tab-{{ $tabId }}" aria-controls="aa-panel-{{ $tabId }}"
                data-tab="{{ $tabId }}" aria-selected="{{ $tabId === $defaultTab ? 'true' : 'false' }}" tabindex="{{ $tabId === $defaultTab ? '0' : '-1' }}">
            <i class="bi {{ $tabIcon }}" aria-hidden="true"></i> {{ $tabLabel }}
        </button>
    @endforeach
</div>

{{-- =========================================================
     PROGRAMS
========================================================== --}}
<div class="sb-tab-panel" role="tabpanel" id="aa-panel-programs" aria-labelledby="aa-tab-programs" tabindex="0" @if($defaultTab !== 'programs') hidden @endif>
    <section class="card">
        <div class="card-header"><h2><i class="bi bi-mortarboard" aria-hidden="true"></i> Programs</h2></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th scope="col">Program</th><th scope="col">Majors</th><th scope="col" class="text-nowrap">Required hours</th><th scope="col">Assigned coordinator</th><th scope="col">Students</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead>
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
                            <td>{{ $program->coordinatorNames() ?? 'No coordinator assigned' }}</td>
                            <td>{{ $program->students_count }}</td>
                            <td class="text-end text-nowrap" style="width:1%"><a class="btn btn-sm btn-outline-primary text-nowrap" href="{{ route($academicsRoute.'programs.edit', $program) }}"
                                data-open="program-edit-modal" aria-haspopup="dialog" data-program-edit
                                    data-action="{{ route($academicsRoute.'programs.update', $program) }}"
                                    data-id="{{ $program->id }}"
                                    data-name="{{ $program->name }}"
                                    data-code="{{ $program->code }}"
                                    data-hours="{{ $program->required_internship_hours }}"
                                    data-confirmed="{{ $program->required_hours_confirmed ? 1 : 0 }}"
                                    data-coordinator="{{ $program->coordinator?->user?->name ?? 'No coordinator assigned' }}"
                                    data-majors="{{ $program->majors->pluck('name')->join("\n") }}"><i class="bi bi-pencil" aria-hidden="true"></i> Edit</a></td>
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
<div class="sb-tab-panel" role="tabpanel" id="aa-panel-requirements" aria-labelledby="aa-tab-requirements" tabindex="0" @if($defaultTab !== 'requirements') hidden @endif>
    <div class="sb-form">

        <section class="card">
            <div class="card-header"><h2><i class="bi bi-file-earmark-check" aria-hidden="true"></i> Deployment requirements</h2></div>
            <div class="card-body">
                <p class="text-muted">Manage the documents students need before deployment. Requirements with uploaded files cannot be deleted; deactivate them to hide them from new submissions.</p>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th scope="col">Document</th><th scope="col">Uploaded by</th><th scope="col">Order</th><th scope="col">Active</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead>
                        <tbody>
                            @forelse($deploymentRequirements as $requirement)
                                <tr>
                                    {{-- One row = one PATCH form; fields in other cells join it through the form="" attribute. --}}
                                    @php($requirementFormId = 'deployment-requirement-'.$requirement->id)
                                    @php($sourceLocked = $requirement->portfolios()->exists() || $requirement->code === 'resume')
                                    <td>
                                        <form id="{{ $requirementFormId }}" method="POST" action="{{ route($academicsRoute.'deployment-requirements.update', $requirement) }}#requirements" class="m-0">
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
                                            <form method="POST" action="{{ route($academicsRoute.'deployment-requirements.destroy', $requirement) }}#requirements" class="d-inline" data-confirm="Delete this requirement?" data-confirm-title="Delete requirement">
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

        <section class="card">
            <div class="card-header"><h2><i class="bi bi-plus-circle" aria-hidden="true"></i> Add requirement</h2></div>
            <div class="card-body">
                <form method="POST" action="{{ route($academicsRoute.'deployment-requirements.store') }}#requirements" class="d-grid gap-3">
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
<div class="sb-tab-panel" role="tabpanel" id="aa-panel-calendar" aria-labelledby="aa-tab-calendar" tabindex="0" @if($defaultTab !== 'calendar') hidden @endif>
    <form method="POST" action="{{ route($academicsRoute.'settings.update') }}#calendar">
        @csrf
        <link rel="stylesheet" href="{{ asset('css/academic-calendar-layout.css') }}?v={{ @filemtime(public_path('css/academic-calendar-layout.css')) }}">
        <div class="aa-cal">
            <div class="aa-cal__col">

            <section class="card">
                <div class="card-header"><h2><i class="bi bi-calendar3" aria-hidden="true"></i> Term date ranges</h2></div>
                <div class="card-body">
                    <p class="sb-form-intro mb-3">Defaults follow typical Cebu school calendars. Confirm with the registrar.</p>
                    @error('term_ranges')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror

                    <div class="sb-matrix" role="group" aria-label="Term date ranges">
                        <div class="sb-matrix-head" aria-hidden="true">
                            <span>Term</span><span>Start month</span><span>Start day</span><span>End month</span><span>End day</span>
                        </div>
                        <?php foreach ($termOptions as $termKey => $termHeading):
                            $prefix = ['first' => 'first_semester', 'second' => 'second_semester', 'summer' => 'summer'][$termKey];
                        ?>
                            <div class="sb-matrix-row">
                                <h3 class="sb-matrix-name">{{ $termHeading }}</h3>
                                @foreach(['start' => 'Start', 'end' => 'End'] as $edge => $edgeLabel)
                                    <div class="sb-matrix-cell">
                                        <label class="sb-matrix-label" for="{{ $prefix }}_{{ $edge }}_month"><span class="visually-hidden">{{ $termHeading }} </span>{{ $edgeLabel }} month</label>
                                        <select id="{{ $prefix }}_{{ $edge }}_month" name="{{ $prefix }}_{{ $edge }}_month" class="form-select @error($prefix.'_'.$edge.'_month') is-invalid @enderror">
                                            @foreach($monthOptions as $month => $monthLabel)
                                                <option value="{{ $month }}" @selected((int) old($prefix.'_'.$edge.'_month', $settings->{$prefix.'_'.$edge.'_month'}) === $month)>{{ $monthLabel }}</option>
                                            @endforeach
                                        </select>
                                        @error($prefix.'_'.$edge.'_month')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="sb-matrix-cell">
                                        <label class="sb-matrix-label" for="{{ $prefix }}_{{ $edge }}_day"><span class="visually-hidden">{{ $termHeading }} </span>{{ $edgeLabel }} day</label>
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

            <section class="card" aria-labelledby="ojtDepartmentsHeading">
                <div class="card-header"><h2 id="ojtDepartmentsHeading"><i class="bi bi-diagram-3" aria-hidden="true"></i> OJT departments (Designation)</h2></div>
                <div class="card-body">
                    <label class="form-label" for="ojt_departments_text">One department per line</label>
                    <textarea id="ojt_departments_text" name="ojt_departments_text" rows="6" class="form-control form-control-sm @error('ojt_departments') is-invalid @enderror @error('ojt_departments.*') is-invalid @enderror">{{ old('ojt_departments_text', implode("\n", $settings->ojtDepartments())) }}</textarea>
                    @error('ojt_departments')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @foreach($errors->get('ojt_departments.*') as $messages)<div class="invalid-feedback d-block">{{ $messages[0] }}</div>@endforeach
                    <div class="form-text">Students and supervisors pick the trainee's Designation from this list, so it is spelled the same on every form. Removing a name does not change forms already submitted.</div>
                </div>
            </section>

            </div>
            <div class="aa-cal__col">

            <section class="card" aria-labelledby="evaluationPeriodsHeading">
                <div class="card-header"><h2 id="evaluationPeriodsHeading"><i class="bi bi-clipboard-data" aria-hidden="true"></i> Monthly evaluation periods</h2></div>
                <div class="card-body">
                    <p class="sb-form-intro mb-3">Name printed as "Period of Evaluation" on each month's Monthly Evaluation. Leave a month blank to print the month name. Changing a name does not change evaluations already requested.</p>
                    <div class="row g-2">
                        @foreach($settings->evaluationPeriodLabels() as $month => $label)
                            <div class="col-6 col-md-4">
                                <label class="form-label small mb-1" for="evaluationPeriod{{ $month }}">{{ \Illuminate\Support\Carbon::create(2000, $month, 1)->format('F') }}</label>
                                <input id="evaluationPeriod{{ $month }}" name="evaluation_period_labels[{{ $month }}]" maxlength="30" class="form-control form-control-sm @error('evaluation_period_labels.'.$month) is-invalid @enderror" value="{{ old('evaluation_period_labels.'.$month, $label) }}">
                                @error('evaluation_period_labels.'.$month)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3">
                        <label class="form-label" for="journal_due_days">Weekly report due (days after the week ends)</label>
                        <input type="number" min="0" max="14" id="journal_due_days" name="journal_due_days" value="{{ old('journal_due_days', $settings->journal_due_days ?? 3) }}" class="form-control form-control-sm sb-input-narrow @error('journal_due_days') is-invalid @enderror">
                        @error('journal_due_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">A week's Activity Narrative Report is due this many days after its Friday. Students are reminded before and when overdue.</div>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h2><i class="bi bi-person-check" aria-hidden="true"></i> Internship eligibility</h2></div>
                <div class="card-body d-grid gap-3">
                    <p class="sb-form-intro mb-0">Eligibility checks apply when students submit internship applications.</p>
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
                </div>
            </section>
                <div class="aa-cal__actions">
                    <button type="submit" class="btn btn-primary">Save academic settings</button>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Tab logic (plain JS). Runs right after the markup so a saved hash opens its tab before first paint. --}}
<script>
    (function () {
        var tablist = document.querySelector('.sb-section-tabs[role="tablist"]');
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
@include('admin.academics.partials.program-modal')
    <script>
        (function () {
            var dialog = document.getElementById('program-edit-modal');
            if (!dialog) return;
            var form = dialog.querySelector('form');
            function set(id, value) { var el = document.getElementById(id); if (el) el.value = value; }
            // Capture phase so the fields are filled before the dialog opens.
            document.addEventListener('click', function (e) {
                var btn = e.target.closest && e.target.closest('[data-program-edit]');
                if (!btn) return;
                form.action = btn.dataset.action;
                set('pe-id', btn.dataset.id);
                set('pe-name', btn.dataset.name);
                set('pe-code', btn.dataset.code);
                set('pe-hours', btn.dataset.hours);
                set('pe-majors', btn.dataset.majors || '');
                document.getElementById('pe-confirmed').checked = btn.dataset.confirmed === '1';
                document.getElementById('pe-coordinator').textContent = btn.dataset.coordinator;
                dialog.querySelectorAll('.is-invalid').forEach(function (n) { n.classList.remove('is-invalid'); });
                dialog.querySelectorAll('.invalid-feedback').forEach(function (n) { n.remove(); });
            }, true);
        })();
    </script>
</div>
</div>
@endsection
