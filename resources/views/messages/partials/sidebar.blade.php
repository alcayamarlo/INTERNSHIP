{{-- Messenger-style list: conversations, locked companies, and people you can start a chat with. --}}
@php $activeId = isset($contact) ? $contact->id : null; @endphp
<aside class="msgr-list" aria-label="Conversations">
    <div class="msgr-list-head">
        <h2>Chats</h2>
    </div>

    <div class="msgr-list-body">
        @forelse($conversations as $contactId => $thread)
            @php
                $person = $people->get($contactId);
                $latest = $thread->first();
                $unread = $thread->where('receiver_id', auth()->id())->whereNull('read_at')->count();
                $locked = $lockedReasons->get($contactId);
            @endphp
            @if($person)
                <a href="{{ route('messages.show', $person) }}" class="msgr-item {{ $activeId === $person->id ? 'is-active' : '' }} {{ $unread ? 'is-unread' : '' }}" @if($activeId === $person->id) aria-current="page" @endif>
                    <span class="msgr-avatar" aria-hidden="true"><x-avatar :avatar="\App\Support\Avatar::for($person)" /></span>
                    <span class="msgr-item-main">
                        <span class="msgr-item-top">
                            <strong>{{ $person->name }}</strong>
                            <time datetime="{{ $latest->created_at->toIso8601String() }}">{{ $latest->created_at->shortAbsoluteDiffForHumans() }}</time>
                        </span>
                        <span class="msgr-item-preview">
                            @if($locked)
                                <i class="bi bi-lock-fill" aria-hidden="true" title="{{ $locked }}"></i>
                            @endif
                            {{ ($latest->sender_id === auth()->id() ? 'You: ' : '').Str::limit($latest->displayBody(auth()->id()), 40) }}
                        </span>
                        @if($locked)
                            <span class="msgr-item-note" data-chat-locked>{{ $locked }}</span>
                        @endif
                    </span>
                    @if($unread)
                        <span class="msgr-unread" aria-label="{{ $unread }} unread">{{ $unread > 9 ? '9+' : $unread }}</span>
                    @endif
                </a>
            @endif
        @empty
            <x-empty-state icon="bi-chat-dots" title="No conversations yet" message="Start a new one from the contact list." />
        @endforelse

        @if($lockedCompanies->isNotEmpty())
            <div class="msgr-section">Companies you applied to</div>
            @foreach($lockedCompanies as $row)
                <div class="msgr-item is-locked" data-chat-locked>
                    <span class="msgr-avatar" aria-hidden="true"><i class="bi bi-lock-fill"></i></span>
                    <span class="msgr-item-main">
                        <span class="msgr-item-top"><strong>{{ $row['user']->employer?->company_name ?? $row['user']->name }}</strong></span>
                        <span class="msgr-item-note">{{ $row['reason'] }}</span>
                    </span>
                </div>
            @endforeach
        @endif

        @php $newContacts = $contacts->reject(fn ($c) => $conversations->has($c->id)); @endphp
        @if($newContacts->count())
            <div class="msgr-section">Start new</div>
            @foreach($newContacts as $person)
                <a href="{{ route('messages.show', $person) }}" class="msgr-item {{ $activeId === $person->id ? 'is-active' : '' }}">
                    <span class="msgr-avatar" aria-hidden="true"><x-avatar :avatar="\App\Support\Avatar::for($person)" /></span>
                    <span class="msgr-item-main">
                        <span class="msgr-item-top"><strong>{{ $person->name }}</strong></span>
                        <span class="msgr-item-preview">{{ $person->role->label() }}</span>
                    </span>
                </a>
            @endforeach
        @endif
    </div>
</aside>
