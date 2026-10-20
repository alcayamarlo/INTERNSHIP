@extends('layouts.app')

@section('title', 'Chat with ' . $contact->name)

@section('content')
<x-page-header :title="'Chat with '.$contact->name" :subtitle="$contact->role->label()">
    <a href="{{ route('messages.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Messages</a>
</x-page-header>

<div class="messenger-shell">
    <div class="messenger-header">
        <div class="messenger-contact">
            <span class="messenger-avatar" aria-hidden="true"><i class="bi bi-person-circle"></i></span>
            <div>
                <div class="messenger-name">{{ $contact->name }}</div>
                <div class="messenger-role">{{ $contact->role->label() }}</div>
            </div>
        </div>
    </div>

    <div class="messenger-thread" id="messageThread" data-thread-user-id="{{ $contact->id }}">
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
        <form method="POST" action="{{ route('messages.store', $contact) }}" class="messenger-form" data-no-loading="true">
            @csrf
            <input type="hidden" name="submission_id" value="{{ $submissionId }}">
            <textarea class="messenger-input @error('body') is-invalid @enderror" name="body" rows="1" placeholder="Type your message..." required>{{ old('body') }}</textarea>
            <button type="submit" class="messenger-send"><i class="bi bi-send-fill"></i> Send</button>
            <div class="messenger-feedback w-100" role="status" aria-live="polite"></div>
            @error('body')
                <div class="invalid-feedback d-block w-100">{{ $message }}</div>
            @enderror
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const thread = document.getElementById('messageThread');
    if (thread) thread.scrollTop = thread.scrollHeight;

    const messageForm = document.querySelector('.messenger-form');
    if (messageForm) {
        const input = messageForm.querySelector('.messenger-input');
        const sendButton = messageForm.querySelector('.messenger-send');

        input.addEventListener('keydown', event => {
            if (event.key !== 'Enter' || event.shiftKey || event.isComposing) return;

            event.preventDefault();
            if (input.value.trim() && !sendButton.disabled && messageForm.dataset.submitting !== 'true') {
                messageForm.requestSubmit();
            }
        });

        messageForm.addEventListener('submit', async event => {
            event.preventDefault();
            if (messageForm.dataset.submitting === 'true') {
                return;
            }

            messageForm.dataset.submitting = 'true';
            const feedback = messageForm.querySelector('.messenger-feedback');
            const originalButton = sendButton.innerHTML;
            const body = input.value.trim();
            if (!body) {
                messageForm.dataset.submitting = 'false';
                return;
            }
            feedback.textContent = '';
            feedback.classList.remove('text-danger');

            if (sendButton) {
                sendButton.disabled = true;
                sendButton.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Sending...';
            }

            try {
                const response = await fetch(messageForm.action, {
                    method: 'POST',
                    body: new FormData(messageForm),
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const payload = await response.json().catch(() => ({ message: response.status === 413 ? 'The file is too large to upload.' : `Request failed (HTTP ${response.status}). Please try again.` }));
                if (!response.ok) {
                    const firstError = payload.errors ? Object.values(payload.errors).flat()[0] : null;
                    throw new Error(firstError || payload.message || 'Unable to send message.');
                }

                messageForm.elements.namedItem('submission_id').value = payload.submission_id;
                if (payload.duplicate) {
                    feedback.textContent = 'This message was already sent.';
                    input.value = '';
                    return;
                }

                const emptyState = thread.querySelector('.messenger-empty');
                if (emptyState) emptyState.remove();
                const row = document.createElement('div');
                row.className = 'messenger-row outgoing';
                const bubble = document.createElement('div');
                bubble.className = 'messenger-bubble';
                const message = document.createElement('p');
                message.className = 'messenger-message';
                message.textContent = payload.message;
                const time = document.createElement('span');
                time.className = 'messenger-time';
                time.textContent = payload.created_at;
                bubble.append(message, time);
                row.append(bubble);
                thread.append(row);
                thread.scrollTop = thread.scrollHeight;
                input.value = '';
            } catch (error) {
                feedback.classList.add('text-danger');
                feedback.textContent = error.message || 'Unable to send message.';
            } finally {
                messageForm.dataset.submitting = 'false';
                if (sendButton) {
                    sendButton.disabled = false;
                    sendButton.innerHTML = originalButton;
                    sendButton.removeAttribute('aria-busy');
                    sendButton.querySelectorAll('.sb-submit-spinner').forEach(spinner => spinner.remove());
                }
            }
        });
    }
</script>
@endpush
