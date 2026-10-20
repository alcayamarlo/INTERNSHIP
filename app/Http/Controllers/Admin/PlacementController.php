<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Placement;
use Illuminate\Http\Request;

class PlacementController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('status');

        $placements = Placement::query()
            ->with(['student.user', 'internship', 'employer', 'evaluation:id,placement_id,average_score'])
            ->withApprovedHours()
            ->when($filter === 'ongoing', fn ($query) => $query->where('status', Placement::STATUS_ACTIVE))
            ->when($filter === 'for_clearance', fn ($query) => $query->where('status', Placement::STATUS_COMPLETED)->whereNull('cleared_at'))
            ->when($filter === 'cleared', fn ($query) => $query->whereNotNull('cleared_at'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'ongoing' => Placement::where('status', Placement::STATUS_ACTIVE)->count(),
            'for_clearance' => Placement::where('status', Placement::STATUS_COMPLETED)->whereNull('cleared_at')->count(),
            'cleared' => Placement::whereNotNull('cleared_at')->count(),
            'approved_hours' => round((float) AttendanceLog::where('status', AttendanceLog::STATUS_APPROVED)->sum('hours'), 1),
        ];

        return view('admin.placements.index', compact('placements', 'summary', 'filter'));
    }

    public function show(Placement $placement)
    {
        $placement->load([
            'student.user',
            'internship',
            'employer',
            'attendanceLogs' => fn ($query) => $query->latest('work_date'),
            'attendanceLogs.reviewer:id,name',
            'journalEntries' => fn ($query) => $query->latest('week_start'),
            'evaluation.evaluator:id,name',
            'clearedBy:id,name',
        ]);

        return view('admin.placements.show', compact('placement'));
    }
}
