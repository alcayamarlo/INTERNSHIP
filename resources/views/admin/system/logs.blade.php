@extends('layouts.app')

@section('title', 'System Logs')

@section('content')
<style>
    .system-logs-page {
        padding-top: 4px;
    }

    .system-logs-header {
        margin-bottom: 1.5rem;
    }

    .system-logs-header h1 {
        margin: 0;
        color: #edf6ff;
        font-size: clamp(2.4rem, 2vw + 1rem, 3.5rem);
        font-weight: 800;
        letter-spacing: -0.06em;
    }

    .system-logs-header p {
        margin: 0.5rem 0 0;
        color: rgba(202, 219, 234, 0.76);
        font-size: 1rem;
    }

    .system-logs-card {
        border: 1px solid rgba(148, 163, 184, 0.14);
        border-radius: 18px;
        background: rgba(13, 27, 39, 0.9);
        box-shadow: 0 12px 24px rgba(3, 8, 18, 0.14);
        overflow: hidden;
    }

    .system-logs-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .system-logs-table thead th {
        background: rgba(18, 35, 49, 0.9);
        color: rgba(203, 221, 236, 0.82);
        padding: 0.9rem 1rem;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.09em;
        text-transform: uppercase;
        border-bottom: 1px solid rgba(148, 163, 184, 0.12);
        text-align: left;
    }

    .system-logs-table tbody td {
        padding: 0.9rem 1rem;
        color: rgba(237, 246, 255, 0.96);
        border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        font-size: 0.95rem;
        vertical-align: middle;
    }

    .system-logs-table tbody tr:last-child td {
        border-bottom: none;
    }

    .system-logs-table tbody tr:hover {
        background: rgba(148, 163, 184, 0.03);
    }

    .system-logs-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 28px;
        padding: 0.3rem 0.7rem;
        border-radius: 999px;
        background: rgba(236, 72, 153, 0.12);
        border: 1px solid rgba(236, 72, 153, 0.2);
        color: #f9b1d0;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: lowercase;
    }

    .system-logs-detail {
        color: rgba(180, 199, 217, 0.8);
        font-size: 0.78rem;
        line-height: 1.5;
    }

    .system-logs-empty {
        text-align: center;
        color: rgba(180, 199, 217, 0.8);
        padding: 1.5rem 1rem;
    }
</style>

<div class="system-logs-page">
    <div class="system-logs-header">
        <h1>System Logs</h1>
        <p>Activity and audit trail</p>
    </div>

    <div class="system-logs-card">
        <div class="table-responsive">
            <table class="system-logs-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>IP Address</th>
                        <th>Details</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->user?->name ?? 'System' }}</td>
                            <td><span class="system-logs-action">{{ $log->action }}</span></td>
                            <td>{{ $log->ip_address ?? '—' }}</td>
                            <td>
                                @if($log->details)
                                    <span class="system-logs-detail">{{ Str::limit(json_encode($log->details), 60) }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $log->created_at->format('M d, Y h:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="system-logs-empty">No system logs recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
