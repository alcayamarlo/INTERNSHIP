{{--
    One chat message, Messenger style: the sender's picture (their messages), a reply quote,
    "Forwarded", the bubble, reactions, and hover tools (react, reply, more).
    Rendered on page load and by MessageController for sends and live updates, so all three match.
    Needs: $message, $contact (the other person), $contactAvatar, $reactionEmojis.
--}}
@php
    $me = auth()->id();
    $mine = $message->sender_id === $me;
    $gone = $message->isGone();

    $reply = $message->replyTo;
    $replyUnavailable = $reply && ($reply->isGone() || ($reply->sender_id === $me ? $reply->sender_removed_at : $reply->receiver_removed_at));
    $replyLabel = $reply ? match (true) {
        $mine && $reply->sender_id === $me => 'You replied to yourself',
        $mine => 'You replied to '.$contact->name,
        $reply->sender_id === $me => $contact->name.' replied to you',
        default => $contact->name.' replied to themselves',
    } : null;

    $reactions = $message->reactions;
    $myReaction = $reactions->firstWhere('user_id', $me)?->emoji;
    $reactedBy = $reactions->map(fn ($reaction) => ($reaction->user_id === $me ? 'You' : $reaction->user?->name).' '.$reaction->emoji)->join(', ');
@endphp
<div class="messenger-row {{ $mine ? 'outgoing' : 'incoming' }} {{ $message->isPinned() ? 'is-pinned' : '' }}"
     id="message-{{ $message->id }}"
     data-message-id="{{ $message->id }}"
     data-at="{{ $message->created_at->toIso8601String() }}"
     data-sender-name="{{ $mine ? 'yourself' : $contact->name }}"
     data-row-url="{{ route('messages.row', $message) }}">

    @unless($mine)
        <span class="msgr-msg-avatar" aria-hidden="true"><x-avatar :avatar="$contactAvatar" /></span>
    @endunless

    <div class="msgr-msg">
        @if($reply)
            <div class="msgr-reply-label"><i class="bi bi-reply-fill" aria-hidden="true"></i> {{ $replyLabel }}</div>
            @if($replyUnavailable)
                <div class="msgr-reply-quote is-unavailable">Original message is no longer available.</div>
            @else
                <button type="button" class="msgr-reply-quote" data-jump-to="{{ $reply->id }}" aria-label="Show the original message">{{ \Illuminate\Support\Str::limit($reply->displayBody($me), 90) }}</button>
            @endif
        @endif

        @if($message->isForwarded() && ! $gone)
            <div class="msgr-forwarded"><i class="bi bi-forward-fill" aria-hidden="true"></i> Forwarded</div>
        @endif

        <div class="messenger-bubble" tabindex="0" title="Click to show the date and time">
            <p class="messenger-message {{ $gone ? 'fst-italic text-muted' : '' }}">{{ $message->displayBody($me) }}</p>
            <span class="messenger-time">
                {{ $message->created_at->format('M d, h:i A') }}
                <span data-edited-label>@if($message->isEdited() && ! $gone) &middot; edited @endif</span>
                @if($message->isPinned()) &middot; <i class="bi bi-pin-angle-fill" aria-hidden="true"></i> Pinned @endif
            </span>
        </div>

        @if($reactions->isNotEmpty())
            <button type="button" class="msgr-reactions" data-msg-react-open title="{{ $reactedBy }}" aria-label="Reactions: {{ $reactedBy }}">
                @foreach($reactions->groupBy('emoji') as $emoji => $group)
                    <span aria-hidden="true">{{ $emoji }}</span>
                @endforeach
                @if($reactions->count() > 1)
                    <span class="msgr-reactions-count" aria-hidden="true">{{ $reactions->count() }}</span>
                @endif
            </button>
        @endif
    </div>

    {{-- Hover tools. Handled by the script in messages/show.blade.php. --}}
    <div class="msgr-tools"
         data-update-url="{{ route('messages.update', $message) }}"
         data-unsend-url="{{ route('messages.unsend', $message) }}"
         data-remove-url="{{ route('messages.remove', $message) }}"
         data-react-url="{{ route('messages.react', $message) }}"
         data-pin-url="{{ route('messages.pin', $message) }}"
         data-forward-url="{{ route('messages.forward', $message) }}">
        @unless($gone)
            <div class="msgr-react">
                <button type="button" class="msgr-tool" data-msg-react-open aria-label="React" aria-haspopup="true"><i class="bi bi-emoji-smile" aria-hidden="true"></i></button>
                <div class="msgr-react-picker" role="group" aria-label="Pick a reaction" hidden>
                    @foreach($reactionEmojis as $emoji)
                        <button type="button" class="{{ $myReaction === $emoji ? 'is-mine' : '' }}" data-msg-react="{{ $emoji }}" aria-label="React with {{ $emoji }}" aria-pressed="{{ $myReaction === $emoji ? 'true' : 'false' }}">{{ $emoji }}</button>
                    @endforeach
                </div>
            </div>
            <button type="button" class="msgr-tool" data-msg-reply aria-label="Reply"><i class="bi bi-reply-fill" aria-hidden="true"></i></button>
        @endunless

        <div class="dropdown msgr-actions">
            <button type="button" class="msgr-tool msgr-more" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More options"><i class="bi bi-three-dots-vertical" aria-hidden="true"></i></button>
            <ul class="dropdown-menu {{ $mine ? 'dropdown-menu-end' : '' }}">
                @if($mine && ! $gone)
                    <li><button type="button" class="dropdown-item" data-msg-edit><i class="bi bi-pencil" aria-hidden="true"></i> Edit</button></li>
                @endif
                <li><button type="button" class="dropdown-item" data-msg-remove><i class="bi bi-eye-slash" aria-hidden="true"></i> Remove for you</button></li>
                @if($mine && ! $gone)
                    <li><button type="button" class="dropdown-item text-danger" data-msg-unsend><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Unsend for everyone</button></li>
                @endif
                @unless($gone)
                    <li><button type="button" class="dropdown-item" data-msg-forward><i class="bi bi-forward" aria-hidden="true"></i> Forward</button></li>
                    <li><button type="button" class="dropdown-item" data-msg-pin><i class="bi bi-pin-angle" aria-hidden="true"></i> {{ $message->isPinned() ? 'Unpin' : 'Pin' }}</button></li>
                @endunless
                @if(! $mine && ! $gone)
                    <li><button type="button" class="dropdown-item" data-report-type="message" data-report-id="{{ $message->id }}" data-report-label="this message"><i class="bi bi-flag" aria-hidden="true"></i> Report</button></li>
                @endif
            </ul>
        </div>
    </div>
</div>
