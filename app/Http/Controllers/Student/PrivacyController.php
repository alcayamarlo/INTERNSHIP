<?php

namespace App\Http\Controllers\Student;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AccountDeletionRequest;
use App\Models\User;
use App\Services\AccountAnonymizer;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** "Privacy and my data": what the school holds, and a request to delete the account (an admin approves it). */
class PrivacyController extends Controller
{
    public function __construct(private AccountAnonymizer $anonymizer, private ActivityLogService $activityLog, private NotificationService $notifications) {}

    public function index(Request $request)
    {
        $user = $request->user();

        return view('student.privacy', [
            'pending' => $user->deletionRequests()->pending()->latest()->first(),
            'history' => $user->deletionRequests()->where('status', '<>', AccountDeletionRequest::STATUS_PENDING)->latest()->limit(5)->get(),
            'preview' => $this->anonymizer->preview($user),
            'retentionYears' => config('privacy.retention_years'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
            'acknowledge' => ['accepted'],
            'reason' => ['nullable', 'string', 'max:500'],
        ], [
            'password.current_password' => 'That is not your current password.',
            'acknowledge.accepted' => 'Tick the box to confirm you understand.',
        ]);
        $user = $request->user();

        $created = DB::transaction(function () use ($user, $request) {
            User::query()->lockForUpdate()->find($user->id); // two clicks cannot make two requests
            if ($user->deletionRequests()->pending()->exists()) {
                return null;
            }

            return $user->deletionRequests()->create([
                'source' => AccountDeletionRequest::SOURCE_STUDENT,
                'requested_by_user_id' => $user->id,
                'reason' => filled($request->input('reason')) ? trim((string) $request->input('reason')) : null,
            ]);
        });
        if (! $created) {
            return back()->with('error', 'You already have a deletion request waiting for an administrator.');
        }

        $this->activityLog->log($user, 'deletion_requested', ['request_id' => $created->id]);
        foreach (User::query()->where('role', UserRole::Admin->value)->where('is_active', true)->get() as $admin) {
            $this->notifications->send($admin, 'deletion_request', 'Account deletion request', "A student asked to delete their account. Open the queue to see who and to decide.", ['request_id' => $created->id]); // no name: the notice outlives the account
        }

        return redirect()->route('student.privacy.index')->with('success', 'Your request was sent. An administrator will review it, and you will be notified.');
    }

    public function cancel(Request $request)
    {
        $pending = $request->user()->deletionRequests()->pending()->latest()->first();
        if (! $pending) {
            return back()->with('error', 'You have no deletion request waiting.');
        }
        $pending->update(['status' => AccountDeletionRequest::STATUS_CANCELLED, 'decided_at' => now()]);
        $this->activityLog->log($request->user(), 'deletion_cancelled', ['request_id' => $pending->id]);

        return redirect()->route('student.privacy.index')->with('success', 'Your deletion request was cancelled. Your account is unchanged.');
    }
}
