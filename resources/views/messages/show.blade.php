@extends('layouts.app')

@section('title', 'Chat with ' . $contact->name)

@section('content')
<style>
    .messenger-shell {
        height: calc(100vh - 220px);
        display: flex;
        flex-direction: column;
        background: rgba(7, 18, 29, 0.82);
        border: 1px solid rgba(148, 163, 184, 0.14);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 24px rgba(2, 6, 23, 0.15);
    }

    .messenger-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.1rem;
        background: rgba(18, 31, 44, 0.92);
        border-bottom: 1px solid rgba(148, 163, 184, 0.12);
    }

    .messenger-header a {
        color: #5ab0ff;
        font-weight: 600;
        text-decoration: none;
    }

    .messenger-header a:hover {
        text-decoration: none;
        color: #88c6ff;
    }

    .messenger-thread {
        flex: 1;
        overflow-y: auto;
        background: rgba(7, 18, 29, 0.82);
        padding: 1rem 1.1rem 0.8rem;
    }

    .messenger-row {
        display: flex;
        margin-bottom: 0.8rem;
    }

    .messenger-row.outgoing {
        justify-content: flex-end;
    }

    .messenger-bubble {
        max-width: 62%;
        border-radius: 16px;
        padding: 0.7rem 0.9rem;
        line-height: 1.45;
        word-wrap: break-word;
    }

    .messenger-row.incoming .messenger-bubble {
        background: rgba(255, 255, 255, 0.94);
        color: #101827;
        border-top-left-radius: 6px;
    }

    .messenger-row.outgoing .messenger-bubble {
        background: linear-gradient(180deg, #1e8af4, #0a6adf);
        color: white;
        border-top-right-radius: 6px;
    }

    .messenger-message {
        margin: 0;
        font-size: 1rem;
    }

    .messenger-time {
        display: block;
        margin-top: 0.3rem;
        font-size: 0.72rem;
        opacity: 0.8;
    }

    .messenger-composer {
        padding: 0.85rem 1rem;
        background: rgba(18, 31, 44, 0.82);
        border-top: 1px solid rgba(148, 163, 184, 0.12);
    }

    .messenger-form {
        display: flex;
        align-items: flex-end;
        gap: 0.75rem;
    }

    .messenger-input {
        flex: 1;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: rgba(255, 255, 255, 0.06);
        border-radius: 12px;
        color: #e5eefb;
        padding: 0.88rem 1rem;
        min-height: 52px;
        resize: none;
    }

    .messenger-input::placeholder {
        color: rgba(191, 205, 219, 0.75);
    }

    .messenger-input:focus {
        border-color: rgba(96, 165, 250, 0.7);
        box-shadow: none;
        outline: none;
    }

    .messenger-send {
        border: none;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.88);
        color: #111827;
        font-weight: 700;
        padding: 0.75rem 1rem;
        min-width: 90px;
        transition: opacity 0.2s ease;
    }

    .messenger-send:hover {
        opacity: 0.95;
    }

    .messenger-empty {
        text-align: center;
        color: rgba(191, 205, 219, 0.75);
        padding: 2rem 1rem;
    }
</style>

<div class="messenger-shell">
    <div class="messenger-header">
        <a href="{{ route('messages.index') }}"><i class="bi bi-arrow-left"></i> Back to Messages</a>
    </div>

    <div class="messenger-header" style="border-top: 0; border-bottom: 1px solid rgba(148,163,184,0.12); padding-top: 0.9rem; padding-bottom: 0.9rem;">
        <div style="display:flex; align-items:center; gap:0.75rem;">
            <div style="width: 34px; height: 34px; border-radius: 50%; background: rgba(148,163,184,0.15); display:flex; align-items:center; justify-content:center; color: #dfeaf6;">
                <i class="bi bi-person-circle"></i>
            </div>
            <div>
                <div style="font-size: 1.05rem; font-weight: 700; color: #f4f9ff;">{{ $contact->name }}</div>
                <div style="font-size: 0.75rem; color: rgba(191,205,219,0.75);">{{ $contact->role->label() }}</div>
            </div>
        </div>
    </div>

    <div class="messenger-thread" id="messageThread">
        @forelse($messages as $message)
            <div class="messenger-row {{ $message->sender_id === auth()->id() ? 'outgoing' : 'incoming' }}">
                <div class="messenger-bubble">
                    <p class="messenger-message">{{ $message->body }}</p>
                    <span class="messenger-time">{{ $message->created_at->format('M d, h:i A') }}</span>
                </div>
            </div>
        @empty
            <div class="messenger-empty">No messages yet. Start the conversation below.</div>
        @endforelse
    </div>

    <div class="messenger-composer">
        <form method="POST" action="{{ route('messages.store', $contact) }}" class="messenger-form">
            @csrf
            <textarea class="messenger-input @error('body') is-invalid @enderror" name="body" rows="1" placeholder="Type your message..." required>{{ old('body') }}</textarea>
            <button type="submit" class="messenger-send"><i class="bi bi-send-fill"></i> Send</button>
            @error('body')
                <div class="text-danger small mt-1 w-100">{{ $message }}</div>
            @enderror
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const thread = document.getElementById('messageThread');
    if (thread) thread.scrollTop = thread.scrollHeight;
</script>
@endpush
