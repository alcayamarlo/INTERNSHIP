@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<x-page-header title="Notifications" subtitle="Your recent alerts and updates">
    <div class="btn-group" role="group" aria-label="Notification views">
        <a href="{{ route('notifications.index', ['view' => 'active']) }}" class="btn btn-sm {{ $view === 'active' ? 'btn-primary' : 'btn-outline-primary' }}" @if($view === 'active') aria-current="true" @endif>Active</a>
        <a href="{{ route('notifications.index', ['view' => 'archived']) }}" class="btn btn-sm {{ $view === 'archived' ? 'btn-primary' : 'btn-outline-primary' }}" @if($view === 'archived') aria-current="true" @endif>Archived</a>
    </div>
    <form action="{{ route('notifications.read-all') }}" method="POST" data-ajax-notification="read-all">
        @csrf
        <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-check-all" aria-hidden="true"></i> Mark All Read</button>
    </form>
    @if($view === 'archived')
        <form action="{{ route('notifications.archive-all-read') }}" method="POST" data-ajax-notification="archive-all-read">
            @csrf
            <button type="submit" class="btn btn-outline-secondary btn-sm">Archive all read</button>
        </form>
    @endif
</x-page-header>

<div class="card">
    <div class="list-group list-group-flush">
        @forelse($notifications as $notification)
            <div class="list-group-item {{ $notification->read_at ? '' : 'is-unread' }}" data-notification-item data-notification-id="{{ $notification->id }}">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <a class="flex-grow-1 text-reset text-decoration-none notification-open-link"
                       href="{{ route('notifications.show', $notification) }}"
                       data-notification-open
                       data-read-url="{{ route('notifications.read', $notification) }}">
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            @if(!$notification->read_at)<span class="badge bg-primary">New</span>@endif
                            @if($notification->archived_at)<span class="sb-tag">Archived</span>@endif
                            <strong>{{ $notification->title }}</strong>
                            <span class="sb-tag">{{ $notification->type }}</span>
                        </div>
                        <p class="mb-1">{{ $notification->message }}</p>
                        <small class="text-muted">{{ $notification->created_at->format('M d, Y h:i A') }} ({{ $notification->created_at->diffForHumans() }})</small>
                    </a>
                    <div class="d-flex flex-column gap-2 align-items-end">
                        @if(!$notification->read_at)
                            <form action="{{ route('notifications.read', $notification) }}" method="POST" data-ajax-notification="read">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary">Mark Read</button>
                            </form>
                        @endif
                        @if($notification->archived_at)
                            <form action="{{ route('notifications.restore', $notification) }}" method="POST" data-ajax-notification="restore">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Restore</button>
                            </form>
                        @else
                            <form action="{{ route('notifications.archive', $notification) }}" method="POST" data-ajax-notification="archive" data-confirm="Delete this notification? It will be moved to your archive." data-confirm-title="Delete notification">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Delete (archive)</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <x-empty-state icon="bi-bell-slash" title="No notifications yet" message="Alerts and updates about your activity will appear here." />
        @endforelse
    </div>
    @if($notifications->hasPages())<div class="card-footer">{{ $notifications->links() }}</div>@endif
</div>
@endsection
