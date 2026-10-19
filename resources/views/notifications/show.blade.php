@extends('layouts.app')

@section('title', $notification->title)

@section('content')
<x-page-header :title="$notification->title" :subtitle="$notification->created_at->format('M d, Y h:i A')">
    <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to notifications</a>
</x-page-header>

<div>

    <article class="card">
        <div class="card-body p-4">
            <div class="mb-3"><span class="sb-tag">{{ $notification->type }}</span></div>
            <p class="mb-4">{{ $announcement?->content ?? $notification->message }}</p>

            @if($relatedUrl)
                <a href="{{ $relatedUrl }}" class="btn btn-primary">Open related page</a>
            @endif
        </div>
    </article>
</div>
@endsection
