@extends('layouts.app')

@section('title', 'Chat with ' . $contact->name)

@section('content')
<div class="mb-3">
    <a href="{{ route('messages.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Messages</a>
</div>

<div class="card" style="height: calc(100vh - 220px); display:flex; flex-direction:column;">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-person-circle"></i> {{ $contact->name }}</h5>
        <small class="text-muted">{{ $contact->role->label() }}</small>
    </div>
    <div class="card-body overflow-auto flex-grow-1" id="messageThread">
        @forelse($messages as $message)
            <div class="d-flex mb-3 {{ $message->sender_id === auth()->id() ? 'justify-content-end' : '' }}">
                <div class="rounded px-3 py-2 {{ $message->sender_id === auth()->id() ? 'bg-primary text-white' : 'bg-light' }}" style="max-width:75%;">
                    <p class="mb-1">{{ $message->body }}</p>
                    <small class="{{ $message->sender_id === auth()->id() ? 'opacity-75' : 'text-muted' }}">{{ $message->created_at->format('M d, h:i A') }}</small>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-5">No messages yet. Start the conversation below.</div>
        @endforelse
    </div>
    <div class="card-footer bg-white">
        <form method="POST" action="{{ route('messages.store', $contact) }}">
            @csrf
            <div class="input-group">
                <textarea class="form-control @error('body') is-invalid @enderror" name="body" rows="2" placeholder="Type your message..." required>{{ old('body') }}</textarea>
                <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Send</button>
            </div>
            @error('body')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
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
