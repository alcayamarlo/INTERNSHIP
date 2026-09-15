@extends('layouts.app')

@section('title', 'Notifications')

@push('styles')
<style>
    .student-page-shell { padding: 8px 0; color: #edf8ff; }
    .student-page-shell .card, .student-page-shell .list-group-item, .student-page-shell .btn { border-color: rgba(117,176,215,.18) !important; background: rgba(12,24,36,.9) !important; color: #edf8ff !important; }
    .student-page-shell .card { border-radius: 18px; box-shadow: 0 8px 28px rgba(2,9,20,.2); }
    .student-page-shell .card:hover { border-color: rgba(49,217,244,.36) !important; }
    .student-page-shell .list-group-item { border-bottom: 1px solid rgba(117,176,215,.12); }
    .student-page-title { margin: 0 0 8px; color: #f4f9ff; font-size: clamp(2.2rem,2.3vw,3.2rem); font-weight: 800; letter-spacing: -.05em; }
    .student-page-subtitle { margin: 0; color: rgba(186,209,228,.8); font-size: 1.05rem; }
    .student-page-shell .btn-outline-primary { border-color: rgba(77,210,255,.5); color: #7fe0ff !important; }
    .student-page-shell .badge { border-radius: 999px; padding: .4rem .7rem; }
    .student-page-shell .bg-light { background: rgba(31,191,255,.1) !important; color: #edf8ff !important; }
    .student-page-shell .btn-outline-primary { border-radius: 10px; }
</style>
@endpush

@section('content')
<div class="student-page-shell">
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="student-page-title">Notifications</h1>
        <p class="student-page-subtitle">Your recent alerts and updates</p>
    </div>
    <form action="{{ route('notifications.read-all') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-check-all"></i> Mark All Read</button>
    </form>
</div>

<div class="card">
    <div class="list-group list-group-flush">
        @forelse($notifications as $notification)
            <div class="list-group-item {{ $notification->read_at ? '' : 'bg-light border-start border-primary border-3' }}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            @if(!$notification->read_at)<span class="badge bg-primary">New</span>@endif
                            <strong>{{ $notification->title }}</strong>
                            <span class="badge bg-secondary">{{ $notification->type }}</span>
                        </div>
                        <p class="mb-1">{{ $notification->message }}</p>
                        <small class="text-muted">{{ $notification->created_at->format('M d, Y h:i A') }} ({{ $notification->created_at->diffForHumans() }})</small>
                    </div>
                    @if(!$notification->read_at)
                        <form action="{{ route('notifications.read', $notification) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary">Mark Read</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="list-group-item text-center text-muted py-5">
                <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>
                No notifications yet.
            </div>
        @endforelse
    </div>
    @if($notifications->hasPages())<div class="card-footer">{{ $notifications->links() }}</div>@endif
</div>
</div>
@endsection
