<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountDeletionRequest;
use App\Services\AccountAnonymizer;
use App\Services\ActivityLogService;
use App\Services\BackupArchive;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Approval queue for account deletions. Approving anonymises the account (App\Services\AccountAnonymizer) and cannot
 * be undone, so it needs the admin's password and a ticked confirmation, and an encrypted backup is made first: if the
 * backup fails, nothing is deleted.
 */
class DeletionRequestController extends Controller
{
    public function __construct(private AccountAnonymizer $anonymizer, private ActivityLogService $activityLog, private NotificationService $notifications) {}

    public function index()
    {
        $pending = AccountDeletionRequest::query()->pending()->with(['user.student', 'requestedBy'])->oldest()->get()
            ->each(fn (AccountDeletionRequest $request) => $request->setAttribute('preview', $this->anonymizer->preview($request->user)));

        return view('admin.privacy.deletion-requests', [
            'pending' => $pending,
            'history' => AccountDeletionRequest::query()->where('status', '<>', AccountDeletionRequest::STATUS_PENDING)->with('decidedBy')->latest('decided_at')->latest('id')->limit(20)->get(),
        ]);
    }

    public function approve(Request $request, AccountDeletionRequest $deletionRequest)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
            'confirm' => ['accepted'],
        ], [
            'password.current_password' => 'That is not your current password.',
            'confirm.accepted' => 'Tick the box to confirm. This cannot be undone.',
        ]);
        if (! $deletionRequest->isPending() || ! $this->anonymizer->canAnonymise($deletionRequest->user)) {
            return back()->with('error', 'This request was already decided.');
        }

        // One approval at a time per request (a double click, or two admins).
        $lock = Cache::lock('deletion-request-'.$deletionRequest->id, 900);
        if (! $lock->get()) {
            return back()->with('error', 'This request is being processed. Wait a moment and refresh.');
        }
        set_time_limit(300); // the backup of the whole system takes a while

        try {
            // 1. An encrypted backup first. No backup, no deletion.
            try {
                $backup = app(BackupArchive::class)->create();
            } catch (\RuntimeException $exception) {
                report($exception);

                return back()->with('error', 'No backup could be made, so nothing was deleted: '.(str_starts_with($exception->getMessage(), 'mysqldump') ? 'check MYSQLDUMP_PATH and the database connection.' : $exception->getMessage()));
            }

            // 2. Anonymise.
            try {
                $summary = $this->anonymizer->anonymise($deletionRequest->user, Auth::user());
            } catch (\Throwable $exception) {
                report($exception);

                return back()->with('error', 'The account could not be anonymised, so nothing was changed. A backup was made first ('.$backup['name'].'). See the log for details.');
            }

            $deletionRequest->update([
                'status' => AccountDeletionRequest::STATUS_APPROVED,
                'decided_by_user_id' => Auth::id(),
                'decided_at' => now(),
                'backup_name' => $backup['name'],
                'summary' => $summary,
                'reason' => null, // the student's own words may name them; the request itself stays as a record
            ]);
            $this->activityLog->log(Auth::user(), 'deletion_approved', ['request_id' => $deletionRequest->id, 'backup' => $backup['name']]);
        } finally {
            $lock->release();
        }

        return redirect()->route('admin.deletion-requests.index')->with('success', 'The account was anonymised. A backup was made first: '.$backup['name'].'. It still holds the old data until that backup is removed (the oldest of 30 is removed each day).');
    }

    public function reject(Request $request, AccountDeletionRequest $deletionRequest)
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        if (! $deletionRequest->isPending()) {
            return back()->with('error', 'This request was already decided.');
        }
        $deletionRequest->update([
            'status' => AccountDeletionRequest::STATUS_REJECTED,
            'decided_by_user_id' => Auth::id(),
            'decided_at' => now(),
            'decision_note' => $data['note'] ?? null,
        ]);
        $this->activityLog->log(Auth::user(), 'deletion_rejected', ['request_id' => $deletionRequest->id]);
        if ($deletionRequest->user && $deletionRequest->user->is_active && $deletionRequest->source === AccountDeletionRequest::SOURCE_STUDENT) {
            $this->notifications->send($deletionRequest->user, 'deletion_decision', 'Your deletion request was not approved', 'An administrator did not approve your request to delete your account.'.(filled($data['note'] ?? null) ? ' Note: '.$data['note'] : ''), ['request_id' => $deletionRequest->id]);
        }

        return redirect()->route('admin.deletion-requests.index')->with('success', 'The request was rejected.');
    }
}
