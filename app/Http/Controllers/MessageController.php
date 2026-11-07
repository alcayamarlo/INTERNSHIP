<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\MessageUpdated;
use App\Http\Requests\Messaging\StoreMessageRequest;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\MessagingEligibility;
use App\Support\Avatar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLog,
        private MessagingEligibility $eligibility,
    ) {}

    public function index()
    {
        return view('messages.index', $this->sidebarData());
    }

    /** What the conversation list (left side of the Messenger layout) needs, shared by index and show. */
    private function sidebarData(): array
    {
        $userId = Auth::id();

        $conversations = Message::query()
            ->where(fn ($query) => $query->where('sender_id', $userId)->orWhere('receiver_id', $userId))
            ->visibleTo($userId)
            ->latest()
            ->get()
            ->groupBy(fn ($message) => $message->sender_id === $userId
                ? $message->receiver_id
                : $message->sender_id);
        $people = User::query()->with(['student', 'coordinator', 'employer'])->whereIn('id', $conversations->keys())->get()->keyBy('id');

        return [
            'conversations' => $conversations,
            'people' => $people,
            // Existing threads stay listed; ones the user can no longer send to show the reason.
            'lockedReasons' => $people->map(fn (User $contact) => Gate::inspect('message.send', $contact))
                ->reject(fn ($response) => $response->allowed())
                ->map(fn ($response) => $response->message()),
            'contacts' => $this->allowedContacts(),
            'lockedCompanies' => $this->lockedCompanies($people->keys()),
        ];
    }

    /**
     * For students: companies they applied to but cannot message yet (no thread yet), so the
     * Messages list can show them with a lock and the reason.
     */
    private function lockedCompanies($threadUserIds)
    {
        $student = Auth::user()->student;
        if (! Auth::user()->isRole(\App\Enums\UserRole::Student) || ! $student) {
            return collect();
        }

        return $student->applications()
            ->with('internship.employer.user')
            ->get()
            ->map(fn ($application) => $application->internship?->employer?->user)
            ->filter()
            ->unique('id')
            ->reject(fn (User $company) => $threadUserIds->contains($company->id))
            ->map(fn (User $company) => ['user' => $company, 'reason' => Gate::inspect('message.send', $company)])
            ->reject(fn (array $row) => $row['reason']->allowed())
            ->map(fn (array $row) => ['user' => $row['user'], 'reason' => $row['reason']->message()])
            ->values();
    }

    public function show(User $user)
    {
        Gate::authorize('message.view', $user);
        $sendAccess = Gate::inspect('message.send', $user);

        $messages = $this->conversationWith($user)
            ->with(['sender', 'replyTo.sender', 'reactions.user'])
            ->orderBy('created_at')
            ->get();

        Message::where('sender_id', $user->id)
            ->where('receiver_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $sidebar = $this->sidebarData();

        return view('messages.show', $sidebar + [
            'contact' => $user,
            'messages' => $messages,
            'pinned' => $messages->filter(fn (Message $message) => $message->isPinned())->sortByDesc('pinned_at')->values(),
            'forwardTargets' => $sidebar['people']->values()->merge($sidebar['contacts'])->unique('id')
                ->filter(fn (User $person) => Gate::allows('message.send', $person))
                ->sortBy('name')->values(),
            'reactionEmojis' => MessageReaction::EMOJIS,
            'submissionId' => (string) Str::uuid(),
            'canSend' => $sendAccess->allowed(),
            'sendBlockedReason' => $sendAccess->message(),
        ]);
    }

    public function store(StoreMessageRequest $request, User $user)
    {
        $cacheKey = 'message-submission:'.Auth::id().':'.$request->validated('submission_id');

        if (! Cache::add($cacheKey, true, now()->addMinutes(10))) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'duplicate' => true,
                    'submission_id' => (string) Str::uuid(),
                ]);
            }

            return back()->with('success', 'Message already sent.');
        }

        // Reply: only to a message in this same chat that you can still see.
        $replyTo = null;
        if ($replyToId = $request->validated('reply_to_id')) {
            $replyTo = $this->conversationWith($user)->whereKey($replyToId)->first();
            if (! $replyTo || $replyTo->isGone()) {
                Cache::forget($cacheKey);
                abort(422, 'You can only reply to a message in this chat.');
            }
        }

        try {
            $message = DB::transaction(function () use ($request, $user, $replyTo) {
                $message = Message::create([
                    'sender_id' => Auth::id(),
                    'receiver_id' => $user->id,
                    'body' => $request->validated('body'),
                    'reply_to_id' => $replyTo?->id,
                ]);

                $this->activityLog->log(Auth::user(), 'message_send', ['receiver_id' => $user->id]);

                return $message;
            });
        } catch (\Throwable $exception) {
            Cache::forget($cacheKey);
            throw $exception;
        }

        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable $exception) {
            // Broadcasting is additive; the message is already saved regardless
            // of whether a live push succeeds (e.g. no Pusher keys configured).
            report($exception);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'id' => $message->id,
                'update_url' => route('messages.update', $message),
                'unsend_url' => route('messages.unsend', $message),
                'remove_url' => route('messages.remove', $message),
                'message' => $request->validated('body'),
                'created_at' => now()->format('M d, h:i A'),
                'submission_id' => (string) Str::uuid(),
                // The rendered message (reply quote, actions) for the open chat to append.
                'html' => $this->renderRow($message, $user),
            ]);
        }

        return back()->with('success', 'Message sent.');
    }

    /** The sender edits their own message while the chat is still open to them. */
    public function update(Request $request, Message $message)
    {
        $this->authorizeOwn($message);
        abort_if($message->isUnsent(), 422, 'This message was unsent.');
        Gate::authorize('message.send', $message->receiver);

        $validated = $request->validate(['body' => ['required', 'string', 'max:5000']], [
            'body.required' => 'Message cannot be empty.',
            'body.max' => 'Message must not exceed 5000 characters.',
        ]);

        if ($validated['body'] !== $message->body) {
            $message->forceFill(['body' => $validated['body'], 'edited_at' => now()])->save();
            $this->activityLog->log(Auth::user(), 'message_edit', ['message_id' => $message->id]);
            $this->broadcastUpdate($message);
        }

        return response()->json(['success' => true, 'message' => $message->body, 'edited' => $message->isEdited()]);
    }

    /** The sender takes a message back. Allowed even when the chat is now locked. */
    public function unsend(Message $message)
    {
        $this->authorizeOwn($message);

        if (! $message->isUnsent()) {
            $message->forceFill(['unsent_at' => now()])->save();
            $this->activityLog->log(Auth::user(), 'message_unsend', ['message_id' => $message->id]);
            $this->broadcastUpdate($message);
        }

        return response()->json(['success' => true, 'message' => $message->displayBody(Auth::id())]);
    }

    /** "Remove for you": clears a message you sent or received from your own view; the other person still sees it. */
    public function removeForMe(Message $message)
    {
        abort_unless($message->isParticipant(Auth::id()), 403);

        $column = $message->sender_id === Auth::id() ? 'sender_removed_at' : 'receiver_removed_at';
        if ($message->{$column} === null) {
            $message->forceFill([$column => now()])->save();
            $this->activityLog->log(Auth::user(), 'message_remove_for_me', ['message_id' => $message->id]);
        }

        return response()->json(['success' => true]);
    }

    /** One message rendered for the signed-in user, plus the chat's pinned bar (used for live updates). */
    public function row(Message $message)
    {
        abort_unless($message->isParticipant(Auth::id()), 403);

        return response()->json($this->rowPayload($message));
    }

    /** React with an emoji. Picking your current reaction again removes it; a new one replaces it. */
    public function react(Request $request, Message $message)
    {
        $this->authorizeAction($message);
        Gate::authorize('message.send', $this->otherPerson($message));

        $emoji = $request->validate(['emoji' => ['required', 'string', Rule::in(MessageReaction::EMOJIS)]])['emoji'];
        $current = $message->reactions()->where('user_id', Auth::id())->first();

        if ($current?->emoji === $emoji) {
            $current->delete();
        } else {
            MessageReaction::updateOrCreate(['message_id' => $message->id, 'user_id' => Auth::id()], ['emoji' => $emoji]);
        }

        $this->broadcastUpdate($message);

        return response()->json($this->rowPayload($message->fresh()));
    }

    /** Pin or unpin a message for both people in the chat. */
    public function pin(Message $message)
    {
        $this->authorizeAction($message);

        $pin = ! $message->isPinned();
        $message->forceFill(['pinned_at' => $pin ? now() : null, 'pinned_by' => $pin ? Auth::id() : null])->save();
        $this->activityLog->log(Auth::user(), $pin ? 'message_pin' : 'message_unpin', ['message_id' => $message->id]);
        $this->broadcastUpdate($message);

        return response()->json($this->rowPayload($message->fresh()) + ['pinned' => $pin]);
    }

    /** Forward a copy of a message to someone you can message. */
    public function forward(Request $request, Message $message)
    {
        $this->authorizeAction($message);
        abort_unless(Message::whereKey($message->id)->visibleTo(Auth::id())->exists(), 404);

        $targetId = $request->validate(['to' => ['required', 'integer', 'exists:users,id']])['to'];
        $target = User::findOrFail($targetId);
        Gate::authorize('message.send', $target);

        $copy = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $target->id,
            'body' => $message->body,
            'forwarded_at' => now(),
        ]);
        $this->activityLog->log(Auth::user(), 'message_forward', ['message_id' => $message->id, 'receiver_id' => $target->id]);

        try {
            broadcast(new MessageSent($copy))->toOthers();
        } catch (\Throwable $exception) {
            report($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'Forwarded to '.$target->name.'.',
            'thread_url' => route('messages.show', $target),
        ]);
    }

    /** Messages between the signed-in user and $contact that the user has not removed from view. */
    private function conversationWith(User $contact): Builder
    {
        $me = Auth::id();

        return Message::query()
            ->where(fn (Builder $outer) => $outer
                ->where(fn (Builder $q) => $q->where('sender_id', $me)->where('receiver_id', $contact->id))
                ->orWhere(fn (Builder $q) => $q->where('sender_id', $contact->id)->where('receiver_id', $me)))
            ->visibleTo($me);
    }

    private function otherPerson(Message $message): User
    {
        return User::findOrFail($message->sender_id === Auth::id() ? $message->receiver_id : $message->sender_id);
    }

    /** The message row HTML (null when removed from this user's view) and the pinned bar. */
    private function rowPayload(Message $message): array
    {
        $contact = $this->otherPerson($message);
        $visible = Message::whereKey($message->id)->visibleTo(Auth::id())->exists();

        return [
            'success' => true,
            'id' => $message->id,
            'html' => $visible ? $this->renderRow($message, $contact) : null,
            'pinned_html' => view('messages.partials.pinned', [
                'pinned' => $this->conversationWith($contact)->with('sender')->whereNotNull('pinned_at')->latest('pinned_at')->get(),
            ])->render(),
        ];
    }

    private function renderRow(Message $message, User $contact): string
    {
        $message->loadMissing(['replyTo.sender', 'reactions.user']);

        return view('messages.partials.message', [
            'message' => $message,
            'contact' => $contact,
            'contactAvatar' => Avatar::for($contact),
            'reactionEmojis' => MessageReaction::EMOJIS,
        ])->render();
    }

    /** React, pin, forward: only people in the chat, and not on unsent or admin-removed messages. */
    private function authorizeAction(Message $message): void
    {
        abort_unless($message->isParticipant(Auth::id()), 403);
        abort_if($message->isGone(), 422, 'This message can no longer be changed.');
    }

    /** Live: the other person's open chat re-renders this message. Optional, like MessageSent. */
    private function broadcastUpdate(Message $message): void
    {
        try {
            broadcast(new MessageUpdated($message, Auth::id()))->toOthers();
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function authorizeOwn(Message $message): void
    {
        abort_unless($message->sender_id === Auth::id(), 403);
        abort_if($message->isHidden(), 403, 'This message was removed by an administrator.');
    }

    /** People the user can start a conversation with (rules in MessagingEligibility). */
    private function allowedContacts()
    {
        return User::query()
            ->with(['student', 'coordinator', 'employer']) // profile pictures in the chat list
            ->whereIn('id', $this->eligibility->startableContactIds(Auth::user()))
            ->orderBy('name')
            ->get();
    }
}
