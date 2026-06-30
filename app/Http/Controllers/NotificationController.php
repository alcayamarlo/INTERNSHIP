<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        return view('notifications.index', [
            'notifications' => Auth::user()->appNotifications()->latest()->paginate(15),
        ]);
    }

    public function markRead(AppNotification $notification)
    {
        $this->authorize('update', $notification);
        $notification->markAsRead();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    public function markAllRead()
    {
        Auth::user()->appNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    public function unreadJson()
    {
        return response()->json([
            'count' => Auth::user()->appNotifications()->whereNull('read_at')->count(),
            'items' => Auth::user()->appNotifications()->whereNull('read_at')->latest()->take(5)->get(),
        ]);
    }
}
