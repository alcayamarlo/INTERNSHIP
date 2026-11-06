{{-- Menu on your own messages (handled by the script in messages/show.blade.php):
     Edit, Remove for you (only your copy), Unsend for everyone. An unsent message only offers Remove for you. --}}
<div class="dropdown msgr-actions" data-update-url="{{ route('messages.update', $message) }}" data-unsend-url="{{ route('messages.unsend', $message) }}" data-remove-url="{{ route('messages.remove', $message) }}">
    <button type="button" class="msgr-more" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Message options"><i class="bi bi-three-dots-vertical" aria-hidden="true"></i></button>
    <ul class="dropdown-menu">
        @unless($message->isUnsent())
            <li><button type="button" class="dropdown-item" data-msg-edit><i class="bi bi-pencil" aria-hidden="true"></i> Edit</button></li>
        @endunless
        <li><button type="button" class="dropdown-item" data-msg-remove><i class="bi bi-eye-slash" aria-hidden="true"></i> Remove for you</button></li>
        @unless($message->isUnsent())
            <li><button type="button" class="dropdown-item text-danger" data-msg-unsend><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Unsend for everyone</button></li>
        @endunless
    </ul>
</div>
