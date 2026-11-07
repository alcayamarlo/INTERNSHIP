@extends('layouts.app')

@section('title', 'Chat with ' . $contact->name)

@section('content')
@php
    $contactAvatar = \App\Support\Avatar::for($contact);

    // Messenger-style time divider: today "3:07 PM", this week "Fri 3:07 PM", older "Nov 2, 3:07 PM".
    $dividerLabel = function ($at) {
        $at = $at->copy()->timezone(config('app.timezone'));

        return match (true) {
            $at->isToday() => $at->format('g:i A'),
            $at->greaterThanOrEqualTo(now()->subDays(6)->startOfDay()) => $at->format('D g:i A'),
            $at->isCurrentYear() => $at->format('M j, g:i A'),
            default => $at->format('M j, Y, g:i A'),
        };
    };
    $previousAt = null;
@endphp
<div class="msgr msgr--chat">
    @include('messages.partials.sidebar')

    <section class="msgr-chat messenger-shell" aria-label="Chat with {{ $contact->name }}">
        <div class="messenger-header">
            <div class="messenger-contact">
                <a href="{{ route('messages.index') }}" class="msgr-back" aria-label="Back to conversations"><i class="bi bi-arrow-left" aria-hidden="true"></i></a>
                <span class="msgr-avatar" aria-hidden="true"><x-avatar :avatar="$contactAvatar" /></span>
                <div>
                    <div class="messenger-name">{{ $contact->name }}</div>
                    <div class="messenger-role">{{ $contact->role->label() }}</div>
                </div>
            </div>
            @include('partials.report-button', ['type' => 'user', 'id' => $contact->id, 'label' => $contact->name])
        </div>

        @include('messages.partials.pinned', ['pinned' => $pinned])

    <div class="messenger-thread" id="messageThread" data-thread-user-id="{{ $contact->id }}">
        @forelse($messages as $message)
            {{-- A time divider opens the thread and every gap of an hour or more. --}}
            @if($previousAt === null || abs($previousAt->diffInMinutes($message->created_at)) >= 60)
                <div class="messenger-divider"><time datetime="{{ $message->created_at->toIso8601String() }}">{{ $dividerLabel($message->created_at) }}</time></div>
            @endif
            @php $previousAt = $message->created_at; @endphp
            @include('messages.partials.message', ['message' => $message])
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
            <input type="hidden" name="reply_to_id" value="">
            {{-- "Replying to …" bar, shown after clicking Reply on a message --}}
            <div class="msgr-replying w-100" data-replying hidden>
                <div class="msgr-replying-main">
                    <div class="msgr-replying-label">Replying to <strong data-replying-name></strong></div>
                    <div class="msgr-replying-text" data-replying-text></div>
                </div>
                <button type="button" class="msgr-replying-cancel" data-replying-cancel aria-label="Cancel reply"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </div>
            <textarea class="messenger-input @error('body') is-invalid @enderror" name="body" rows="1" placeholder="Type your message..." aria-label="Message" required>{{ old('body', \Illuminate\Support\Str::limit((string) request('draft'), 1000, '')) }}</textarea>
            <button type="submit" class="messenger-send"><i class="bi bi-send-fill"></i> Send</button>
            <div class="messenger-feedback w-100" role="status" aria-live="polite"></div>
            @error('body')
                <div class="invalid-feedback d-block w-100">{{ $message }}</div>
            @enderror
        </form>
    </div>
    </section>
</div>

{{-- Forward: pick who gets a copy of the message --}}
<div class="modal fade" id="msgrForwardModal" tabindex="-1" aria-labelledby="msgrForwardTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="msgrForwardTitle">Forward message</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="msgr-forward-preview" data-forward-preview></p>
                <div class="msgr-forward-feedback small" role="status" aria-live="polite" data-forward-feedback></div>
                @if($forwardTargets->isEmpty())
                    <p class="text-muted mb-0">There is nobody you can forward messages to yet.</p>
                @else
                    <ul class="msgr-forward-list">
                        @foreach($forwardTargets as $person)
                            <li>
                                <span class="msgr-avatar" aria-hidden="true"><x-avatar :avatar="\App\Support\Avatar::for($person)" /></span>
                                <span class="msgr-forward-name">{{ $person->name }}<small>{{ $person->role->label() }}</small></span>
                                <button type="button" class="btn btn-sm btn-primary" data-forward-to="{{ $person->id }}">Send</button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const thread = document.getElementById('messageThread');
    if (thread) thread.scrollTop = thread.scrollHeight;

    const messageForm = document.querySelector('.messenger-form');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const jsonHeaders = { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf };

    async function sendJson(url, method, body) {
        const response = await fetch(url, { method, headers: jsonHeaders, body: body ? JSON.stringify(body) : undefined });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            const firstError = payload.errors ? Object.values(payload.errors).flat()[0] : null;
            throw new Error(firstError || payload.message || 'Something went wrong. Please try again.');
        }
        return payload;
    }

    // ---------- rows: every message is rendered by the server (messages/partials/message.blade.php) ----------
    const toElement = (html) => {
        const template = document.createElement('template');
        template.innerHTML = html.trim();
        return template.content.firstElementChild;
    };

    // Messenger-style divider when an hour or more passed since the last message.
    function addDividerBefore(atIso) {
        const rows = thread.querySelectorAll('.messenger-row[data-at]');
        const last = rows.length ? new Date(rows[rows.length - 1].dataset.at) : null;
        const at = new Date(atIso);
        if (last && at - last < 60 * 60 * 1000) return;
        const divider = document.createElement('div');
        divider.className = 'messenger-divider';
        const time = document.createElement('time');
        time.dateTime = at.toISOString();
        time.textContent = at.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        divider.append(time);
        thread.append(divider);
    }

    function replacePinnedBar(html) {
        const bar = document.getElementById('msgrPinned');
        if (bar && typeof html === 'string') bar.replaceWith(toElement(html));
    }

    // Also used by resources/js/app.js for messages and changes that arrive live.
    window.sbChat = {
        append(html) {
            const row = toElement(html);
            if (!thread || !row || thread.querySelector('[data-message-id="' + row.dataset.messageId + '"]')) return;
            thread.querySelector('.messenger-empty')?.remove();
            addDividerBefore(row.dataset.at);
            thread.append(row);
            thread.scrollTop = thread.scrollHeight;
        },
        apply(payload) {
            const current = thread?.querySelector('[data-message-id="' + payload.id + '"]');
            if (current) {
                if (payload.html) {
                    const row = toElement(payload.html);
                    row.classList.toggle('show-time', current.classList.contains('show-time'));
                    current.replaceWith(row);
                } else {
                    current.remove();
                }
            }
            replacePinnedBar(payload.pinned_html);
        },
        async load(rowUrl, mode) {
            const response = await fetch(rowUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) return;
            const payload = await response.json();
            if (mode === 'append') {
                if (payload.html) this.append(payload.html);
                replacePinnedBar(payload.pinned_html);
            } else {
                this.apply(payload);
            }
        },
    };

    // ---------- reply ----------
    const replying = document.querySelector('[data-replying]');
    function startReply(row) {
        if (!messageForm || messageForm.hidden) return;
        messageForm.elements.namedItem('reply_to_id').value = row.dataset.messageId;
        replying.querySelector('[data-replying-name]').textContent = row.dataset.senderName;
        replying.querySelector('[data-replying-text]').textContent = row.querySelector('.messenger-message')?.textContent.trim() || '';
        replying.hidden = false;
        messageForm.querySelector('.messenger-input').focus();
    }
    function cancelReply() {
        if (!messageForm) return;
        messageForm.elements.namedItem('reply_to_id').value = '';
        replying.hidden = true;
    }
    document.querySelector('[data-replying-cancel]')?.addEventListener('click', cancelReply);

    // ---------- jump to a quoted or pinned message ----------
    document.addEventListener('click', (event) => {
        const jump = event.target.closest('[data-jump-to]');
        if (!jump) return;
        const target = document.getElementById('message-' + jump.dataset.jumpTo);
        if (!target) return;
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        target.classList.remove('is-flash');
        void target.offsetWidth;
        target.classList.add('is-flash');
    });

    // ---------- reaction picker: one open at a time, closes on outside click or Escape ----------
    const closePickers = (except) => thread?.querySelectorAll('.msgr-react-picker:not([hidden])').forEach((picker) => {
        if (picker !== except) {
            picker.hidden = true;
            picker.closest('.messenger-row')?.classList.remove('has-open-tools');
        }
    });
    document.addEventListener('click', (event) => {
        if (!event.target.closest('.msgr-react, .msgr-reactions')) closePickers();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closePickers();
    });

    // ---------- forward ----------
    const forwardModalElement = document.getElementById('msgrForwardModal');
    const forwardModal = forwardModalElement && window.bootstrap ? bootstrap.Modal.getOrCreateInstance(forwardModalElement) : null;
    let forwardUrl = null;
    forwardModalElement?.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-forward-to]');
        if (!button || !forwardUrl) return;
        const feedback = forwardModalElement.querySelector('[data-forward-feedback]');
        button.disabled = true;
        try {
            const payload = await sendJson(forwardUrl, 'POST', { to: Number(button.dataset.forwardTo) });
            feedback.className = 'msgr-forward-feedback small text-success';
            feedback.textContent = payload.message;
            button.textContent = 'Sent';
        } catch (error) {
            feedback.className = 'msgr-forward-feedback small text-danger';
            feedback.textContent = error.message;
            button.disabled = false;
        }
    });

    // ---------- message tools ----------
    thread?.addEventListener('click', async (event) => {
        const row = event.target.closest('.messenger-row');
        const tools = row?.querySelector('.msgr-tools');
        if (!row || !tools) return;

        // Click (or Enter) on a bubble shows its date and time.
        if (event.target.closest('.messenger-bubble') && !event.target.closest('button, a, textarea, .msgr-edit')) {
            row.classList.toggle('show-time');
            return;
        }

        if (event.target.closest('[data-msg-react-open]')) {
            const picker = row.querySelector('.msgr-react-picker');
            if (!picker) return;
            closePickers(picker);
            picker.hidden = !picker.hidden;
            row.classList.toggle('has-open-tools', !picker.hidden);
            if (!picker.hidden) picker.querySelector('button')?.focus();
            return;
        }

        const reaction = event.target.closest('[data-msg-react]');
        if (reaction) {
            try {
                window.sbChat.apply(await sendJson(tools.dataset.reactUrl, 'POST', { emoji: reaction.dataset.msgReact }));
            } catch (error) {
                window.alert(error.message);
            }
            return;
        }

        if (event.target.closest('[data-msg-reply]')) {
            startReply(row);
            return;
        }

        if (event.target.closest('[data-msg-pin]')) {
            try {
                window.sbChat.apply(await sendJson(tools.dataset.pinUrl, 'POST'));
            } catch (error) {
                window.alert(error.message);
            }
            return;
        }

        if (event.target.closest('[data-msg-forward]')) {
            forwardUrl = tools.dataset.forwardUrl;
            forwardModalElement.querySelector('[data-forward-preview]').textContent = row.querySelector('.messenger-message')?.textContent.trim() || '';
            forwardModalElement.querySelector('[data-forward-feedback]').textContent = '';
            forwardModalElement.querySelectorAll('[data-forward-to]').forEach((button) => { button.disabled = false; button.textContent = 'Send'; });
            forwardModal?.show();
            return;
        }

        if (event.target.closest('[data-msg-remove]')) {
            if (!window.confirm('Remove this message for you? The other person will still see it.')) return;
            try {
                await sendJson(tools.dataset.removeUrl, 'DELETE');
                row.remove();
                window.sbChat.load(row.dataset.rowUrl, 'refresh'); // refreshes the pinned bar
            } catch (error) {
                window.alert(error.message);
            }
            return;
        }

        if (event.target.closest('[data-msg-unsend]')) {
            if (!window.confirm('Unsend for everyone? This message will be removed for both of you.')) return;
            try {
                await sendJson(tools.dataset.unsendUrl, 'DELETE');
                window.sbChat.load(row.dataset.rowUrl, 'refresh');
            } catch (error) {
                window.alert(error.message);
            }
            return;
        }

        if (event.target.closest('[data-msg-edit]')) {
            const text = row.querySelector('.messenger-message');
            if (!text || row.querySelector('.msgr-edit')) return;
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
                    await sendJson(tools.dataset.updateUrl, 'PUT', { body });
                    window.sbChat.load(row.dataset.rowUrl, 'refresh');
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

    // Keyboard: Enter or Space on a bubble shows its date and time.
    thread?.addEventListener('keydown', (event) => {
        const bubble = event.target.closest('.messenger-bubble');
        if (!bubble || event.target !== bubble || (event.key !== 'Enter' && event.key !== ' ')) return;
        event.preventDefault();
        bubble.closest('.messenger-row').classList.toggle('show-time');
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
            if (event.key === 'Escape') cancelReply();
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

                if (payload.html) window.sbChat.append(payload.html);
                input.value = '';
                cancelReply();
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
