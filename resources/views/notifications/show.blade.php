@extends('layouts.app')

@section('title', $notification->title)

@push('styles')
<style>
    .nt-detail { max-width: none; overflow: hidden; }
    .nt-detail-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px 16px; padding: 16px 20px; border-bottom: 1px solid var(--sb-border); }
    .nt-detail-head h1 { margin: 0; font-size: 1rem; font-weight: 700; }
    .nt-detail-head p { margin: 2px 0 0; color: var(--sb-muted); font-size: .875rem; }
    .nt-detail-head .btn, .nt-detail .btn { min-height: 36px; padding: 0 14px; font-size: .8125rem; line-height: 1; }
    .nt-detail-body { display: flex; align-items: flex-start; gap: 12px; padding: 20px; }
    .nt-detail-icon { display: grid; flex: 0 0 auto; width: 36px; height: 36px; place-items: center; border: 1px solid var(--sb-border); border-radius: 50%; background: var(--sb-brand-soft); color: var(--sb-brand); font-size: 16px; }
    .nt-detail-main { min-width: 0; flex: 1 1 auto; }
    .nt-detail-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 12px; color: var(--sb-muted); font-size: .8125rem; }
    .nt-detail-title { margin: 8px 0 6px; color: var(--sb-ink); font-size: 1.125rem; font-weight: 700; line-height: 1.3; letter-spacing: -.01em; overflow-wrap: anywhere; }
    .nt-detail-text { margin: 0; color: var(--sb-text-soft, var(--sb-ink)); font-size: .9375rem; line-height: 1.6; white-space: pre-line; overflow-wrap: anywhere; }
    .nt-detail-actions { margin-top: 16px; }
    /* Same full-screen treatment as the notifications list: edge to edge, locked to the screen, the body scrolls inside. */
    .sb-main:has(.nt-detail) { height: 100vh; height: 100dvh; min-height: 0; overflow: hidden; }
    .sb-main:has(.nt-detail) > .sb-topbar { flex: 0 0 auto; }
    body.sb-app .sb-main:has(.nt-detail) > .sb-content { display: flex; flex: 1 1 auto; flex-direction: column; min-height: 0; max-width: none; padding: 0; overflow: hidden; }
    body.sb-app .sb-content > .nt-detail { display: flex; flex: 1 1 auto; flex-direction: column; min-height: 0; margin: 0; border: 0; border-radius: 0; box-shadow: none; }
    .nt-detail .nt-detail-head { flex: 0 0 auto; }
    .nt-detail .nt-detail-body { flex: 1 1 auto; min-height: 0; overflow-y: auto; }
    .sb-main:has(.nt-detail) > .sb-content > .alert { margin: 12px 16px 0; }
    @media (max-width: 575.98px) { .nt-detail-head { padding: 14px 16px; } .nt-detail-body { padding: 16px; } }
</style>
@endpush

@section('content')
@php
    $ntType = strtolower((string) $notification->type);
    $ntIcon = match (true) {
        str_contains($ntType, 'message') => 'bi-chat-dots',
        str_contains($ntType, 'application') => 'bi-briefcase',
        str_contains($ntType, 'invit') => 'bi-envelope-paper',
        str_contains($ntType, 'certificate'), str_contains($ntType, 'transcript'), str_contains($ntType, 'portfolio'), str_contains($ntType, 'competenc') => 'bi-award',
        str_contains($ntType, 'announce') => 'bi-megaphone',
        str_contains($ntType, 'ojt'), str_contains($ntType, 'placement'), str_contains($ntType, 'journal') => 'bi-clock-history',
        str_contains($ntType, 'reject'), str_contains($ntType, 'warn'), str_contains($ntType, 'suspend') => 'bi-exclamation-triangle',
        default => 'bi-bell',
    };
@endphp

<article class="card nt-detail" aria-labelledby="nt-detail-title">
    <header class="nt-detail-head">
        <div>
            <h1 id="nt-detail-title">Notification</h1>
            <p>Details of your alert or update</p>
        </div>
        <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to notifications</a>
    </header>

    <div class="nt-detail-body">
        <span class="nt-detail-icon" aria-hidden="true"><i class="bi {{ $ntIcon }}"></i></span>
        <div class="nt-detail-main">
            <div class="nt-detail-meta">
                <span class="sb-tag">{{ $notification->type }}</span>
                <span><i class="bi bi-clock" aria-hidden="true"></i> {{ $notification->created_at->format('M d, Y h:i A') }}</span>
                <span>({{ $notification->created_at->diffForHumans() }})</span>
            </div>

            <h2 class="nt-detail-title">{{ $notification->title }}</h2>
            <p class="nt-detail-text">{{ $announcement?->content ?? $notification->message }}</p>

            @if($relatedUrl)
                <div class="nt-detail-actions">
                    <a href="{{ $relatedUrl }}" class="btn btn-primary">Open related page</a>
                </div>
            @endif
        </div>
    </div>
</article>
@endsection
