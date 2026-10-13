<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\AppNotification;
use App\Models\InternshipApplication;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

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

    public function show(AppNotification $notification)
    {
        $this->authorize('view', $notification);
        $notification->markAsRead();

        $data = $notification->data ?? [];
        $announcement = isset($data['announcement_id']) && is_numeric($data['announcement_id'])
            ? Announcement::find($data['announcement_id'])
            : null;
        $relatedUrl = null;

        if (isset($data['application_id']) && is_numeric($data['application_id'])) {
            $application = InternshipApplication::query()
                ->with(['student', 'internship.employer'])
                ->find($data['application_id']);

            if ($application && Auth::user()->isRole(UserRole::Student)
                && $application->student?->user_id === Auth::id()) {
                $relatedUrl = route('student.applications.show', $application);
            } elseif ($application && Auth::user()->isRole(UserRole::Employer)
                && $application->internship?->employer?->user_id === Auth::id()) {
                $relatedUrl = route('employer.applicants.show', $application);
            }
        }

        if (! $relatedUrl) {
            $contactId = $data['contact_id'] ?? $data['sender_id'] ?? null;
            if (is_numeric($contactId)) {
                $contact = User::find($contactId);
                if ($contact && Gate::allows('message.send', $contact)) {
                    $relatedUrl = route('messages.show', $contact);
                }
            }
        }

        return view('notifications.show', compact('notification', 'announcement', 'relatedUrl'));
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
