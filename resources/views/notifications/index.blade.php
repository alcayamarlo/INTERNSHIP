@extends('layouts.app')

@section('title', 'Notifications')

@push('styles')
<style>
    .nt-card { max-width: none; overflow: hidden; }
    .nt-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px 16px; padding: 16px 20px; border-bottom: 1px solid var(--sb-border); }
    .nt-head h1 { margin: 0; font-size: 1rem; font-weight: 700; }
    .nt-head p { margin: 2px 0 0; color: var(--sb-muted); font-size: .875rem; }
    .nt-tools .btn { min-height: 36px; padding: 0 14px; font-size: .8125rem; line-height: 1; }
    .nt-tools { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    .nt-list { margin: 0; padding: 4px 20px; }
    .nt-item { position: relative; display: flex; align-items: flex-start; gap: 12px; padding: 12px 0; }
    .nt-item + .nt-item { border-top: 1px solid color-mix(in srgb, var(--sb-border) 60%, transparent); }
        .nt-icon { position: relative; z-index: 1; display: grid; flex: 0 0 auto; width: 36px; height: 36px; place-items: center; border: 1px solid var(--sb-border); border-radius: 50%; background: var(--sb-surface); color: var(--sb-brand); font-size: 16px; }
    .nt-item.is-unread .nt-icon { border-color: var(--sb-brand); background: var(--sb-brand-soft); }
    .nt-item.is-unread .nt-icon::after { position: absolute; top: -1px; right: -1px; width: 10px; height: 10px; border: 2px solid var(--sb-surface); border-radius: 50%; background: var(--sb-brand); content: ""; }
    .nt-open { display: block; min-width: 0; flex: 1 1 auto; color: inherit; text-decoration: none; }
    .nt-row { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; }
    .nt-title { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; color: var(--sb-ink); font-size: .9375rem; font-weight: 600; overflow-wrap: anywhere; }
    .nt-item:not(.is-unread) .nt-title { font-weight: 500; }
    .nt-time { flex: 0 0 auto; color: var(--sb-muted); font-size: .8125rem; white-space: nowrap; }
    .nt-msg { margin: 2px 0 0; color: var(--sb-muted); font-size: .875rem; line-height: 1.45; overflow-wrap: anywhere; }
    .nt-actions { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .nt-actions .btn { min-height: 30px; padding: 0 12px; font-size: .75rem; font-weight: 600; line-height: 1; }
    .nt-item .badge { padding: .3em .6em; font-size: .6875rem; }
    .nt-open:hover .nt-title { color: var(--sb-brand-deep); }
    .nt-foot { padding: 12px 20px; border-top: 1px solid var(--sb-border); }
    @media (max-width: 575.98px) {
        .nt-head { padding: 14px 16px; }
        .nt-list { padding: 2px 16px; }
        .nt-row { flex-direction: column; gap: 2px; }
    }
</style>
@endpush

@section('content')
@php
    // Icon per notification type (keyword match, falls back to a bell).
    $ntIcon = function (string $type): string {
        $type = strtolower($type);
        return match (true) {
            str_contains($type, 'message') => 'bi-chat-dots',
            str_contains($type, 'application') => 'bi-briefcase',
            str_contains($type, 'invit') => 'bi-envelope-paper',
            str_contains($type, 'certificate'), str_contains($type, 'transcript'), str_contains($type, 'portfolio'), str_contains($type, 'competenc') => 'bi-award',
            str_contains($type, 'announce') => 'bi-megaphone',
            str_contains($type, 'ojt'), str_contains($type, 'placement'), str_contains($type, 'journal') => 'bi-clock-history',
            str_contains($type, 'reject'), str_contains($type, 'warn'), str_contains($type, 'suspend') => 'bi-exclamation-triangle',
            default => 'bi-bell',
        };
    };
    $unreadOnPage = $notifications->getCollection()->whereNull('read_at')->count();
@endphp

<section class="card nt-card" aria-labelledby="nt-title">
    <header class="nt-head">
        <div>
            <h1 id="nt-title">Notifications</h1>
            <p>
                @if($view === 'archived')
                    Your archived notifications.
                @elseif($unreadOnPage > 0)
                    You have {{ $unreadOnPage }} new {{ \Illuminate\Support\Str::plural('notification', $unreadOnPage) }}.
                @else
                    You're all caught up.
                @endif
            </p>
        </div>
        <div class="nt-tools">
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
        </div>
    </header>

    <div class="nt-list">
        @forelse($notifications as $notification)
            <div class="nt-item {{ $notification->read_at ? '' : 'is-unread' }}" data-notification-item data-notification-id="{{ $notification->id }}">
                <span class="nt-icon" aria-hidden="true"><i class="bi {{ $ntIcon((string) $notification->type) }}"></i></span>
                <div class="min-w-0 flex-grow-1">
                    <a class="nt-open notification-open-link"
                       href="{{ route('notifications.show', $notification) }}"
                       data-notification-open
                       data-read-url="{{ route('notifications.read', $notification) }}">
                        <div class="nt-row">
                            <span class="nt-title">
                                {{ $notification->title }}
                                @if(!$notification->read_at)<span class="badge bg-primary">New</span>@endif
                                @if($notification->archived_at)<span class="sb-tag">Archived</span>@endif
                            </span>
                            <time class="nt-time" datetime="{{ $notification->created_at->toIso8601String() }}" title="{{ $notification->created_at->format('M d, Y h:i A') }}">{{ $notification->created_at->diffForHumans() }}</time>
                        </div>
                        <p class="nt-msg">{{ $notification->message }}</p>
                    </a>
                    <div class="nt-actions">
                        @if(!$notification->read_at)
                            <form action="{{ route('notifications.read', $notification) }}" method="POST" data-ajax-notification="read">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary">Mark Read</button>
                            </form>
                        @endif
                        @if($notification->archived_at)
                            <form action="{{ route('notifications.restore', $notification) }}" method="POST" data-ajax-notification="restore">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary">Restore</button>
                            </form>
                        @else
                            <form action="{{ route('notifications.archive', $notification) }}" method="POST" data-ajax-notification="archive" data-confirm="Delete this notification? It will be moved to your archive." data-confirm-title="Delete notification">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary">Delete (archive)</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <x-empty-state icon="bi-bell-slash" title="No notifications yet" message="Alerts and updates about your activity will appear here." />
        @endforelse
    </div>

    @if($notifications->hasPages())<div class="nt-foot">{{ $notifications->links() }}</div>@endif
</section>
@endsection
