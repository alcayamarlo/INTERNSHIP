@extends('layouts.app')

@section('title', 'Messages')

@section('content')
<x-page-header title="Messages" subtitle="Your conversations" />

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h2>Conversations</h2></div>
            <div class="list-group list-group-flush sb-scroll-list">
                @forelse($conversations as $contactId => $thread)
                    @php $contact = $contacts->firstWhere('id', $contactId) ?? \App\Models\User::find($contactId); @endphp
                    @if($contact)
                        <a href="{{ route('messages.show', $contact) }}" class="list-group-item list-group-item-action {{ request()->routeIs('messages.show') && request()->route('user')?->id === $contact->id ? 'active' : '' }}">
                            <strong>{{ $contact->name }}</strong>
                            <br><small class="{{ request()->routeIs('messages.show') && request()->route('user')?->id === $contact->id ? '' : 'text-muted' }}">{{ Str::limit($thread->first()->body, 50) }}</small>
                        </a>
                    @endif
                @empty
                    <x-empty-state icon="bi-chat-dots" title="No conversations yet" message="Start a new one from the contact list." />
                @endforelse
            </div>
        </div>
        @if($contacts->count())
        <div class="card mt-3">
            <div class="card-header"><h2>Start New</h2></div>
            <div class="list-group list-group-flush">
                @foreach($contacts as $contact)
                    <a href="{{ route('messages.show', $contact) }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-person" aria-hidden="true"></i> {{ $contact->name }}
                        <small class="text-muted d-block">{{ $contact->role->label() }}</small>
                    </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    <div class="col-lg-8">
        <div class="card">
            <x-empty-state icon="bi-chat-dots" title="Select a conversation" message="Choose a conversation or contact to start messaging." />
        </div>
    </div>
</div>
@endsection
