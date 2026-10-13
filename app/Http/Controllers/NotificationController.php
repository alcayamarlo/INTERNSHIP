<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $view = request()->query('view', 'active');

        $notifications = Auth::user()->appNotifications()
            ->when($view === 'archived', fn ($query) => $query->whereNotNull('archived_at'), fn ($query) => $query->whereNull('archived_at'))
            ->latest()
            ->paginate(15)
            ->appends(['view' => $view]);

        return view('notifications.index', [
            'notifications' => $notifications,
            'view' => $view,
        ]);
    }

    public function markRead(AppNotification $notification)
    {
        $this->authorize('update', $notification);
        $notification->markAsRead();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'unread_count' => Auth::user()->appNotifications()->whereNull('read_at')->whereNull('archived_at')->count(),
            ]);
        }

        return back();
    }

    public function markAllRead()
    {
        Auth::user()->appNotifications()->whereNull('read_at')->whereNull('archived_at')->update(['read_at' => now()]);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'unread_count' => 0]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    public function archive(AppNotification $notification)
    {
        $this->authorize('update', $notification);
        $notification->archive();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'archived' => true]);
        }

        return back()->with('success', 'Notification archived.');
    }

    public function restore(AppNotification $notification)
    {
        $this->authorize('update', $notification);
        $notification->restore();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'archived' => false]);
        }

        return back()->with('success', 'Notification restored.');
    }

    public function archiveAllRead()
    {
        Auth::user()->appNotifications()
            ->whereNotNull('read_at')
            ->whereNull('archived_at')
            ->update(['archived_at' => now()]);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'unread_count' => Auth::user()->appNotifications()->whereNull('read_at')->whereNull('archived_at')->count()]);
        }

        return back()->with('success', 'All read notifications have been archived.');
    }

    public function unreadJson()
    {
        return response()->json([
            'count' => Auth::user()->appNotifications()->whereNull('read_at')->whereNull('archived_at')->count(),
            'items' => Auth::user()->appNotifications()->whereNull('read_at')->whereNull('archived_at')->latest()->take(5)->get(),
        ]);
    }
}
