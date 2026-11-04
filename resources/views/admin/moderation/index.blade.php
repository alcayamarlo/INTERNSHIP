@extends('layouts.app')

@section('title', 'Reported Content')

@section('content')
<x-page-header title="Reported content" subtitle="Postings, companies, messages, and users that people reported. Only administrators see this." />

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link {{ $status === 'open' ? 'active' : '' }}" href="{{ route('admin.moderation.index', array_filter(['type' => $type])) }}">Open @if($openCount)<span class="badge text-bg-danger ms-1">{{ $openCount }}</span>@endif</a></li>
    <li class="nav-item"><a class="nav-link {{ $status === 'resolved' ? 'active' : '' }}" href="{{ route('admin.moderation.index', array_filter(['status' => 'resolved', 'type' => $type])) }}">Resolved</a></li>
</ul>

<div class="d-flex flex-wrap gap-2 mb-3" role="group" aria-label="Filter by type">
    <a href="{{ route('admin.moderation.index', array_filter(['status' => $status === 'resolved' ? 'resolved' : null])) }}" class="btn btn-sm {{ $type ? 'btn-outline-primary' : 'btn-primary' }}">All types</a>
    @foreach(\App\Models\ContentReport::TYPE_LABELS as $key => $label)
        <a href="{{ route('admin.moderation.index', array_filter(['status' => $status === 'resolved' ? 'resolved' : null, 'type' => $key])) }}" class="btn btn-sm {{ $type === $key ? 'btn-primary' : 'btn-outline-primary' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Reported</th><th>Item</th><th>Reason</th><th>Reported by</th><th>{{ $status === 'open' ? 'Received' : 'Outcome' }}</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
                @forelse($reports as $report)
                    <tr>
                        <td><span class="badge text-bg-light border">{{ \App\Models\ContentReport::TYPE_LABELS[$report->reportable_type] ?? $report->reportable_type }}</span></td>
                        <td>{{ $report->summary }}</td>
                        <td>{{ \App\Models\ContentReport::REASONS[$report->reason] ?? $report->reason }}</td>
                        <td>{{ $report->reporter?->name ?? 'Deleted user' }}</td>
                        <td class="text-nowrap">
                            @if($report->isOpen())
                                {{ $report->created_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}
                            @else
                                {{ \App\Models\ContentReport::RESOLUTIONS[$report->resolution] ?? $report->resolution }}
                                <small class="d-block text-muted">{{ $report->resolved_at?->timezone('Asia/Manila')->format('M d, Y') }}</small>
                            @endif
                        </td>
                        <td class="text-end"><a href="{{ route('admin.moderation.show', $report) }}" class="btn btn-sm btn-outline-primary">{{ $report->isOpen() ? 'Review' : 'View' }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ $status === 'open' ? 'No open reports. All clear.' : 'No resolved reports yet.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($reports->hasPages())<div class="card-footer">{{ $reports->links() }}</div>@endif
</div>
@endsection
