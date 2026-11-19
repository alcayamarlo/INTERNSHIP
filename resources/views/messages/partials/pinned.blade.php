{{-- Pinned messages bar at the top of a chat (newest pin first). Needs: $pinned (Message collection). --}}
<div class="msgr-pinned" id="msgrPinned" @if($pinned->isEmpty()) hidden @endif>
    @if($first = $pinned->first())
        <i class="bi bi-pin-angle-fill msgr-pinned-icon" aria-hidden="true"></i>
        <button type="button" class="msgr-pinned-main" data-jump-to="{{ $first->id }}" aria-label="Show the pinned message">
            <span class="msgr-pinned-who">{{ $first->sender_id === auth()->id() ? 'You' : $first->sender?->name }}</span>
            <span class="msgr-pinned-text">{{ \Illuminate\Support\Str::limit($first->displayBody(auth()->id()), 120) }}</span>
        </button>
        @if($pinned->count() > 1)
            <button type="button" class="msgr-pinned-more" data-bs-toggle="modal" data-bs-target="#msgrPinnedModal">View all <span>({{ $pinned->count() }})</span></button>
            <div class="modal fade msgr-pinned-modal" id="msgrPinnedModal" tabindex="-1" aria-labelledby="msgrPinnedModalTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="msgrPinnedModalTitle">Pinned messages</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-2">
                            @foreach($pinned as $pin)
                                <button type="button" class="msgr-pinned-item" data-jump-to="{{ $pin->id }}" data-bs-dismiss="modal">
                                    <i class="bi bi-pin-angle-fill" aria-hidden="true"></i>
                                    <span class="msgr-pinned-item-body">
                                        <strong>{{ $pin->sender_id === auth()->id() ? 'You' : $pin->sender?->name }}</strong>
                                        <span>{{ \Illuminate\Support\Str::limit($pin->displayBody(auth()->id()), 160) }}</span>
                                    </span>
                                    @php($at = $pin->created_at)
                                    <small class="msgr-pinned-item-time">
                                        @if($at->isToday()) {{ $at->format('g:i A') }}
                                        @elseif($at->isYesterday()) Yesterday, {{ $at->format('g:i A') }}
                                        @elseif($at->isCurrentYear()) {{ $at->format('M j, g:i A') }}
                                        @else {{ $at->format('M j, Y, g:i A') }}
                                        @endif
                                    </small>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
