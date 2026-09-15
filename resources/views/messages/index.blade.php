@extends('layouts.app')

@section('title', 'Messages')

@push('styles')
<style>
    .student-page-shell { padding: 8px 0; color: #edf8ff; }
    .student-page-shell .card, .student-page-shell .list-group-item, .student-page-shell .btn { border-color: rgba(117,176,215,.18) !important; background: rgba(12,24,36,.9) !important; color: #edf8ff !important; }
    .student-page-shell .card { border-radius: 18px; box-shadow: 0 8px 28px rgba(2,9,20,.2); }
    .student-page-shell .card:hover { border-color: rgba(49,217,244,.36) !important; }
    .student-page-shell .card-header { background: rgba(13,30,44,.92) !important; border-bottom: 1px solid rgba(117,176,215,.18); }
    .student-page-title { margin: 0 0 8px; color: #f4f9ff; font-size: clamp(2.2rem,2.3vw,3.2rem); font-weight: 800; letter-spacing: -.05em; }
    .student-page-subtitle { margin: 0; color: rgba(186,209,228,.8); font-size: 1.05rem; }
    .student-page-shell .list-group-item { border-bottom: 1px solid rgba(117,176,215,.12); }
    .student-page-shell .list-group-item-action.active { background: rgba(31,191,255,.14) !important; border-color: rgba(31,191,255,.4); }
    .student-page-shell .list-group-item-action:hover { background: rgba(31,191,255,.08) !important; }
    .student-page-shell .card-body.text-center { min-height: 230px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
</style>
@endpush

@section('content')
<div class="student-page-shell">
<div class="mb-4">
    <h1 class="student-page-title">Messages</h1>
    <p class="student-page-subtitle">Your conversations</p>
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
</div>
@endsection
