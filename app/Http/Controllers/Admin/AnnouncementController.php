<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Announcement\StoreAnnouncementRequest;
use App\Models\Announcement;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;

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
        ]);
    }

    public function store(StoreAnnouncementRequest $request)
    {
        $validated = $request->validated();

        $announcement = Announcement::create([
            ...$validated,
            'user_id' => Auth::id(),
            'published_at' => now(),
        ]);

        $usersQuery = User::query();
        if (! empty($validated['target_role'])) {
            $usersQuery->where('role', $validated['target_role']);
        }

        foreach ($usersQuery->get() as $user) {
            $this->notificationService->send(
                $user,
                'announcement',
                $announcement->title,
                str($announcement->content)->limit(120)->value(),
                ['announcement_id' => $announcement->id]
            );
        }

        $this->activityLog->log(Auth::user(), 'announcement_create', ['announcement_id' => $announcement->id]);

        return back()->with('success', 'Announcement published successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        $this->authorize('delete', $announcement);

        $announcementId = $announcement->id;
        $announcement->delete();

        $this->activityLog->log(Auth::user(), 'announcement_delete', ['announcement_id' => $announcementId]);

        return back()->with('success', 'Announcement deleted.');
    }
}
