{{-- Dashboard Notifications Component --}}
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">
            <i class="bi bi-bell"></i>
            {{ $title ?? 'Notifications' }}
        </h5>
    </div>
    @if($notifications && $notifications->count())
        <div class="list-group list-group-flush">
            @foreach($notifications as $notification)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            @if($notification->title ?? false)
                                <h6 class="mb-1">{{ $notification->title }}</h6>
                            @endif
                            <p class="mb-0 text-muted small">{{ $notification->message }}</p>
                        </div>
                        <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="list-group-item text-muted text-center py-3">
            No notifications at this time.
        </div>
    @endif
</div>
