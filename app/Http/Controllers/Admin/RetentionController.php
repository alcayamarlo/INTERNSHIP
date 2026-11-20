<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountDeletionRequest;
use App\Models\Student;
use App\Services\ActivityLogService;
use App\Services\RetentionReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** Student accounts past the retention period. Listing and opening a request only: deleting still needs approval. */
class RetentionController extends Controller
{
    public function __construct(private RetentionReview $review, private ActivityLogService $activityLog) {}

    public function index()
    {
        return view('admin.privacy.retention', [
            'candidates' => $this->review->candidates(),
            'years' => config('privacy.retention_years'),
            'cutoff' => $this->review->cutoff(),
        ]);
    }

    /** Opens a deletion request for an account on the list. It then waits in the approval queue like any other. */
    public function store(Request $request, Student $student)
    {
        if (! $this->review->candidates()->contains(fn (object $row) => $row->student->id === $student->id)) {
            return back()->with('error', 'That account is not past the retention period (or already has a request).');
        }

        $created = DB::transaction(function () use ($student) {
            if ($student->user->deletionRequests()->pending()->exists()) {
                return null;
            }

            return $student->user->deletionRequests()->create([
                'source' => AccountDeletionRequest::SOURCE_RETENTION,
                'requested_by_user_id' => Auth::id(),
                'reason' => 'Past the retention period of '.config('privacy.retention_years').' years.',
            ]);
        });
        if (! $created) {
            return back()->with('error', 'That account already has a request waiting.');
        }
        $this->activityLog->log(Auth::user(), 'deletion_requested', ['request_id' => $created->id, 'source' => 'retention']);

        return redirect()->route('admin.deletion-requests.index')->with('success', 'A deletion request was opened. Review and approve it here.');
    }
}
