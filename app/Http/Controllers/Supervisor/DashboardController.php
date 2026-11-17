<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\JournalEntry;
use App\Models\Placement;
use Illuminate\Support\Facades\Auth;

/** A supervisor's home: the interns assigned to them and what waits for their approval. */
class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $supervisor = $user->supervisor?->loadMissing('employer');
        abort_unless($supervisor, 403);

        $placements = Placement::query()
            ->where('employer_id', $supervisor->employer_id)
            ->where('supervisor_user_id', $user->id)
            ->with(['student.user', 'internship'])
            ->withApprovedHours()
            ->withCount(['attendanceLogs as pending_logs_count' => fn ($query) => $query->where('status', AttendanceLog::STATUS_PENDING)])
            ->withCount(['journalEntries as unread_journals_count' => fn ($query) => $query->where('status', JournalEntry::STATUS_SUBMITTED)])
            ->orderByRaw("case when status = 'active' then 0 else 1 end")
            ->latest()
            ->limit(10)
            ->get();

        return view('supervisor.dashboard', compact('supervisor', 'placements'));
    }
}
