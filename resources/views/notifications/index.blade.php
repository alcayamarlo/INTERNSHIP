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
    .nt-tools .btn-group .btn { display: inline-flex; align-items: center; gap: 6px; }
    .nt-count { min-width: 1.5em; padding: .2em .45em; border-radius: 999px; background: color-mix(in srgb, currentColor 14%, transparent); font-size: .6875rem; font-weight: 700; }
    .nt-bulk { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; padding: 10px 20px; border-bottom: 1px solid var(--sb-border); }
    .nt-bulk label { display: inline-flex; align-items: center; gap: 8px; margin: 0; font-size: .8125rem; color: var(--sb-muted); }
    .nt-bulk .btn { min-height: 30px; padding: 0 12px; font-size: .75rem; font-weight: 600; line-height: 1; }
    .nt-feedback { margin: 0; padding: 8px 20px; font-size: .8125rem; border-bottom: 1px solid var(--sb-border); }
    .nt-feedback.is-error { color: var(--bs-danger, var(--burgundy-text)); }
    .nt-feedback.is-success { color: var(--bs-success, var(--text)); }
    .nt-list { margin: 0; padding: 4px 20px; }
    .nt-item { position: relative; display: flex; align-items: flex-start; gap: 12px; padding: 12px 0; }
    .nt-item + .nt-item { border-top: 1px solid color-mix(in srgb, var(--sb-border) 60%, transparent); }
    .nt-select { flex: 0 0 auto; margin-top: 10px; }
        .nt-icon { position: relative; z-index: 1; display: grid; flex: 0 0 auto; width: 36px; height: 36px; place-items: center; border: 1px solid var(--sb-border); border-radius: 50%; background: var(--sb-surface); color: var(--burgundy-text); font-size: 16px; }
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
    .nt-open:hover .nt-title { color: var(--burgundy-text); }
    .nt-foot { padding: 12px 20px; border-top: 1px solid var(--sb-border); }
    /* Same full-screen treatment as Messages: edge to edge, locked to the screen, only the list scrolls. */
    .sb-main:has(.nt-card) { height: 100vh; height: 100dvh; min-height: 0; overflow: hidden; }
    .sb-main:has(.nt-card) > .sb-topbar { flex: 0 0 auto; }
    body.sb-app .sb-main:has(.nt-card) > .sb-content { display: flex; flex: 1 1 auto; flex-direction: column; min-height: 0; max-width: none; padding: 0; overflow: hidden; }
    body.sb-app .sb-content > .nt-card { display: flex; flex: 1 1 auto; flex-direction: column; min-height: 0; margin: 0; border: 0; border-radius: 0; box-shadow: none; }
    .nt-card .nt-head, .nt-card .nt-foot, .nt-card .nt-bulk, .nt-card .nt-feedback { flex: 0 0 auto; }
    .nt-card .nt-list { flex: 1 1 auto; min-height: 0; overflow-y: auto; padding-left: 20px; padding-right: 20px; }
    .sb-main:has(.nt-card) > .sb-content > .alert { margin: 12px 16px 0; }
    @media (max-width: 575.98px) {
        .nt-head { padding: 14px 16px; }
        .nt-bulk, .nt-feedback { padding-left: 16px; padding-right: 16px; }
        .nt-list { padding: 2px 16px; }
        .nt-row { flex-direction: column; gap: 2px; }
    }
</style>
<link rel="stylesheet" href="{{ asset('css/sb-notifications-page.css') }}?v={{ @filemtime(public_path('css/sb-notifications-page.css')) }}">
@endpush

@section('content')
@php
    // Icon per notification type (keyword match, falls back to a bell).
    $ntIcon = function (string $type): string {
        $type = strtolower($type);
        return match (true) {
            str_contains($type, 'company'), str_contains($type, 'employer') => 'bi-building',
            str_contains($type, 'message') => 'bi-chat-dots',
            str_contains($type, 'application') => 'bi-briefcase',
            str_contains($type, 'invit') => 'bi-envelope-paper',
            str_contains($type, 'certificate'), str_contains($type, 'transcript'), str_contains($type, 'portfolio'), str_contains($type, 'competenc') => 'bi-award',
            str_contains($type, 'announce') => 'bi-megaphone',
            str_contains($type, 'ojt'), str_contains($type, 'placement'), str_contains($type, 'journal') => 'bi-clock-history',
            str_contains($type, 'reject'), str_contains($type, 'warn'), str_contains($type, 'suspend') => 'bi-exclamation-triangle',
            str_contains($type, 'student'), str_contains($type, 'profile'), str_contains($type, 'validation') => 'bi-person',
            default => 'bi-bell',
        };
    };
    $tabs = ['active' => 'Inbox', 'archived' => 'Archived', 'trash' => 'Trash'];
    // Bulk buttons per tab: [action, label, confirm message or null, confirm button label].
    $bulkActions = match ($view) {
        'archived' => [
            ['read', 'Mark read', null, null],
            ['unarchive', 'Unarchive', null, null],
            ['delete', 'Delete', 'Move the selected notifications to Trash? You can restore them from the Trash tab.', 'Delete'],
        ],
        'trash' => [
            ['restore', 'Restore', null, null],
            ['force-delete', 'Delete forever', 'Permanently delete the selected notifications? This cannot be undone.', 'Delete forever'],
        ],
        default => [
            ['read', 'Mark read', null, null],
            ['archive', 'Archive', null, null],
            ['delete', 'Delete', 'Move the selected notifications to Trash? You can restore them from the Trash tab.', 'Delete'],
        ],
    };
@endphp

{{-- Live region: new notifications appear in the list without a reload (resources/js/app.js). --}}
<section class="card nt-card" aria-labelledby="nt-title" data-notification-page data-view="{{ $view }}" data-live-region="notifications" data-live-topics="notifications">
    <header class="nt-head">
        <div class="nt-headtext">
            <h1 id="nt-title">Notifications</h1>
            <p data-notification-summary>
                @if($view === 'archived')
                    Archived notifications. Unarchive one to move it back to your inbox.
                @elseif($view === 'trash')
                    Deleted notifications. Restore them, or delete them forever.
                @elseif($unread_count > 0)
                    You have {{ $unread_count }} unread {{ \Illuminate\Support\Str::plural('notification', $unread_count) }}.
                @else
                    You're all caught up.
                @endif
            </p>
        </div>
        <div class="nt-tools">
            <div class="nt-tabs" role="group" aria-label="Notification folders">
                @foreach($tabs as $key => $label)
                    <a href="{{ route('notifications.index', ['view' => $key]) }}" class="nt-tab {{ $view === $key ? 'is-active' : '' }}" @if($view === $key) aria-current="true" @endif>
                        {{ $label }} <span class="nt-count" data-notification-count="{{ $key }}">{{ $counts[$key] }}</span>
                    </a>
                @endforeach
            </div>
            @if($view === 'active')
                <div class="nt-more" data-nt-more>
                    <button type="button" class="nt-more-toggle" data-nt-more-toggle aria-haspopup="menu" aria-expanded="false" aria-label="More actions"><i class="bi bi-three-dots" aria-hidden="true"></i></button>
                    <div class="nt-more-menu" data-nt-more-menu>
                        <form action="{{ route('notifications.read-all') }}" method="POST" data-ajax-notification="read-all" data-nt-readall @if($unread_count < 1) hidden @endif>
                            @csrf
                            <button type="submit" class="nt-pill"><i class="bi bi-check-all" aria-hidden="true"></i> Mark all read</button>
                        </form>
                        <form action="{{ route('notifications.archive-all-read') }}" method="POST" data-ajax-notification="archive-all-read">
                            @csrf
                            <button type="submit" class="nt-pill"><i class="bi bi-archive" aria-hidden="true"></i> Archive all read</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </header>

    {{-- Bulk actions. Row checkboxes join this form through form="ntBulkForm". --}}
    @if($notifications->isNotEmpty())
        <form id="ntBulkForm" class="nt-bulk" action="{{ route('notifications.bulk') }}" method="POST" data-ajax-notification="bulk">
            @csrf
            <label><input type="checkbox" class="form-check-input" data-notification-select-all> Select all on this page</label>
            <span class="nt-selcount" data-nt-selcount aria-live="polite"></span>
            @foreach($bulkActions as [$action, $label, $confirm, $confirmLabel])
                <button type="submit" name="action" value="{{ $action }}" disabled data-bulk-action
                        class="btn {{ in_array($action, ['delete', 'force-delete'], true) ? 'btn-outline-danger' : 'btn-outline-secondary' }}"
                        @if($confirm) data-confirm="{{ $confirm }}" data-confirm-title="{{ $label }}" data-confirm-label="{{ $confirmLabel }}" @endif>
                    {{ $label }}
                </button>
            @endforeach
        </form>
    @endif

    <div class="nt-toast" data-nt-toast>
        <span class="nt-toast-icon" aria-hidden="true"><i class="bi bi-check-circle-fill"></i></span>
        {{-- Success and error messages for every action on this page (text written by resources/js/app.js). --}}
        <p class="nt-feedback" role="status" aria-live="polite" data-notification-feedback hidden></p>
        <button type="button" class="nt-toast-x" data-nt-toast-close aria-label="Dismiss message"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    </div>

    <div class="nt-list" data-notification-list>
        @forelse($notifications as $notification)
            <div class="nt-item {{ $notification->read_at ? '' : 'is-unread' }}" data-notification-item data-notification-id="{{ $notification->id }}">
                <input type="checkbox" class="form-check-input nt-select" name="ids[]" value="{{ $notification->id }}" form="ntBulkForm" data-notification-select aria-label="Select: {{ $notification->title }}">
                <span class="nt-icon" aria-hidden="true"><i class="bi {{ $ntIcon((string) $notification->type) }}"></i></span>
                <div class="nt-main">
                    @if($view === 'trash')
                        {{-- A deleted notification cannot be opened until it is restored. --}}
                        <div class="nt-open">
                    @else
                        <a class="nt-open notification-open-link"
                           href="{{ route('notifications.show', $notification) }}"
                           data-notification-open
                           data-read-url="{{ route('notifications.read', $notification) }}">
                    @endif
                        <span class="nt-title">{{ $notification->title }}</span>
                        <span class="nt-msg" title="{{ $notification->message }}">{{ $notification->message }}</span>
                    @if($view === 'trash')
                        </div>
                    @else
                        </a>
                    @endif
                </div>
                <div class="nt-side">
                    <time class="nt-time" datetime="{{ $notification->created_at->toIso8601String() }}" title="{{ $notification->created_at->format('M d, Y h:i A') }}">{{ $notification->created_at->diffForHumans() }}</time>
                    <div class="nt-actions">
                        @if($view === 'trash')
                            <form action="{{ route('notifications.restore-deleted', $notification) }}" method="POST" data-ajax-notification="restore-deleted">
                                @csrf
                                <button type="submit" class="nt-ib" title="Restore" aria-label="Restore"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i></button>
                            </form>
                            <form action="{{ route('notifications.force-destroy', $notification) }}" method="POST" data-ajax-notification="force-delete" data-confirm="Permanently delete this notification? This cannot be undone." data-confirm-title="Delete forever" data-confirm-label="Delete forever">
                                @csrf @method('DELETE')
                                <button type="submit" class="nt-ib nt-ib--danger" title="Delete permanently" aria-label="Delete permanently"><i class="bi bi-trash3" aria-hidden="true"></i></button>
                            </form>
                        @else
                            @if(!$notification->read_at)
                                <form action="{{ route('notifications.read', $notification) }}" method="POST" data-ajax-notification="read">
                                    @csrf
                                    <button type="submit" class="nt-ib" title="Mark read" aria-label="Mark read"><i class="bi bi-envelope-open" aria-hidden="true"></i></button>
                                </form>
                            @endif
                            @if($notification->archived_at)
                                <form action="{{ route('notifications.restore', $notification) }}" method="POST" data-ajax-notification="unarchive">
                                    @csrf
                                    <button type="submit" class="nt-ib" title="Unarchive" aria-label="Unarchive"><i class="bi bi-box-arrow-up" aria-hidden="true"></i></button>
                                </form>
                            @else
                                <form action="{{ route('notifications.archive', $notification) }}" method="POST" data-ajax-notification="archive">
                                    @csrf
                                    <button type="submit" class="nt-ib" title="Archive" aria-label="Archive"><i class="bi bi-archive" aria-hidden="true"></i></button>
                                </form>
                            @endif
                            <form action="{{ route('notifications.destroy', $notification) }}" method="POST" data-ajax-notification="delete" data-confirm="Move this notification to Trash? You can restore it from the Trash tab." data-confirm-title="Delete notification" data-confirm-label="Delete">
                                @csrf @method('DELETE')
                                <button type="submit" class="nt-ib nt-ib--danger" title="Delete" aria-label="Delete"><i class="bi bi-trash3" aria-hidden="true"></i></button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            @include('notifications.partials.empty', ['view' => $view])
        @endforelse
    </div>

    {{-- Shown by app.js when the last row on the page is archived or deleted. --}}
    <template data-notification-empty>@include('notifications.partials.empty', ['view' => $view])</template>

    @if($notifications->hasPages())<div class="nt-foot">{{ $notifications->links() }}</div>@endif
</section>
@endsection
@push('scripts')
<script src="{{ asset('js/sb-notif-page.js') }}?v={{ @filemtime(public_path('js/sb-notif-page.js')) }}" defer></script>
@endpush
