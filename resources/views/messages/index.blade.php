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
                    @php $contact = $people->get($contactId); @endphp
                    @if($contact)
                        <a href="{{ route('messages.show', $contact) }}" class="list-group-item list-group-item-action {{ request()->routeIs('messages.show') && request()->route('user')?->id === $contact->id ? 'active' : '' }}">
                            <strong>{{ $contact->name }}</strong>
                            @if($lockedReasons->has($contactId))
                                <i class="bi bi-lock-fill text-muted ms-1" title="{{ $lockedReasons[$contactId] }}" aria-hidden="true"></i>
                                <small class="d-block text-muted" data-chat-locked>{{ $lockedReasons[$contactId] }}</small>
                            @endif
                            <small class="d-block {{ request()->routeIs('messages.show') && request()->route('user')?->id === $contact->id ? '' : 'text-muted' }}">{{ Str::limit($thread->first()->body, 50) }}</small>
                        </a>
                    @endif
                @empty
                    <x-empty-state icon="bi-chat-dots" title="No conversations yet" message="Start a new one from the contact list." />
                @endforelse
            </div>
        </div>
        @if($lockedCompanies->isNotEmpty())
        <div class="card mt-3">
            <div class="card-header"><h2>Companies you applied to</h2></div>
            <ul class="list-group list-group-flush">
                @foreach($lockedCompanies as $row)
                    <li class="list-group-item" data-chat-locked>
                        <i class="bi bi-lock-fill text-muted" aria-hidden="true"></i> {{ $row['user']->employer?->company_name ?? $row['user']->name }}
                        <small class="text-muted d-block">{{ $row['reason'] }}</small>
                    </li>
                @endforeach
            </ul>
        </div>
        @endif
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
