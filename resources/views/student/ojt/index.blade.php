@extends('layouts.app')

@section('title', 'My OJT')

@push('styles')
<style>
    .ojt-tabs {
        position: sticky;
        top: var(--sb-topbar-height, 64px);
        z-index: 1020;
        margin: -8px -4px 20px;
        padding: 10px 4px;
        overflow-x: auto;
        white-space: nowrap;
        background: var(--sb-canvas, var(--sb-surface));
        border-bottom: 1px solid var(--sb-border);
    }
    /* All three tabs share one size, padding, font, radius, and icon placement. */
    .ojt-tabs .ojt-tab {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 999px;
        font-size: 14px;
        font-weight: 600;
        line-height: 1.4;
        background: var(--sb-surface, #fff);
        border: 1px solid var(--sb-border, #dee2e6);
        color: var(--sb-text, #212529);
        transition: background-color .18s ease, border-color .18s ease, color .18s ease, transform .1s ease;
    }
    .ojt-tabs .ojt-tab:hover { border-color: var(--sb-brand-content, #8f1a27); color: var(--sb-brand-content, #8f1a27); }
    .ojt-tabs .ojt-tab:active { transform: scale(.97); }
    .ojt-tabs .ojt-tab:focus-visible { outline: 2px solid var(--sb-brand-content, #8f1a27); outline-offset: 2px; }
    .ojt-tabs .ojt-tab[aria-selected="true"] {
        background: var(--sb-brand-content, #8f1a27);
        border-color: var(--sb-brand-content, #8f1a27);
        color: #fff;
    }
    .ojt-tabs .ojt-tab[aria-selected="true"]:hover { filter: brightness(1.08); }
    .ojt-panel[hidden], .ojt-tabs [hidden] { display: none !important; }
    .ojt-panel { animation: ojt-fade .18s ease; }
    @keyframes ojt-fade { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }
    @media (prefers-reduced-motion: reduce) { .ojt-panel { animation: none; } .ojt-tabs .ojt-tab { transition: none; } }
</style>
@endpush

@section('content')
<x-page-header title="My OJT" subtitle="Log your daily time, submit weekly journals and track your progress toward completion." />
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
            'weekly-journal' => ['Weekly Journal', 'bi-journal-text'],
        ];
        $activeTab = array_key_exists((string) request('tab'), $tabs) ? request('tab') : 'progress';
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

    <div class="ojt-tabs d-flex align-items-center justify-content-between gap-2">
        <div class="d-flex gap-2" role="tablist" aria-label="OJT sections" data-ojt-tabs>
            @foreach($tabs as $key => [$label, $icon])
                <button type="button" role="tab" id="ojt-tab-{{ $key }}" class="ojt-tab" data-tab="{{ $key }}"
                        aria-controls="ojt-panel-{{ $key }}" aria-selected="{{ $key === $activeTab ? 'true' : 'false' }}"
                        tabindex="{{ $key === $activeTab ? '0' : '-1' }}">
                    <i class="bi {{ $icon }}" aria-hidden="true"></i> {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Action for the open tab, on the right of the tab bar. Progress has none. --}}
        @if($canLog)
            <button type="button" class="btn btn-primary btn-sm flex-shrink-0" data-ojt-action="time-record" data-bs-toggle="modal" data-bs-target="#dailyEntryModal" @if($activeTab !== 'time-record') hidden @endif><i class="bi bi-plus-lg" aria-hidden="true"></i> Add daily entry</button>
            <button type="button" class="btn btn-primary btn-sm flex-shrink-0" data-ojt-action="weekly-journal" data-bs-toggle="modal" data-bs-target="#weeklyJournalModal" @if($activeTab !== 'weekly-journal') hidden @endif><i class="bi bi-plus-lg" aria-hidden="true"></i> Add weekly journal</button>
        @endif
    </div>

    {{-- PROGRESS --}}
    <section id="ojt-panel-progress" class="ojt-panel" role="tabpanel" aria-labelledby="ojt-tab-progress" @if($activeTab !== 'progress') hidden @endif>
        @include('ojt.partials.progress', ['placement' => $placement])
    </section>

    {{-- TIME RECORD --}}
    <section id="ojt-panel-time-record" class="ojt-panel" role="tabpanel" aria-labelledby="ojt-tab-time-record" @if($activeTab !== 'time-record') hidden @endif>
        @if($placement->isActive() && $placement->start_date->isFuture())
            <div class="alert alert-info">
                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                Your OJT starts on {{ $placement->start_date->format('F d, Y') }}. You can log time and submit journals from that date.
            </div>
        @endif
        @include('ojt.partials.attendance', ['placement' => $placement, 'mode' => 'student'])
    </section>

    {{-- WEEKLY JOURNAL --}}
    <section id="ojt-panel-weekly-journal" class="ojt-panel" role="tabpanel" aria-labelledby="ojt-tab-weekly-journal" @if($activeTab !== 'weekly-journal') hidden @endif>
        @if($placement->isActive() && $placement->start_date->isFuture())
            <div class="alert alert-info">
                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                Your OJT starts on {{ $placement->start_date->format('F d, Y') }}. You can log time and submit journals from that date.
            </div>
        @endif
        @include('ojt.partials.journals', ['placement' => $placement, 'mode' => 'student'])
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
                    <h2 class="modal-title h5" id="dailyEntryModalLabel">Add daily entry</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="dailyEntryForm" method="POST" action="{{ route('student.ojt.attendance.store', $placement) }}">
                            @csrf
<input type="hidden" name="_modal" value="daily">
                            <div class="row g-2">
                                <div class="col-12">
                                    <label class="form-label" for="work_date">Date</label>
                                    <input type="date" id="work_date" name="work_date" value="{{ old('work_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" min="{{ $placement->start_date->toDateString() }}" class="form-control @error('work_date') is-invalid @enderror" required>
                                    @error('work_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-4">
                                    <label class="form-label" for="time_in">Time in</label>
                                    <input type="time" id="time_in" name="time_in" value="{{ old('time_in', '08:00') }}" class="form-control @error('time_in') is-invalid @enderror" required>
                                    @error('time_in')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-4">
                                    <label class="form-label" for="time_out">Time out</label>
                                    <input type="time" id="time_out" name="time_out" value="{{ old('time_out', '17:00') }}" class="form-control @error('time_out') is-invalid @enderror" required>
                                    @error('time_out')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-4">
                                    <label class="form-label" for="break_minutes">Break (min)</label>
                                    <input type="number" id="break_minutes" name="break_minutes" min="0" max="240" value="{{ old('break_minutes', 60) }}" class="form-control @error('break_minutes') is-invalid @enderror">
                                    @error('break_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="task_summary">Tasks done</label>
                                    <textarea id="task_summary" name="task_summary" rows="3" maxlength="2000" class="form-control @error('task_summary') is-invalid @enderror" required>{{ old('task_summary') }}</textarea>
                                    @error('task_summary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            
                        </form>
                </div>
                <div class="modal-footer">
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
                    <h2 class="modal-title h5" id="weeklyJournalModalLabel">Add weekly journal</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="weeklyJournalForm" method="POST" action="{{ route('student.ojt.journals.store', $placement) }}">
                            @csrf
<input type="hidden" name="_modal" value="weekly">
                            <div class="row g-2">
                                <div class="col-md-5">
                                    <label class="form-label" for="week_start">Any day in the week</label>
                                    <input type="date" id="week_start" name="week_start" value="{{ old('week_start', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="form-control @error('week_start') is-invalid @enderror" required>
                                    @error('week_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label" for="title">Title</label>
                                    <input id="title" name="title" value="{{ old('title') }}" maxlength="255" class="form-control @error('title') is-invalid @enderror" required>
                                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="accomplishments">Accomplishments</label>
                                    <textarea id="accomplishments" name="accomplishments" rows="2" class="form-control @error('accomplishments') is-invalid @enderror" required>{{ old('accomplishments') }}</textarea>
                                    @error('accomplishments')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="learnings">Learnings</label>
                                    <textarea id="learnings" name="learnings" rows="2" class="form-control">{{ old('learnings') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="challenges">Challenges</label>
                                    <textarea id="challenges" name="challenges" rows="2" class="form-control">{{ old('challenges') }}</textarea>
                                </div>
                            </div>
                            
                        </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" form="weeklyJournalForm">Submit journal</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/sb-modals.js') }}?v={{ @filemtime(public_path('js/sb-modals.js')) }}"></script>
    @endpush
    @endif
@endif
@endsection
