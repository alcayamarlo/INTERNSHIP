<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\AppNotification;
use App\Models\InternshipApplication;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    /** Tabs on the notifications page; "active" is the inbox (kept as the URL value for old links). */
    private const VIEWS = ['active', 'archived', 'trash'];

    /** Bulk actions for the selected notifications. */
    private const BULK_ACTIONS = ['read', 'archive', 'unarchive', 'delete', 'restore', 'force-delete'];

    /** At most this many notifications per bulk request (one page is 15). */
    private const BULK_LIMIT = 100;

    public function __construct(private NotificationService $notifications) {}

    public function index()
    {
        $user = Auth::user();
        $view = in_array(request()->query('view'), self::VIEWS, true) ? request()->query('view') : 'active';

        $notifications = match ($view) {
            'archived' => $user->appNotifications()->archived()->latest('archived_at'),
            'trash' => $user->appNotifications()->onlyTrashed()->latest('deleted_at'),
            default => $user->appNotifications()->inbox()->latest(),
        };

        return view('notifications.index', [
            'notifications' => $notifications->latest('id')->paginate(10)->appends(['view' => $view]),
            'view' => $view,
            ...$this->state(),
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

        if (! $relatedUrl && isset($data['invitation_id']) && is_numeric($data['invitation_id'])) {
            $invitation = \App\Models\InternshipInvitation::find($data['invitation_id']);
            if ($invitation && Gate::allows('view', $invitation)) {
                $relatedUrl = Auth::user()->isRole(UserRole::Student)
                    ? route('student.invitations.index')
                    : route('employer.invitations.index');
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

        return $this->respond('Notification marked as read.');
    }

    public function markAllRead()
    {
        Auth::user()->appNotifications()->unread()->update(['read_at' => now()]);

        return $this->respond('All notifications marked as read.');
    }

    public function archive(AppNotification $notification)
    {
        $this->authorize('update', $notification);
        $notification->archive();

        return $this->respond('Notification archived.', ['archived' => true]);
    }

    /** Unarchive: back to the inbox. (Route name kept as notifications.restore for existing links.) */
    public function restore(AppNotification $notification)
    {
        $this->authorize('update', $notification);
        $notification->unarchive();

        return $this->respond('Notification moved back to your inbox.', ['archived' => false]);
    }

    /** Soft delete: moves the notification to Trash, where it can be restored. */
    public function destroy(AppNotification $notification)
    {
        $this->authorize('delete', $notification);
        // Already in Trash (double click, second tab): nothing to do, still a success.
        if (! $notification->trashed()) {
            $notification->delete();
        }

        return $this->respond('Notification moved to Trash.');
    }

    /** Takes a notification out of Trash (back to the inbox or archive it was in). */
    public function restoreDeleted(AppNotification $notification)
    {
        $this->authorize('delete', $notification);
        if ($notification->trashed()) {
            $notification->restore();
        }

        return $this->respond('Notification restored.');
    }

    /** Permanent delete, allowed only from Trash so nothing is lost by one click. */
    public function forceDestroy(AppNotification $notification)
    {
        $this->authorize('delete', $notification);
        abort_unless($notification->trashed(), 409, 'Move the notification to Trash before deleting it permanently.');
        $notification->forceDelete();

        return $this->respond('Notification permanently deleted.');
    }

    /**
     * One action on the selected notifications. Only the signed-in user's notifications can
     * match (the query starts from their own relation), so other users' ids are ignored.
     * Answers with the ids that changed so the page updates exactly those rows.
     */
    public function bulk(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(self::BULK_ACTIONS)],
            'ids' => ['required', 'array', 'min:1', 'max:'.self::BULK_LIMIT],
            'ids.*' => ['integer'],
        ], [
            'ids.required' => 'Select at least one notification.',
            'ids.max' => 'Select at most '.self::BULK_LIMIT.' notifications at a time.',
        ]);

        $mine = Auth::user()->appNotifications()->whereIn('id', $validated['ids']);
        $affected = match ($validated['action']) {
            'read' => $mine->whereNull('read_at'),
            'archive' => $mine->whereNull('archived_at'),
            'unarchive' => $mine->whereNotNull('archived_at'),
            'delete' => $mine,
            'restore', 'force-delete' => $mine->onlyTrashed(),
        };
        $ids = (clone $affected)->pluck('id')->all();

        if ($ids !== []) {
            match ($validated['action']) {
                'read' => $affected->update(['read_at' => now()]),
                'archive' => $affected->update(['archived_at' => now()]),
                'unarchive' => $affected->update(['archived_at' => null]),
                'delete' => $affected->delete(),
                'restore' => $affected->restore(),
                'force-delete' => $affected->forceDelete(),
            };
        }

        $count = count($ids);
        $noun = $count === 1 ? 'notification' : 'notifications';
        $message = match ($validated['action']) {
            'read' => "{$count} {$noun} marked as read.",
            'archive' => "{$count} {$noun} archived.",
            'unarchive' => "{$count} {$noun} moved back to your inbox.",
            'delete' => "{$count} {$noun} moved to Trash.",
            'restore' => "{$count} {$noun} restored.",
            'force-delete' => "{$count} {$noun} permanently deleted.",
        };

        return $this->respond($message, ['ids' => $ids, 'action' => $validated['action']]);
    }

    public function archiveAllRead()
    {
        Auth::user()->appNotifications()
            ->whereNotNull('read_at')
            ->inbox()
            ->update(['archived_at' => now()]);

        return $this->respond('All read notifications have been archived.');
    }

    public function unreadJson()
    {
        return response()->json([
            'count' => $this->notifications->unreadCount(Auth::user()),
            'items' => Auth::user()->appNotifications()->unread()->latest()->take(5)->get(),
        ]);
    }

    /** Badge and tab counts after a change, so the page never shows stale numbers. */
    private function state(): array
    {
        $user = Auth::user();

        return [
            'unread_count' => $this->notifications->unreadCount($user),
            'counts' => [
                'active' => $user->appNotifications()->inbox()->count(),
                'archived' => $user->appNotifications()->archived()->count(),
                'trash' => $user->appNotifications()->onlyTrashed()->count(),
            ],
        ];
    }

    /** JSON for the page's fetch() calls; a normal redirect when JavaScript is off. */
    private function respond(string $message, array $extra = [])
    {
        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message, ...$this->state(), ...$extra]);
        }

        return back()->with('success', $message);
    }
}
