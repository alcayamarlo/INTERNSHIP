@extends('layouts.app')

@section('title', 'Report Reviews')

@section('content')
<x-page-header title="Report reviews" subtitle="Your second review after the company supervisor: weekly reports, evaluations, and recent attendance." />

<ul class="nav nav-tabs mb-3" role="tablist">
    @foreach(['reports' => 'Weekly reports', 'evaluations' => 'Evaluations', 'attendance' => 'Attendance (last 14 days)'] as $key => $label)
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ route('coordinator.reviews.index', ['tab' => $key]) }}" @if($tab === $key) aria-current="page" @endif>
                {{ $label }} @if($counts[$key])<span class="badge text-bg-danger ms-1">{{ $counts[$key] }}</span>@endif
            </a>
        </li>
    @endforeach
</ul>

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
                                <span class="badge {{ $log->status === 'approved' ? 'text-bg-success' : ($log->status === 'rejected' ? 'text-bg-danger' : 'text-bg-warning') }}">{{ ucfirst($log->status) }}</span>
                                @if($log->reviewer_remarks)<small class="d-block text-muted">{{ $log->reviewer_remarks }}</small>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No time entries in the last 14 days.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())<div class="card-footer">{{ $items->links() }}</div>@endif
    </div>
@else
    @forelse($items as $item)
        @php
            $isReport = $tab === 'reports';
            $placement = $item->placement;
        @endphp
        <article class="card mb-3" data-review-item>
            <div class="card-body">
                <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                    <div>
                        <h2 class="h6 mb-0">{{ $isReport ? $item->title : 'Evaluation of '.($placement?->student?->user?->name ?? 'intern') }}</h2>
                        <small class="text-muted">
                            <a href="{{ route('coordinator.placements.show', $placement) }}">{{ $placement?->student?->user?->name }}</a> · {{ $placement?->employer?->company_name }}
                            · {{ $isReport ? 'Week of '.$item->week_start?->format('M d, Y') : 'Submitted '.$item->submitted_at?->format('M d, Y') }}
                        </small>
                    </div>
                    @unless($isReport)<span class="badge text-bg-light border">Average {{ number_format((float) $item->average_score, 2) }} / 5</span>@endunless
                </div>
                @if($isReport)
                    <p class="small mb-1"><strong>Accomplishments:</strong> {{ \Illuminate\Support\Str::limit($item->accomplishments, 400) }}</p>
                    @if($item->supervisor_comment)<p class="small mb-2"><strong>Supervisor:</strong> {{ $item->supervisor_comment }}</p>@endif
                @else
                    @if($item->strengths)<p class="small mb-1"><strong>Strengths:</strong> {{ $item->strengths }}</p>@endif
                    @if($item->improvements)<p class="small mb-1"><strong>To improve:</strong> {{ $item->improvements }}</p>@endif
                    @if($item->comments)<p class="small mb-2"><strong>Comments:</strong> {{ $item->comments }}</p>@endif
                @endif
                @php($reviewUrl = $isReport ? route('coordinator.reviews.journals', $item) : route('coordinator.reviews.evaluations', $item))
                {{-- One form per decision: the decision travels in a hidden field, not on the (auto-disabled) button. --}}
                <div class="row g-2 align-items-end">
                    <form method="POST" action="{{ $reviewUrl }}" class="col-md-9 row g-2 align-items-end m-0 p-0">
                        @csrf
                        <input type="hidden" name="decision" value="returned">
                        <div class="col">
                            <label class="form-label small" for="comment{{ $item->id }}">Comment <span class="text-muted">(required to return)</span></label>
                            <input id="comment{{ $item->id }}" type="text" name="comment" maxlength="1000" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-auto"><button type="submit" class="btn btn-sm btn-outline-danger">Return</button></div>
                    </form>
                    <form method="POST" action="{{ $reviewUrl }}" class="col-md-3 m-0">
                        @csrf
                        <input type="hidden" name="decision" value="reviewed">
                        <button type="submit" class="btn btn-sm btn-success w-100">Mark reviewed</button>
                    </form>
                </div>
            </div>
        </article>
    @empty
        <div class="card"><div class="card-body text-center text-muted py-5">Nothing waiting for your review.</div></div>
    @endforelse
    @if($items->hasPages())<div class="mt-3">{{ $items->links() }}</div>@endif
@endif
@endsection
