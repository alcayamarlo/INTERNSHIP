@extends('layouts.app')

@section('title', 'My OJT')

@section('content')
<x-page-header title="My OJT" subtitle="Log your daily time, submit weekly reports and track your progress toward completion." />
@if(! $placement)
    <div class="card">
        <div class="card-body">
            <x-empty-state icon="bi-clock-history" title="No OJT placement yet" message="Your OJT record opens automatically once an employer accepts one of your applications.">
                <a href="{{ route('student.internships.index') }}" class="btn btn-primary">Browse internships</a>
            </x-empty-state>
        </div>
    </div>
@else
    @php
        $tabs = [
            'progress' => ['Progress', 'bi-speedometer2'],
            'time-record' => ['Time Record', 'bi-clock-history'],
            'weekly-journal' => ['Weekly Report', 'bi-journal-text'],
            'monthly-evaluation' => ['Monthly Evaluation', 'bi-clipboard-data'],
        ];
        $activeTab = array_key_exists((string) request('tab'), $tabs) ? request('tab') : 'progress';
        // A failed monthly evaluation submit reopens that tab.
        if (! request('tab') && old('appraisal_id')) {
            $activeTab = 'monthly-evaluation';
        }
        $canLog = $placement->isActive() && ! $placement->start_date->isFuture();
    @endphp

    {{-- The placement picker sits above the tabs and applies to whichever tab is open. --}}
    @if($placements->count() > 1)
        <form method="GET" class="mb-3 d-flex gap-2 align-items-center" id="placementForm">
            <input type="hidden" name="tab" value="{{ $activeTab }}" data-ojt-tab-field>
            <label for="placementSelect" class="form-label mb-0 small">Placement</label>
            <select id="placementSelect" name="placement" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                @foreach($placements as $option)
                    <option value="{{ $option->id }}" @selected($option->id === $placement->id)>{{ $option->internship?->title }} — {{ $option->employer?->company_name }} ({{ $option->statusLabel() }})</option>
                @endforeach
            </select>
        </form>
    @endif

    <div class="sb-tabbar">
        <div class="sb-section-tabs" role="tablist" aria-label="OJT sections" data-ojt-tabs>
            @foreach($tabs as $key => [$label, $icon])
                <button type="button" role="tab" id="ojt-tab-{{ $key }}" class="sb-section-tab" data-tab="{{ $key }}"
                        aria-controls="ojt-panel-{{ $key }}" aria-selected="{{ $key === $activeTab ? 'true' : 'false' }}"
                        tabindex="{{ $key === $activeTab ? '0' : '-1' }}">
                    <i class="bi {{ $icon }}" aria-hidden="true"></i> {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Action for the open tab, on the right of the tab bar. Progress has none. --}}
        @if($canLog)
            <button type="button" class="btn btn-primary btn-sm flex-shrink-0" data-ojt-action="time-record" data-bs-toggle="modal" data-bs-target="#dailyEntryModal" @if($activeTab !== 'time-record') hidden @endif><i class="bi bi-plus-lg" aria-hidden="true"></i> Add daily entry</button>
            <button type="button" class="btn btn-primary btn-sm flex-shrink-0" data-ojt-action="weekly-journal" data-bs-toggle="modal" data-bs-target="#weeklyJournalModal" @if($activeTab !== 'weekly-journal') hidden @endif><i class="bi bi-plus-lg" aria-hidden="true"></i> Add weekly report</button>
        @endif
    </div>

    {{-- PROGRESS --}}
    <section id="ojt-panel-progress" class="sb-tab-panel" role="tabpanel" aria-labelledby="ojt-tab-progress" @if($activeTab !== 'progress') hidden @endif>
        @include('ojt.partials.deadlines', ['placement' => $placement, 'actor' => 'student'])
        @include('ojt.partials.progress', ['placement' => $placement])
        @include('ojt.partials.document-details', ['placement' => $placement])
    </section>

    {{-- TIME RECORD --}}
    <section id="ojt-panel-time-record" class="sb-tab-panel" role="tabpanel" aria-labelledby="ojt-tab-time-record" @if($activeTab !== 'time-record') hidden @endif>
        @if($placement->isActive() && $placement->start_date->isFuture())
            <div class="sb-callout mt-0 mb-4">
                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                Your OJT starts on {{ $placement->start_date->format('F d, Y') }}. You can log time and submit journals from that date.
            </div>
        @endif
        @include('ojt.partials.attendance', ['placement' => $placement, 'mode' => 'student'])
    </section>

    {{-- WEEKLY JOURNAL --}}
    <section id="ojt-panel-weekly-journal" class="sb-tab-panel" role="tabpanel" aria-labelledby="ojt-tab-weekly-journal" @if($activeTab !== 'weekly-journal') hidden @endif>
        @if($placement->isActive() && $placement->start_date->isFuture())
            <div class="sb-callout mt-0 mb-4">
                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                Your OJT starts on {{ $placement->start_date->format('F d, Y') }}. You can log time and submit journals from that date.
            </div>
        @endif
        @include('ojt.partials.journals', ['placement' => $placement, 'mode' => 'student'])
    </section>

    {{-- MONTHLY EVALUATION --}}
    <section id="ojt-panel-monthly-evaluation" class="sb-tab-panel" role="tabpanel" aria-labelledby="ojt-tab-monthly-evaluation" @if($activeTab !== 'monthly-evaluation') hidden @endif>
        @include('ojt.partials.appraisals', ['placement' => $placement, 'mode' => 'student'])
    </section>

    @push('scripts')
    <script>
        (function () {
            var bar = document.querySelector('[data-ojt-tabs]');
            if (!bar) return;
            var tabs = [].slice.call(bar.querySelectorAll('[role="tab"]'));
            var field = document.querySelector('[data-ojt-tab-field]');

            function select(key, focus) {
                tabs.forEach(function (t) {
                    var on = t.dataset.tab === key;
                    t.setAttribute('aria-selected', on ? 'true' : 'false');
                    t.tabIndex = on ? 0 : -1;
                    document.getElementById('ojt-panel-' + t.dataset.tab).hidden = !on;
                    if (on && focus) t.focus();
                });
                [].forEach.call(document.querySelectorAll('[data-ojt-action]'), function (b) { b.hidden = b.dataset.ojtAction !== key; });
                if (field) field.value = key;
                var url = new URL(location.href);
                url.searchParams.set('tab', key);
                try { history.replaceState(null, '', url); } catch (_) {}
            }
            tabs.forEach(function (t, i) {
                t.addEventListener('click', function () { select(t.dataset.tab, false); });
                t.addEventListener('keydown', function (e) {
                    var next = e.key === 'ArrowRight' ? i + 1 : e.key === 'ArrowLeft' ? i - 1 : e.key === 'Home' ? 0 : e.key === 'End' ? tabs.length - 1 : null;
                    if (next === null) return;
                    e.preventDefault();
                    select(tabs[(next + tabs.length) % tabs.length].dataset.tab, true);
                });
            });
        })();
    </script>
    @endpush

    @if($canLog)
    <div class="modal fade" id="dailyEntryModal" tabindex="-1" aria-labelledby="dailyEntryModalLabel" aria-hidden="true" data-sb-form-modal @if(old('_modal') === 'daily') data-sb-open-on-load @endif>
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title h5" id="dailyEntryModalLabel">Add daily entry</h2>
                        <p class="wr-subtitle">Log the hours you worked and what you did.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="dailyEntryForm" method="POST" action="{{ route('student.ojt.attendance.store', $placement) }}">
                        @csrf
                        <input type="hidden" name="_modal" value="daily">

                        <section class="wr-section" aria-labelledby="de-time-title">
                            <div class="wr-section-head">
                                <span class="wr-step" aria-hidden="true">1</span>
                                <div>
                                    <h3 class="wr-section-title" id="de-time-title">Date and hours</h3>
                                    <p class="wr-section-hint">When did you work, and how long was your break?</p>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="work_date">Date <span class="sb-req" aria-hidden="true">*</span></label>
                                <input type="date" id="work_date" name="work_date" value="{{ old('work_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" min="{{ $placement->start_date->toDateString() }}" class="form-control @error('work_date') is-invalid @enderror" required>
                                @error('work_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="wr-times">
                                <div>
                                    <label class="form-label" for="time_in">Time in <span class="sb-req" aria-hidden="true">*</span></label>
                                    <input type="time" id="time_in" name="time_in" value="{{ old('time_in', '08:00') }}" class="form-control @error('time_in') is-invalid @enderror" required>
                                    @error('time_in')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="form-label" for="time_out">Time out <span class="sb-req" aria-hidden="true">*</span></label>
                                    <input type="time" id="time_out" name="time_out" value="{{ old('time_out', '17:00') }}" class="form-control @error('time_out') is-invalid @enderror" required>
                                    @error('time_out')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label class="form-label" for="break_minutes">Break (min)</label>
                                    <input type="number" id="break_minutes" name="break_minutes" min="0" max="240" value="{{ old('break_minutes', 60) }}" class="form-control @error('break_minutes') is-invalid @enderror">
                                    @error('break_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="wr-summary" data-de-summary aria-live="polite">
                                <span>Total hours for this day</span>
                                <strong data-de-total>8h 0m</strong>
                            </div>
                        </section>

                        <section class="wr-section" aria-labelledby="de-tasks-title">
                            <div class="wr-section-head">
                                <span class="wr-step" aria-hidden="true">2</span>
                                <div>
                                    <h3 class="wr-section-title" id="de-tasks-title">Tasks done</h3>
                                    <p class="wr-section-hint">Briefly describe what you worked on today.</p>
                                </div>
                            </div>
                            <textarea id="task_summary" name="task_summary" rows="4" maxlength="2000" aria-label="Tasks done" aria-describedby="task_summary-count" data-de-count class="form-control @error('task_summary') is-invalid @enderror" required>{{ old('task_summary') }}</textarea>
                            @error('task_summary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="wr-counter" id="task_summary-count" aria-live="polite">0 / 2000</div>
                        </section>
                    </form>
                </div>
                <div class="modal-footer">
                    <span class="wr-footer-note"><span class="sb-req">*</span> Required</span>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" form="dailyEntryForm">Submit time entry</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="weeklyJournalModal" tabindex="-1" aria-labelledby="weeklyJournalModalLabel" aria-hidden="true" data-sb-form-modal @if(old('_modal') === 'weekly') data-sb-open-on-load @endif>
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title h5" id="weeklyJournalModalLabel">Add weekly activity narrative report</h2>
                        <p class="wr-subtitle">Confirm the details, pick the week, then write about it.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="weeklyJournalForm" method="POST" action="{{ route('student.ojt.journals.store', $placement) }}">
                        @csrf
                        <input type="hidden" name="_modal" value="weekly">
                        @include('ojt.partials.report-header-fields', ['placement' => $placement])

                        <section class="wr-section" aria-labelledby="wr-week-title">
                            <div class="wr-section-head">
                                <span class="wr-step" aria-hidden="true">2</span>
                                <div>
                                    <h3 class="wr-section-title" id="wr-week-title">Reporting week</h3>
                                    <p class="wr-section-hint">Choose any day inside the week you are reporting on.</p>
                                </div>
                            </div>
                            <div class="wr-week">
                                <div>
                                    <label class="form-label" for="week_start">Any day in the week <span class="sb-req" aria-hidden="true">*</span></label>
                                    <input type="date" id="week_start" name="week_start" value="{{ old('week_start', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="form-control @error('week_start') is-invalid @enderror" required>
                                    @error('week_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <p class="wr-note"><i class="bi bi-info-circle" aria-hidden="true"></i><span>The daily activity table (dates, activities, hours) is filled in automatically from your time records for that week.</span></p>
                            </div>
                        </section>

                        @php
                            $narrativeFields = [
                                'challenges' => ['Difficulties encountered', 'What was hard or got in the way this week?'],
                                'actions_taken' => ['Actions taken', 'What did you do to deal with those difficulties?'],
                                'learnings' => ['Learning insights', 'What did you learn, and how will you use it?'],
                            ];
                        @endphp
                        <section class="wr-section" aria-labelledby="wr-narrative-title">
                            <div class="wr-section-head">
                                <span class="wr-step" aria-hidden="true">3</span>
                                <div>
                                    <h3 class="wr-section-title" id="wr-narrative-title">Your narrative</h3>
                                    <p class="wr-section-hint">Write at least 20 characters in each part.</p>
                                </div>
                            </div>
                            @foreach($narrativeFields as $name => [$label, $help])
                                <div class="wr-narrative">
                                    <label for="{{ $name }}">{{ $label }} <span class="sb-req" aria-hidden="true">*</span></label>
                                    <p class="wr-help" id="{{ $name }}-help">{{ $help }}</p>
                                    <textarea id="{{ $name }}" name="{{ $name }}" rows="3" minlength="20" maxlength="5000" aria-describedby="{{ $name }}-help {{ $name }}-count" data-wr-count class="form-control @error($name) is-invalid @enderror" required>{{ old($name) }}</textarea>
                                    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <div class="wr-counter" id="{{ $name }}-count" data-wr-counter aria-live="polite">0 / 20 minimum</div>
                                </div>
                            @endforeach
                        </section>
                    </form>
                </div>
                <div class="modal-footer">
                    <span class="wr-footer-note"><span class="sb-req">*</span> Required</span>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" form="weeklyJournalForm">Submit report</button>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/ojt-weekly-report.css') }}?v={{ @filemtime(public_path('css/ojt-weekly-report.css')) }}">
    @endpush
    @push('scripts')
        <script src="{{ asset('js/sb-modals.js') }}?v={{ @filemtime(public_path('js/sb-modals.js')) }}"></script>
        <script src="{{ asset('js/ojt-report-header.js') }}?v={{ @filemtime(public_path('js/ojt-report-header.js')) }}"></script>
    @endpush
    @endif
@endif
@endsection
