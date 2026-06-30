@extends('layouts.app')

@section('title', 'Messages')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-1">Messages</h1>
    <p class="text-muted mb-0">Your conversations</p>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-white"><h5 class="mb-0">Conversations</h5></div>
            <div class="list-group list-group-flush" style="max-height:500px;overflow-y:auto;">
                @forelse($conversations as $contactId => $thread)
                    @php $contact = $contacts->firstWhere('id', $contactId) ?? \App\Models\User::find($contactId); @endphp
                    @if($contact)
                        <a href="{{ route('messages.show', $contact) }}" class="list-group-item list-group-item-action {{ request()->routeIs('messages.show') && request()->route('user')?->id === $contact->id ? 'active' : '' }}">
                            <strong>{{ $contact->name }}</strong>
                            <br><small class="{{ request()->routeIs('messages.show') && request()->route('user')?->id === $contact->id ? '' : 'text-muted' }}">{{ Str::limit($thread->first()->body, 50) }}</small>
                        </a>
                    @endif
                @empty
                    <div class="list-group-item text-muted text-center">No conversations yet.</div>
                @endforelse
            </div>
        </div>
        @if($contacts->count())
        <div class="card mt-3">
            <div class="card-header bg-white"><h5 class="mb-0">Start New</h5></div>
            <div class="list-group list-group-flush">
                @foreach($contacts as $contact)
                    <a href="{{ route('messages.show', $contact) }}" class="list-group-item list-group-item-action">
                        <i class="bi bi-person"></i> {{ $contact->name }}
                        <small class="text-muted d-block">{{ $contact->role->label() }}</small>
                    </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-chat-dots fs-1 d-block mb-2"></i>
                Select a conversation or contact to start messaging.
            </div>
        </div>
    </div>
</div>
@endsection
