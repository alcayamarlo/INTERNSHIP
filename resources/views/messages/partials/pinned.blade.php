{{-- Pinned messages bar at the top of a chat (newest pin first). Needs: $pinned (Message collection). --}}
<div class="msgr-pinned" id="msgrPinned" @if($pinned->isEmpty()) hidden @endif>
    @if($first = $pinned->first())
        <i class="bi bi-pin-angle-fill msgr-pinned-icon" aria-hidden="true"></i>
        <button type="button" class="msgr-pinned-main" data-jump-to="{{ $first->id }}" aria-label="Show the pinned message">
            <span class="msgr-pinned-who">{{ $first->sender_id === auth()->id() ? 'You' : $first->sender?->name }}</span>
            <span class="msgr-pinned-text">{{ \Illuminate\Support\Str::limit($first->displayBody(auth()->id()), 120) }}</span>
        </button>
        @if($pinned->count() > 1)
            <div class="dropdown">
                <button type="button" class="msgr-pinned-more" data-bs-toggle="dropdown" aria-expanded="false">{{ $pinned->count() }} pinned <i class="bi bi-chevron-down" aria-hidden="true"></i></button>
                <ul class="dropdown-menu dropdown-menu-end msgr-pinned-list">
                    @foreach($pinned as $pin)
                        <li>
                            <button type="button" class="dropdown-item" data-jump-to="{{ $pin->id }}">
                                <strong>{{ $pin->sender_id === auth()->id() ? 'You' : $pin->sender?->name }}</strong>
                                <span>{{ \Illuminate\Support\Str::limit($pin->displayBody(auth()->id()), 70) }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif
</div>
