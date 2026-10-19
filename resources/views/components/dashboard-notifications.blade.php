{{-- Dashboard notifications panel. Props: title, notifications --}}
@props(['title' => 'Notifications', 'notifications' => null])

<div class="card">
    <div class="card-header">
        <h2>
            <i class="bi bi-bell" aria-hidden="true"></i>
            {{ $title }}
        </h2>
    </div>

    @if($notifications && $notifications->count())
        <ul class="sb-list">
            @foreach($notifications as $notification)
                <li class="sb-list-item {{ ($notification->read_at ?? true) ? '' : 'is-unread' }}">
                    <div>
                        @if($notification->title ?? false)
                            <p class="sb-list-title">{{ $notification->title }}</p>
                        @endif
                        <p class="sb-list-text">{{ $notification->message }}</p>
                    </div>
                    <time class="sb-list-meta" datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time>
                </li>
            @endforeach
        </ul>
    @else
        <x-empty-state icon="bi-bell" title="No notifications" message="You're all caught up. New activity will show up here." />
    @endif
</div>
