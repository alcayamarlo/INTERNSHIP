@extends('layouts.app')

@section('title', 'System Logs')

@section('content')

<x-page-header title="System Logs" subtitle="Activity and audit trail" />

<div class="card">

    @if($logs->count())
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th scope="col">User</th>
                        <th scope="col">Action</th>
                        <th scope="col">IP Address</th>
                        <th scope="col">Details</th>
                        <th scope="col">Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td>{{ $log->user?->name ?? 'System' }}</td>
                            <td><span class="sb-tag sb-tag--brand">{{ $log->action }}</span></td>
                            <td>{{ $log->ip_address ?? '—' }}</td>
                            <td>
                                @if($log->details)
                                    <span class="text-muted small">{{ Str::limit(json_encode($log->details), 60) }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-nowrap">{{ $log->created_at->format('M d, Y h:i A') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <x-empty-state icon="bi-journal-text" title="No system logs recorded" message="Account and system activity will be listed here." />
    @endif

    @if($logs instanceof \Illuminate\Contracts\Pagination\Paginator && $logs->hasPages())
        <div class="card-footer">{{ $logs->links() }}</div>
    @endif

</div>

@endsection
