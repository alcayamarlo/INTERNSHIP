<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Http\Requests\Messaging\StoreMessageRequest;
use App\Models\Message;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\MessagingEligibility;
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
        $userId = Auth::id();

        $conversations = Message::query()
            ->where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->latest()
            ->get()
            ->groupBy(fn ($message) => $message->sender_id === $userId
                ? $message->receiver_id
                : $message->sender_id);
        $people = User::query()->whereIn('id', $conversations->keys())->get()->keyBy('id');

        return view('messages.index', [
            'conversations' => $conversations,
            'people' => $people,
            // Existing threads stay listed; ones the user can no longer send to show the reason.
            'lockedReasons' => $people->map(fn (User $contact) => Gate::inspect('message.send', $contact))
                ->reject(fn ($response) => $response->allowed())
                ->map(fn ($response) => $response->message()),
            'contacts' => $this->allowedContacts(),
            'lockedCompanies' => $this->lockedCompanies($people->keys()),
        ]);
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

        $messages = Message::where(function ($query) use ($user) {
            $query->where('sender_id', Auth::id())->where('receiver_id', $user->id);
        })->orWhere(function ($query) use ($user) {
            $query->where('sender_id', $user->id)->where('receiver_id', Auth::id());
        })->orderBy('created_at')->get();

        Message::where('sender_id', $user->id)
            ->where('receiver_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('messages.show', [
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
                'message' => $request->validated('body'),
                'created_at' => now()->format('M d, h:i A'),
                'submission_id' => (string) Str::uuid(),
            ]);
        }

        return back()->with('success', 'Message sent.');
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
