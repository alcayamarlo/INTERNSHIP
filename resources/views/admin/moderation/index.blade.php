@extends('layouts.app')

@section('title', 'Reported Content')

@section('content')
<div class="sb-dash">
<x-page-header title="Reported content" subtitle="Postings, companies, messages, and users that people reported. Only administrators see this." />

<nav class="sb-section-tabs mb-0" aria-label="Report status">
    <a class="sb-section-tab {{ $status === 'open' ? 'active' : '' }}" href="{{ route('admin.moderation.index', array_filter(['type' => $type])) }}" @if($status === 'open') aria-current="page" @endif>Open @if($openCount)<span class="sb-tab-count">{{ $openCount }}</span>@endif</a>
    <a class="sb-section-tab {{ $status === 'resolved' ? 'active' : '' }}" href="{{ route('admin.moderation.index', array_filter(['status' => 'resolved', 'type' => $type])) }}" @if($status === 'resolved') aria-current="page" @endif>Resolved</a>
</nav>

<div class="sb-pill-tabs" role="group" aria-label="Filter by type">
    <a href="{{ route('admin.moderation.index', array_filter(['status' => $status === 'resolved' ? 'resolved' : null])) }}" class="sb-pill-tab {{ $type ? '' : 'is-active' }}" @unless($type) aria-current="true" @endunless>All types</a>
    @foreach(\App\Models\ContentReport::TYPE_LABELS as $key => $label)
        <a href="{{ route('admin.moderation.index', array_filter(['status' => $status === 'resolved' ? 'resolved' : null, 'type' => $key])) }}" class="sb-pill-tab {{ $type === $key ? 'is-active' : '' }}" @if($type === $key) aria-current="true" @endif>{{ $label }}</a>
    @endforeach
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 sb-table-stack">
            <thead><tr><th>Reported</th><th>Item</th><th>Reason</th><th>Reported by</th><th>{{ $status === 'open' ? 'Received' : 'Outcome' }}</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
                @forelse($reports as $report)
                    <tr>
                        <td data-label="Reported"><span class="sb-tag">{{ \App\Models\ContentReport::TYPE_LABELS[$report->reportable_type] ?? $report->reportable_type }}</span></td>
                        <td class="sb-stack-block text-break"><span class="sb-row-title">{{ $report->summary }}</span></td>
                        <td data-label="Reason">{{ \App\Models\ContentReport::REASONS[$report->reason] ?? $report->reason }}</td>
                        <td data-label="Reported by">{{ $report->reporter?->name ?? 'Deleted user' }}</td>
                        <td data-label="{{ $status === 'open' ? 'Received' : 'Outcome' }}" class="sb-stack-block">
                            @if($report->isOpen())
                                {{ $report->created_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}
                            @else
                                {{ \App\Models\ContentReport::RESOLUTIONS[$report->resolution] ?? $report->resolution }}
                                <small class="sb-row-sub">{{ $report->resolved_at?->timezone('Asia/Manila')->format('M d, Y') }}</small>
                            @endif
                        </td>
                        <td class="text-end sb-stack-block"><a href="{{ route('admin.moderation.show', $report) }}" class="btn btn-sm btn-outline-primary">{{ $report->isOpen() ? 'Review' : 'View' }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="sb-table-empty sb-stack-block text-center text-muted">{{ $status === 'open' ? 'No open reports. All clear.' : 'No resolved reports yet.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($reports->hasPages())<div class="card-footer">{{ $reports->links() }}</div>@endif
</div>
</div>
@endsection
