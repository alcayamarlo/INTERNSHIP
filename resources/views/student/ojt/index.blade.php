@extends('layouts.app')

@section('title', 'My OJT')

@push('styles')
<style>
    .ojt-anchor {
        scroll-margin-top: 96px;
    }
</style>
@endpush

@section('content')
<x-page-header title="My OJT" subtitle="Log your daily time, submit weekly journals and track your progress toward completion.">
    @if($placement)
        <nav class="d-flex flex-wrap gap-2" aria-label="Jump to section">
            <a href="#ojt-progress" class="btn btn-outline-secondary btn-sm"><i class="bi bi-speedometer2" aria-hidden="true"></i> Progress</a>
            <a href="#ojt-time-record" class="btn btn-outline-secondary btn-sm"><i class="bi bi-clock-history" aria-hidden="true"></i> Time Record</a>
            <a href="#ojt-journal" class="btn btn-outline-secondary btn-sm"><i class="bi bi-journal-text" aria-hidden="true"></i> Weekly Journal</a>
            <a href="#ojt-evaluation" class="btn btn-outline-secondary btn-sm"><i class="bi bi-clipboard-check" aria-hidden="true"></i> Evaluation</a>
        </nav>
    @endif
</x-page-header>

@if(! $placement)
    <div class="card">
        <div class="card-body">
            <x-empty-state icon="bi-clock-history" title="No OJT placement yet" message="Your OJT record opens automatically once an employer accepts one of your applications.">
                <a href="{{ route('student.internships.index') }}" class="btn btn-primary">Browse internships</a>
            </x-empty-state>
        </div>
    </div>
@else
    @if($placements->count() > 1)
        <form method="GET" class="mb-3 d-flex gap-2 align-items-center">
            <label for="placementSelect" class="form-label mb-0 small">Placement</label>
            <select id="placementSelect" name="placement" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                @foreach($placements as $option)
                    <option value="{{ $option->id }}" @selected($option->id === $placement->id)>{{ $option->internship?->title }} — {{ $option->employer?->company_name }} ({{ $option->statusLabel() }})</option>
                @endforeach
            </select>
        </form>
    @endif

    <div id="ojt-progress" class="ojt-anchor">
        @include('ojt.partials.progress', ['placement' => $placement])
    </div>

    @if($placement->isActive() && $placement->start_date->isFuture())
        <div class="alert alert-info">
            <i class="bi bi-calendar-event" aria-hidden="true"></i>
            Your OJT starts on {{ $placement->start_date->format('F d, Y') }}. You can log time and submit journals from that date.
        </div>
    @elseif($placement->isActive())
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><h2 class="h6 mb-0"><i class="bi bi-plus-circle" aria-hidden="true"></i> Log today's time</h2></div>
                    <div class="card-body d-flex flex-column align-items-start gap-3">
                        <p class="text-muted mb-0">Record the hours you worked and the tasks you completed today.</p>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#dailyEntryModal"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add daily entry</button>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><h2 class="h6 mb-0"><i class="bi bi-pencil-square" aria-hidden="true"></i> Weekly journal</h2></div>
                    <div class="card-body d-flex flex-column align-items-start gap-3">
                        <p class="text-muted mb-0">Summarise your accomplishments, learnings and challenges for the week.</p>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#weeklyJournalModal"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add weekly journal</button>
                    </div>
                </div>
            </div>
        </div>

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
        <script src="{{ asset('js/sb-modals.js') }}"></script>
    @endpush
    @endif

    <div id="ojt-time-record" class="ojt-anchor">
        @include('ojt.partials.attendance', ['placement' => $placement, 'mode' => 'student'])
    </div>
    <div id="ojt-journal" class="ojt-anchor">
        @include('ojt.partials.journals', ['placement' => $placement, 'mode' => 'student'])
    </div>
    <div id="ojt-evaluation" class="ojt-anchor">
        @include('ojt.partials.evaluation', ['placement' => $placement])
    </div>
@endif
@endsection
