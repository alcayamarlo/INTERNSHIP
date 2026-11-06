<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Http\Requests\Messaging\StoreMessageRequest;
use App\Models\Message;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\MessagingEligibility;
use Illuminate\Http\Request;
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
        $people = User::query()->whereIn('id', $conversations->keys())->get()->keyBy('id');

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

        $messages = Message::where(function ($outer) use ($user) {
            $outer->where(function ($query) use ($user) {
                $query->where('sender_id', Auth::id())->where('receiver_id', $user->id);
            })->orWhere(function ($query) use ($user) {
                $query->where('sender_id', $user->id)->where('receiver_id', Auth::id());
            });
        })->visibleTo(Auth::id())->orderBy('created_at')->get();

        Message::where('sender_id', $user->id)
            ->where('receiver_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('messages.show', $this->sidebarData() + [
            'contact' => $user,
            'messages' => $messages,
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

        try {
            $message = DB::transaction(function () use ($request, $user) {
                $message = Message::create([
                    'sender_id' => Auth::id(),
                    'receiver_id' => $user->id,
                    'body' => $request->validated('body'),
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
        }

        return response()->json(['success' => true, 'message' => $message->displayBody(Auth::id())]);
    }

    /** "Remove for you": the sender clears their own copy; the other person still sees it. */
    public function removeForMe(Message $message)
    {
        abort_unless($message->sender_id === Auth::id(), 403);

        if ($message->sender_removed_at === null) {
            $message->forceFill(['sender_removed_at' => now()])->save();
            $this->activityLog->log(Auth::user(), 'message_remove_for_me', ['message_id' => $message->id]);
        }

        return response()->json(['success' => true]);
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
            ->whereIn('id', $this->eligibility->startableContactIds(Auth::user()))
            ->orderBy('name')
            ->get();
    }
}
