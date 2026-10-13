@extends('layouts.app')

@section('title', $notification->title)

@section('content')
<div class="student-page-shell">
    <div class="mb-4">
        <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to notifications
        </a>
    </div>

    <article class="card">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                <span class="badge bg-secondary">{{ $notification->type }}</span>
                <small class="text-muted">{{ $notification->created_at->format('M d, Y h:i A') }}</small>
            </div>
            <h1 class="h3">{{ $notification->title }}</h1>
            <p class="mb-4">{{ $announcement?->content ?? $notification->message }}</p>

            @if($relatedUrl)
                <a href="{{ $relatedUrl }}" class="btn btn-primary">Open related page</a>
            @endif
        </div>
    </article>
</div>
@endsection
