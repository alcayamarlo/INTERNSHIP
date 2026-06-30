@extends('layouts.app')

@section('title', 'System Logs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">System Logs</h1>
        <p class="text-muted mb-0">Activity and audit trail</p>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-light">
                <tr><th>User</th><th>Action</th><th>IP Address</th><th>Details</th><th>Timestamp</th></tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->user?->name ?? 'System' }}</td>
                        <td><code>{{ $log->action }}</code></td>
                        <td>{{ $log->ip_address ?? '—' }}</td>
                        <td>
                            @if($log->details)
                                <small class="text-muted">{{ Str::limit(json_encode($log->details), 60) }}</small>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $log->created_at->format('M d, Y h:i A') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No system logs recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())<div class="card-footer">{{ $logs->links() }}</div>@endif
</div>
@endsection
