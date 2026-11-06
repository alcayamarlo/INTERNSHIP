@extends('layouts.app')

@section('title', 'Chat with ' . $contact->name)

@section('content')
<div class="msgr msgr--chat">
    @include('messages.partials.sidebar')

    <section class="msgr-chat messenger-shell" aria-label="Chat with {{ $contact->name }}">
        <div class="messenger-header">
            <div class="messenger-contact">
                <a href="{{ route('messages.index') }}" class="msgr-back" aria-label="Back to conversations"><i class="bi bi-arrow-left" aria-hidden="true"></i></a>
                <span class="msgr-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($contact->name, 0, 1)) }}</span>
                <div>
                    <div class="messenger-name">{{ $contact->name }}</div>
                    <div class="messenger-role">{{ $contact->role->label() }}</div>
                </div>
            </div>
            @include('partials.report-button', ['type' => 'user', 'id' => $contact->id, 'label' => $contact->name])
        </div>

    <div class="messenger-thread" id="messageThread" data-thread-user-id="{{ $contact->id }}">
        @forelse($messages as $message)
            @php $mine = $message->sender_id === auth()->id(); $gone = $message->isHidden() || $message->isUnsent(); @endphp
            <div class="messenger-row {{ $mine ? 'outgoing' : 'incoming' }}" data-message-id="{{ $message->id }}">
                @if($mine && ! $message->isHidden())
                    @include('messages.partials.actions', ['message' => $message])
                @endif
                <div class="messenger-bubble" tabindex="0" title="Click to show the date and time">
                    <p class="messenger-message {{ $gone ? 'fst-italic text-muted' : '' }}">{{ $message->displayBody(auth()->id()) }}</p>
                    <span class="messenger-time">
                        {{ $message->created_at->format('M d, h:i A') }}
                        <span data-edited-label>@if($message->isEdited() && ! $gone) &middot; edited @endif</span>
                        @if(! $mine && ! $gone)
                            <button type="button" class="btn btn-link btn-sm p-0 ms-1 align-baseline text-muted" data-report-type="message" data-report-id="{{ $message->id }}" data-report-label="this message" aria-label="Report this message"><i class="bi bi-flag" aria-hidden="true"></i></button>
                        @endif
                    </span>
                </div>
            </div>
        @empty
            <div class="messenger-empty">{{ $canSend ? 'No messages yet. Start the conversation below.' : 'No messages yet.' }}</div>
        @endforelse
    </div>

    <div class="messenger-composer">
        {{-- Locked chats keep old messages read-only. The form stays hidden until the chat opens
             (live, when the application is marked Reviewed); the server checks every send anyway. --}}
        <p class="text-muted small mb-0" role="status" data-chat-lock-note @if($canSend) hidden @endif><i class="bi bi-lock" aria-hidden="true"></i> {{ $sendBlockedReason }}</p>
        <form method="POST" action="{{ route('messages.store', $contact) }}" class="messenger-form" data-no-loading="true" data-chat-contact-id="{{ $contact->id }}" @unless($canSend) hidden @endunless>
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
    </section>
</div>
@endsection

@push('scripts')
<script>
    const thread = document.getElementById('messageThread');
    if (thread) thread.scrollTop = thread.scrollHeight;

    const messageForm = document.querySelector('.messenger-form');

    // Only the message shows by default; click (or Enter) on a bubble to reveal its date and time.
    const toggleTime = (event) => {
        const bubble = event.target.closest('.messenger-bubble');
        if (!bubble || event.target.closest('button, a, textarea, .msgr-edit')) return;
        if (event.type === 'keydown') {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
        }
        bubble.closest('.messenger-row').classList.toggle('show-time');
    };
    thread?.addEventListener('click', toggleTime);
    thread?.addEventListener('keydown', toggleTime);

    // ---------- edit / unsend your own messages ----------
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const jsonHeaders = { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf };

    function actionsHtml(id, updateUrl, unsendUrl, removeUrl) {
        return '<div class="dropdown msgr-actions" data-update-url="' + updateUrl + '" data-unsend-url="' + unsendUrl + '" data-remove-url="' + removeUrl + '">'
            + '<button type="button" class="msgr-more" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Message options"><i class="bi bi-three-dots-vertical" aria-hidden="true"></i></button>'
            + '<ul class="dropdown-menu">'
            + '<li><button type="button" class="dropdown-item" data-msg-edit><i class="bi bi-pencil" aria-hidden="true"></i> Edit</button></li>'
            + '<li><button type="button" class="dropdown-item" data-msg-remove><i class="bi bi-eye-slash" aria-hidden="true"></i> Remove for you</button></li>'
            + '<li><button type="button" class="dropdown-item text-danger" data-msg-unsend><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Unsend for everyone</button></li>'
            + '</ul></div>';
    }

    async function sendJson(url, method, body) {
        const response = await fetch(url, { method, headers: jsonHeaders, body: body ? JSON.stringify(body) : undefined });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            const firstError = payload.errors ? Object.values(payload.errors).flat()[0] : null;
            throw new Error(firstError || payload.message || 'Something went wrong. Please try again.');
        }
        return payload;
    }

    thread?.addEventListener('click', async (event) => {
        const row = event.target.closest('.messenger-row.outgoing');
        const actions = row?.querySelector('.msgr-actions');
        if (!row || !actions) return;
        const text = row.querySelector('.messenger-message');

        if (event.target.closest('[data-msg-remove]')) {
            if (!window.confirm('Remove this message for you? The other person will still see it.')) return;
            try {
                await sendJson(actions.dataset.removeUrl, 'DELETE');
                row.remove();
            } catch (error) {
                window.alert(error.message);
            }
            return;
        }

        if (event.target.closest('[data-msg-unsend]')) {
            if (!window.confirm('Unsend for everyone? This message will be removed for both of you.')) return;
            try {
                const payload = await sendJson(actions.dataset.unsendUrl, 'DELETE');
                text.textContent = payload.message;
                text.classList.add('fst-italic', 'text-muted');
                row.querySelector('[data-edited-label]').textContent = '';
                actions.querySelectorAll('[data-msg-edit], [data-msg-unsend]').forEach((item) => item.closest('li').remove());
            } catch (error) {
                window.alert(error.message);
            }
            return;
        }

        if (event.target.closest('[data-msg-edit]')) {
            if (row.querySelector('.msgr-edit')) return;
            const original = text.textContent;
            const form = document.createElement('div');
            form.className = 'msgr-edit';
            form.innerHTML = '<textarea class="form-control form-control-sm" rows="2" maxlength="5000" aria-label="Edit message"></textarea>'
                + '<div class="msgr-edit-error text-danger small" role="alert"></div>'
                + '<div class="msgr-edit-buttons"><button type="button" class="btn btn-sm btn-light" data-edit-cancel>Cancel</button><button type="button" class="btn btn-sm btn-primary" data-edit-save>Save</button></div>';
            const field = form.querySelector('textarea');
            field.value = original;
            text.hidden = true;
            text.after(form);
            field.focus();

            const close = () => { form.remove(); text.hidden = false; };
            form.querySelector('[data-edit-cancel]').addEventListener('click', close);
            const save = async () => {
                const body = field.value.trim();
                if (!body) { form.querySelector('.msgr-edit-error').textContent = 'Message cannot be empty.'; return; }
                if (body === original) { close(); return; }
                try {
                    const payload = await sendJson(actions.dataset.updateUrl, 'PUT', { body });
                    text.textContent = payload.message;
                    row.querySelector('[data-edited-label]').textContent = payload.edited ? ' · edited' : '';
                    close();
                } catch (error) {
                    form.querySelector('.msgr-edit-error').textContent = error.message;
                }
            };
            form.querySelector('[data-edit-save]').addEventListener('click', save);
            field.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) { e.preventDefault(); save(); }
                if (e.key === 'Escape') close();
            });
        }
    });


    // A live "application reviewed" notification for this company unlocks the chat without a reload.
    window.addEventListener('sb:messaging-opened', (event) => {
        if (!messageForm || String(event.detail) !== messageForm.dataset.chatContactId) return;
        messageForm.hidden = false;
        document.querySelector('[data-chat-lock-note]')?.setAttribute('hidden', '');
        const emptyState = document.querySelector('.messenger-empty');
        if (emptyState) emptyState.textContent = 'No messages yet. Start the conversation below.';
    });

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
                row.dataset.messageId = payload.id;
                row.insertAdjacentHTML('beforeend', actionsHtml(payload.id, payload.update_url, payload.unsend_url, payload.remove_url));
                const bubble = document.createElement('div');
                bubble.className = 'messenger-bubble';
                bubble.tabIndex = 0;
                bubble.title = 'Click to show the date and time';
                const message = document.createElement('p');
                message.className = 'messenger-message';
                message.textContent = payload.message;
                const time = document.createElement('span');
                time.className = 'messenger-time';
                time.textContent = payload.created_at;
                const edited = document.createElement('span');
                edited.dataset.editedLabel = '';
                time.append(' ', edited);
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
