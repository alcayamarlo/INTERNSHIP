<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Announcement\StoreAnnouncementRequest;
use App\Models\Announcement;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnnouncementController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
        private ActivityLogService $activityLog
    ) {}

    public function index()
    {
        return view('admin.announcements.index', [
            'announcements' => Announcement::with('author')->latest()->paginate(10),
            'roles' => UserRole::assignable(),
        ]);
    }

    public function store(StoreAnnouncementRequest $request)
    {
        $validated = $request->validated();

        [$announcement, $recipientIds] = DB::transaction(function () use ($validated) {
            $targetRole = $validated['target_role'] === 'all' ? null : $validated['target_role'];
            $announcement = Announcement::create([
                ...$validated,
                'target_role' => $targetRole,
                'user_id' => Auth::id(),
                'published_at' => now(),
            ]);

            $usersQuery = User::query();
            if ($targetRole !== null) {
                $usersQuery->where('role', $targetRole);
            }

            $recipientIds = $usersQuery->pluck('id');
            $this->activityLog->log(Auth::user(), 'announcement_create', ['announcement_id' => $announcement->id]);

            return [$announcement, $recipientIds];
        });

        foreach (User::whereIn('id', $recipientIds)->get() as $user) {
            $this->notificationService->send(
                $user,
                'announcement',
                $announcement->title,
                str($announcement->content)->limit(120)->value(),
                ['announcement_id' => $announcement->id]
            );
        }

        return back()->with('success', 'Announcement published successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        $this->authorize('delete', $announcement);

        $announcementId = $announcement->id;
        DB::transaction(function () use ($announcement, $announcementId) {
            $announcement->delete();
            $this->activityLog->log(Auth::user(), 'announcement_delete', ['announcement_id' => $announcementId]);
        });

        return back()->with('success', 'Announcement deleted.');
    }
}
