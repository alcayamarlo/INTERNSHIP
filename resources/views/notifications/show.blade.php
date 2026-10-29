@extends('layouts.app')

@section('title', $notification->title)

@push('styles')
<style>
    .notification-detail {
        max-width: 820px;
    }

    .notification-detail .notification-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px 14px;
        padding-bottom: 1.1rem;
        border-bottom: 1px solid var(--sb-border);
        color: var(--sb-muted);
        font-size: 0.9rem;
    }

    .notification-detail .notification-title {
        margin: 1.25rem 0 0.75rem;
        color: var(--sb-text);
        font-size: 1.5rem;
        font-weight: 800;
        line-height: 1.25;
        letter-spacing: -0.02em;
        overflow-wrap: anywhere;
    }

    .notification-detail .notification-body {
        margin: 0;
        color: var(--sb-text-soft);
        font-size: 1.05rem;
        line-height: 1.7;
        white-space: pre-line;
        overflow-wrap: anywhere;
    }

    .notification-detail .notification-actions {
        margin-top: 1.5rem;
    }
</style>
@endpush

@section('content')
<x-page-header title="Notification" subtitle="Details of your alert or update">
    <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to notifications</a>
</x-page-header>

<article class="card notification-detail">
    <div class="card-body p-4">
        <div class="notification-meta">
            <span class="sb-tag">{{ $notification->type }}</span>
            <span><i class="bi bi-clock" aria-hidden="true"></i> {{ $notification->created_at->format('M d, Y h:i A') }}</span>
            <span>({{ $notification->created_at->diffForHumans() }})</span>
        </div>

        <h2 class="notification-title">{{ $notification->title }}</h2>
        <p class="notification-body">{{ $announcement?->content ?? $notification->message }}</p>

        @if($relatedUrl)
            <div class="notification-actions">
                <a href="{{ $relatedUrl }}" class="btn btn-primary">Open related page</a>
            </div>
        @endif
    </div>
</article>
@endsection
