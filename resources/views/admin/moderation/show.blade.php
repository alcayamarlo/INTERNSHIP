@extends('layouts.app')

@section('title', 'Review report')

@section('content')
@php($labels = \App\Models\ContentReport::class)
<x-page-header :title="'Report: '.($labels::TYPE_LABELS[$report->reportable_type] ?? 'Item')" :subtitle="$summary">
    <a href="{{ route('admin.moderation.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> All reports</a>
</x-page-header>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header"><h2>What was reported</h2></div>
            <div class="card-body">
                @if(! $target)
                    <p class="text-muted mb-0">This item no longer exists.</p>
                @elseif($report->reportable_type === 'internship')
                    <h3 class="h6">{{ $target->title }}</h3>
                    <p class="small text-muted">{{ $target->employer?->company_name }} · {{ ucfirst($target->status) }}@if($target->hidden_at) · <strong class="text-danger">Hidden</strong>@endif</p>
                    <p class="small mb-0">{{ \Illuminate\Support\Str::limit($target->description, 800) }}</p>
                @elseif($report->reportable_type === 'company')
                    <h3 class="h6">{{ $target->company_name }} <x-verified-badge :employer="$target" /></h3>
                    <p class="small text-muted">{{ $target->industry }} · Contact: {{ $target->contact_person }}</p>
                    <p class="small mb-0">{{ \Illuminate\Support\Str::limit($target->description, 800) }}</p>
                @elseif($report->reportable_type === 'message')
                    <p class="small text-muted">From {{ $target->sender?->name }} to {{ $target->receiver?->name }} · {{ $target->created_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}@if($target->isHidden()) · <strong class="text-danger">Hidden</strong>@endif</p>
                    <blockquote class="border-start border-3 ps-3 mb-0">{{ $target->body }}</blockquote>
                @else
                    <h3 class="h6">{{ $target->name }}</h3>
                    <p class="small text-muted mb-0">{{ $target->role?->label() }} · {{ $target->email }}@if($target->isSuspended()) · <strong class="text-danger">Suspended</strong>@endif</p>
                @endif
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h2>Report</h2></div>
            <div class="card-body small">
                <p class="mb-1"><strong>Reason:</strong> {{ $labels::REASONS[$report->reason] ?? $report->reason }}</p>
                <p class="mb-1"><strong>Reported by:</strong> {{ $report->reporter?->name ?? 'Deleted user' }} · {{ $report->created_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}</p>
                @if($report->details)<p class="mb-0"><strong>Details:</strong> {{ $report->details }}</p>@endif
                @if($owner)<p class="mb-0 mt-2"><strong>Owner of this content:</strong> {{ $owner->name }} ({{ $owner->role?->label() }})</p>@endif
            </div>
        </div>

        @if($related->isNotEmpty())
            <div class="card">
                <div class="card-header"><h2>Other reports on this item ({{ $related->count() }})</h2></div>
                <ul class="list-group list-group-flush small">
                    @foreach($related as $other)
                        <li class="list-group-item">{{ $labels::REASONS[$other->reason] ?? $other->reason }} · {{ $other->reporter?->name ?? 'Deleted user' }} · {{ $other->created_at->timezone('Asia/Manila')->format('M d, Y') }} · {{ ucfirst($other->status) }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <div class="col-lg-5">
        @if($report->isOpen())
            <form method="POST" action="{{ route('admin.moderation.resolve', $report) }}" class="card" data-moderation-form>
                @csrf
                <div class="card-header"><h2>Decide</h2></div>
                <div class="card-body">
                    @error('resolution')<div class="alert alert-danger small">{{ $message }}</div>@enderror
                    <fieldset class="mb-3">
                        <legend class="form-label fs-6">Outcome</legend>
                        @foreach($labels::RESOLUTIONS as $key => $label)
                            @continue($key === 'hide' && ! $canHide)
                            @continue(in_array($key, ['warn', 'suspend'], true) && ! $owner)
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="resolution" value="{{ $key }}" id="resolution{{ $key }}" @checked(old('resolution', 'dismiss') === $key) required>
                                <label class="form-check-label" for="resolution{{ $key }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </fieldset>
                    <label class="form-label" for="moderationWarning">Warning to the owner <span class="text-muted small">(for "Warn")</span></label>
                    <textarea id="moderationWarning" name="warning" rows="2" maxlength="1000" class="form-control mb-2 @error('warning') is-invalid @enderror">{{ old('warning') }}</textarea>
                    @error('warning')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <label class="form-label" for="moderationSuspendReason">Suspension reason <span class="text-muted small">(for "Suspend"; shown at sign-in)</span></label>
                    <textarea id="moderationSuspendReason" name="suspension_reason" rows="2" maxlength="1000" class="form-control mb-2 @error('suspension_reason') is-invalid @enderror">{{ old('suspension_reason') }}</textarea>
                    @error('suspension_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <label class="form-label" for="moderationSuspendUntil">Suspended until <span class="text-muted small">(optional, Philippine time)</span></label>
                    <input id="moderationSuspendUntil" type="datetime-local" name="suspended_until" class="form-control mb-2" value="{{ old('suspended_until') }}" min="{{ now('Asia/Manila')->format('Y-m-d\TH:i') }}">
                    <label class="form-label" for="moderationNotes">Internal notes (optional)</label>
                    <textarea id="moderationNotes" name="admin_notes" rows="2" maxlength="2000" class="form-control">{{ old('admin_notes') }}</textarea>
                    <p class="small text-muted mt-2 mb-0">Resolving closes every open report on this item. Reporters are told it was reviewed, not what was decided.</p>
                </div>
                <div class="card-footer text-end"><button type="submit" class="btn btn-primary">Resolve report</button></div>
            </form>
        @else
            <div class="card">
                <div class="card-header"><h2>Outcome</h2></div>
                <div class="card-body small">
                    <p class="mb-1"><strong>{{ $labels::RESOLUTIONS[$report->resolution] ?? $report->resolution }}</strong></p>
                    <p class="mb-1">By {{ $report->resolvedBy?->name ?? 'an administrator' }} · {{ $report->resolved_at?->timezone('Asia/Manila')->format('M d, Y h:i A') }}</p>
                    @if($report->admin_notes)<p class="mb-0">Notes: {{ $report->admin_notes }}</p>@endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
