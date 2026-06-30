<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\Messaging\StoreMessageRequest;
use App\Models\Message;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

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
        ]);
    }

    public function store(StoreMessageRequest $request, User $user)
    {
        Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $user->id,
            'body' => $request->validated('body'),
        ]);

        $this->activityLog->log(Auth::user(), 'message_send', ['receiver_id' => $user->id]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Message sent.');
    }

    private function allowedContacts()
    {
        $role = Auth::user()->role;

        return match ($role) {
            UserRole::Student => User::whereIn('role', [UserRole::Employer, UserRole::Coordinator])->get(),
            UserRole::Employer => User::where('role', UserRole::Student)->get(),
            UserRole::Coordinator => User::where('role', UserRole::Student)->get(),
            default => collect(),
        };
    }
}
