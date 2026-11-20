@extends('layouts.app')

@section('title', 'Evaluation Reviews')

@section('content')
<link rel="stylesheet" href="{{ asset('css/coordinator-review-cards.css') }}?v={{ @filemtime(public_path('css/coordinator-review-cards.css')) }}">
<div class="sb-dash">
<x-page-header title="Evaluation Reviews" subtitle="Your second review after the company supervisor: daily logs, weekly reports, monthly and final evaluations." />

<nav class="sb-section-tabs mb-0" aria-label="Review lists">
    @foreach(['logs' => ['Daily logs', 'logs'], 'reports' => ['Weekly reports', 'reports'], 'monthly' => ['Monthly evaluation', 'evaluations'], 'final' => ['Final evaluation', 'evaluations']] as $key => [$label, $countKey])
        @php $active = in_array($key, ['monthly', 'final'], true) ? ($tab === 'evaluations' && $progressType === ($key === 'final' ? 'final' : 'monthly')) : $tab === $key; @endphp
        <a class="sb-section-tab {{ $active ? 'active' : '' }}" href="{{ route('coordinator.reviews.index', ['tab' => $key]) }}" @if($active) aria-current="page" @endif>
            {{ $label }} @if($key !== 'monthly' && $counts[$countKey])<span class="sb-tab-count">{{ $counts[$countKey] }}</span>@endif
        </a>
    @endforeach
</nav>

@if($tab === 'logs')
    <form method="GET" class="row g-2 align-items-end my-3">
        <input type="hidden" name="tab" value="logs">
        <div class="col-6 col-md-3"><label class="form-label small mb-1" for="log-status">Status</label>
            <select id="log-status" name="log_status" class="form-select form-select-sm">
                <option value="">All</option>
                @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)<option value="{{ $value }}" @selected($logStatus === $value)>{{ $label }}</option>@endforeach
            </select></div>
        <div class="col-6 col-md-3"><label class="form-label small mb-1" for="log-from">From</label><input id="log-from" type="date" name="from" value="{{ $from }}" class="form-control form-control-sm"></div>
        <div class="col-6 col-md-3"><label class="form-label small mb-1" for="log-to">To</label><input id="log-to" type="date" name="to" value="{{ $to }}" class="form-control form-control-sm"></div>
        <div class="col-6 col-md-3 d-flex gap-2"><button class="btn btn-sm btn-primary">Filter</button><a class="btn btn-sm btn-outline-secondary" href="{{ route('coordinator.reviews.index', ['tab' => 'logs']) }}">Reset</a></div>
    </form>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Student</th><th>Company</th><th>Date</th><th>Hours</th><th>Supervisor decision</th></tr></thead>
                <tbody>
                    @forelse($items as $log)
                        <tr>
                            <td><a href="{{ route('coordinator.placements.show', ['placement' => $log->placement, 'back' => request()->getRequestUri()]) }}">{{ $log->placement?->student?->user?->name }}</a></td>
                            <td>{{ $log->placement?->employer?->company_name }}</td>
                            <td>{{ $log->work_date?->format('M d, Y') }}</td>
                            <td>{{ number_format((float) $log->hours, 2) }}</td>
                            <td>
                                <x-status-badge :status="$log->status" :label="ucfirst($log->status)" />
                                @if($log->reviewer_remarks)<span class="sb-row-sub">{{ $log->reviewer_remarks }}</span>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="sb-table-empty text-center text-muted">No daily logs match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())<div class="card-footer">{{ $items->links() }}</div>@endif
    </div>
@else
    @if($tab === 'reports')
        <nav class="sb-section-tabs my-3" aria-label="Report status">
            @foreach($reportFilters as $key => $label)
                <a class="sb-section-tab {{ $show === $key ? 'active' : '' }}" href="{{ route('coordinator.reviews.index', ['tab' => 'reports', 'show' => $key]) }}" @if($show === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
    @endif
    @if($tab === 'evaluations')
        @include('coordinator.reviews.partials.evaluation-progress')
        @if($items->total() > 0)<h2 class="h5 mb-3" id="final-reviews">Final evaluations waiting for your review</h2>@endif
    @endif
    @forelse($items as $item)
        @php
            $isReport = $tab === 'reports';
            $placement = $item->placement;
            // Only items still waiting on this coordinator get the Return / Mark reviewed form; the rest are read-only.
            $needsReview = $item->coordinator_review_status === null && (! $isReport || $item->status === \App\Models\JournalEntry::STATUS_ACKNOWLEDGED);
        @endphp
        <article class="card rv-card" data-review-item aria-labelledby="rv-title-{{ $item->id }}">
            <header class="rv-head">
                <div class="rv-head__main">
                    <h2 class="rv-title" id="rv-title-{{ $item->id }}">{{ $isReport ? $item->title : 'Evaluation of '.($placement?->student?->user?->name ?? 'intern') }}</h2>
                    <p class="rv-meta">
                        <a href="{{ route('coordinator.placements.show', ['placement' => $placement, 'back' => request()->getRequestUri()]) }}">{{ $placement?->student?->user?->name }}</a>
                        <span class="rv-dot" aria-hidden="true">·</span> {{ $placement?->employer?->company_name }}
                        <span class="rv-dot" aria-hidden="true">·</span> {{ $isReport ? 'Week of '.$item->week_start?->format('M d, Y') : 'Submitted '.$item->submitted_at?->format('M d, Y') }}
                    </p>
                </div>
                <div class="rv-head__side">
                    @if($needsReview)
                        <span class="sb-status sb-status--warning"><i class="bi bi-hourglass-split" aria-hidden="true"></i> Needs your review</span>
                    @elseif($item->coordinator_review_status === 'reviewed')
                        <span class="sb-status sb-status--success"><i class="bi bi-check2-circle" aria-hidden="true"></i> Reviewed</span>
                    @elseif($item->coordinator_review_status === 'returned')
                        <span class="sb-status sb-status--danger"><i class="bi bi-arrow-return-left" aria-hidden="true"></i> Returned</span>
                    @else
                        <span class="sb-status sb-status--neutral"><i class="bi bi-hourglass-split" aria-hidden="true"></i> Waiting for supervisor</span>
                    @endif
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
                    @if($item->coordinator_comment)<div class="rv-row"><dt>Your comment:</dt><dd>{{ $item->coordinator_comment }}</dd></div>@endif
                @else
                    @if($item->strengths)<div class="rv-row"><dt>Strengths:</dt><dd>{{ $item->strengths }}</dd></div>@endif
                    @if($item->improvements)<div class="rv-row"><dt>To improve:</dt><dd>{{ $item->improvements }}</dd></div>@endif
                    @if($item->comments)<div class="rv-row"><dt>Comments:</dt><dd>{{ $item->comments }}</dd></div>@endif
                @endif
            </dl>
            @if($needsReview)
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
            @endif
        </article>
    @empty
        {{-- On the evaluations tab the progress list above is the main content, so the empty note stays small. --}}
        @if($tab === 'evaluations')
            <p class="text-muted small mb-0">Nothing waiting for your review.</p>
        @else
            <div class="card"><x-empty-state icon="bi-check2-circle" title="{{ $tab === 'reports' && $show !== 'waiting' ? 'No reports here.' : 'Nothing waiting for your review.' }}" /></div>
        @endif
    @endforelse
    @if($items->hasPages())<div class="sb-pages">{{ $items->links() }}</div>@endif
@endif
</div>
@endsection
