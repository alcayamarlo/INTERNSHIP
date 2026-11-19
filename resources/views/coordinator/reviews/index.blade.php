@extends('layouts.app')

@section('title', 'Evaluation Reports')

@section('content')
<link rel="stylesheet" href="{{ asset('css/coordinator-review-cards.css') }}?v={{ @filemtime(public_path('css/coordinator-review-cards.css')) }}">
<div class="sb-dash">
<x-page-header title="Evaluation reports" subtitle="Your second review after the company supervisor: weekly reports, evaluations, and recent attendance." />

<nav class="sb-section-tabs mb-0" aria-label="Review lists">
    @foreach(['reports' => 'Weekly reports', 'evaluations' => 'Evaluations', 'attendance' => 'Attendance (last 14 days)'] as $key => $label)
        <a class="sb-section-tab {{ $tab === $key ? 'active' : '' }}" href="{{ route('coordinator.reviews.index', ['tab' => $key]) }}" @if($tab === $key) aria-current="page" @endif>
            {{ $label }} @if($counts[$key])<span class="sb-tab-count">{{ $counts[$key] }}</span>@endif
        </a>
    @endforeach
</nav>

@if($tab === 'attendance')
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Student</th><th>Company</th><th>Date</th><th>Hours</th><th>Supervisor decision</th></tr></thead>
                <tbody>
                    @forelse($items as $log)
                        <tr>
                            <td><a href="{{ route('coordinator.placements.show', $log->placement) }}">{{ $log->placement?->student?->user?->name }}</a></td>
                            <td>{{ $log->placement?->employer?->company_name }}</td>
                            <td>{{ $log->work_date?->format('M d, Y') }}</td>
                            <td>{{ number_format((float) $log->hours, 2) }}</td>
                            <td>
                                <x-status-badge :status="$log->status" :label="ucfirst($log->status)" />
                                @if($log->reviewer_remarks)<span class="sb-row-sub">{{ $log->reviewer_remarks }}</span>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="sb-table-empty text-center text-muted">No time entries in the last 14 days.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())<div class="card-footer">{{ $items->links() }}</div>@endif
    </div>
@else
    @if($tab === 'evaluations')
        @include('coordinator.reviews.partials.evaluation-progress')
        @if($items->total() > 0)<h2 class="h5 mb-3" id="final-reviews">Final evaluations waiting for your review</h2>@endif
    @endif
    @forelse($items as $item)
        @php
            $isReport = $tab === 'reports';
            $placement = $item->placement;
        @endphp
        <article class="card rv-card" data-review-item aria-labelledby="rv-title-{{ $item->id }}">
            <header class="rv-head">
                <div class="rv-head__main">
                    <h2 class="rv-title" id="rv-title-{{ $item->id }}">{{ $isReport ? $item->title : 'Evaluation of '.($placement?->student?->user?->name ?? 'intern') }}</h2>
                    <p class="rv-meta">
                        <a href="{{ route('coordinator.placements.show', $placement) }}">{{ $placement?->student?->user?->name }}</a>
                        <span class="rv-dot" aria-hidden="true">·</span> {{ $placement?->employer?->company_name }}
                        <span class="rv-dot" aria-hidden="true">·</span> {{ $isReport ? 'Week of '.$item->week_start?->format('M d, Y') : 'Submitted '.$item->submitted_at?->format('M d, Y') }}
                    </p>
                </div>
                <div class="rv-head__side">
                    <span class="sb-status sb-status--warning"><i class="bi bi-hourglass-split" aria-hidden="true"></i> Needs your review</span>
                    @unless($isReport)<span class="sb-status sb-status--neutral rv-score">Average {{ number_format((float) $item->average_score, 2) }} / 5</span>@endunless
                </div>
            </header>
            <dl class="rv-body">
                @if($isReport)
                    {{-- Weekly narrative report sections; older reports only have accomplishments. --}}
                    @foreach(['accomplishments' => 'Accomplishments', 'challenges' => 'Difficulties encountered', 'actions_taken' => 'Actions taken', 'learnings' => 'Learning insights'] as $field => $label)
                        @if(filled($item->{$field}))<div class="rv-row"><dt>{{ $label }}:</dt><dd>{{ \Illuminate\Support\Str::limit($item->{$field}, 400) }}</dd></div>@endif
                    @endforeach
                    @if($item->supervisor_comment)<div class="rv-row"><dt>Supervisor:</dt><dd>{{ $item->supervisor_comment }}</dd></div>@endif
                @else
                    @if($item->strengths)<div class="rv-row"><dt>Strengths:</dt><dd>{{ $item->strengths }}</dd></div>@endif
                    @if($item->improvements)<div class="rv-row"><dt>To improve:</dt><dd>{{ $item->improvements }}</dd></div>@endif
                    @if($item->comments)<div class="rv-row"><dt>Comments:</dt><dd>{{ $item->comments }}</dd></div>@endif
                @endif
            </dl>
            @php($reviewUrl = $isReport ? route('coordinator.reviews.journals', $item) : route('coordinator.reviews.evaluations', $item))
            {{-- One form per decision: the decision travels in a hidden field, not on the (auto-disabled) button. --}}
            <footer class="rv-foot">
                <form method="POST" action="{{ $reviewUrl }}" class="rv-return">
                    @csrf
                    <input type="hidden" name="decision" value="returned">
                    <label class="rv-label" for="comment{{ $item->id }}">Comment <span>(required to return)</span></label>
                    <div class="rv-return__row">
                        <input id="comment{{ $item->id }}" type="text" name="comment" maxlength="1000" class="form-control" placeholder="Write a reason if you are returning this" required>
                        <button type="submit" class="btn btn-outline-danger rv-btn"><i class="bi bi-arrow-return-left" aria-hidden="true"></i> Return</button>
                    </div>
                </form>
                <form method="POST" action="{{ $reviewUrl }}" class="rv-approve">
                    @csrf
                    <input type="hidden" name="decision" value="reviewed">
                    <button type="submit" class="btn btn-success rv-btn"><i class="bi bi-check2-circle" aria-hidden="true"></i> Mark reviewed</button>
                </form>
            </footer>
        </article>
    @empty
        {{-- On the evaluations tab the progress list above is the main content, so the empty note stays small. --}}
        @if($tab === 'evaluations')
            <p class="text-muted small mb-0">Nothing waiting for your review.</p>
        @else
            <div class="card"><x-empty-state icon="bi-check2-circle" title="Nothing waiting for your review." /></div>
        @endif
    @endforelse
    @if($items->hasPages())<div class="sb-pages">{{ $items->links() }}</div>@endif
@endif
</div>
@endsection
