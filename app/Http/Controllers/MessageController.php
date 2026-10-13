<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\Messaging\StoreMessageRequest;
use App\Models\Message;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    public function __construct(private ActivityLogService $activityLog) {}

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

        return view('messages.index', [
            'conversations' => $conversations,
            'contacts' => $this->allowedContacts(),
        ]);
    }

    public function show(User $user)
    {
        Gate::authorize('message.send', $user);

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
            DB::transaction(function () use ($request, $user) {
                Message::create([
                    'sender_id' => Auth::id(),
                    'receiver_id' => $user->id,
                    'body' => $request->validated('body'),
                ]);

                $this->activityLog->log(Auth::user(), 'message_send', ['receiver_id' => $user->id]);
            });
        } catch (\Throwable $exception) {
            Cache::forget($cacheKey);
            throw $exception;
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

    private function allowedContacts()
    {
        return User::query()
            ->where('id', '!=', Auth::id())
            ->when(
                Auth::user()->isRole(UserRole::Student),
                fn ($query) => $query->where('role', '!=', UserRole::Student->value)
            )
            ->orderBy('name')
            ->get();
    }
}
