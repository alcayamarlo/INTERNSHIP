<?php

namespace App\Http\Controllers\Employer;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Placement;
use App\Services\CompanyVisibleProfile;
use App\Services\OjtOverview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Sidebar pages "Progress", "Time Records", "Weekly Reports", "Monthly Evaluations" and "Final Evaluations":
 * every intern the viewer supervises (the HR account: the whole company; a supervisor: their own interns)
 * with what each section shows for them and what still needs to be done.
 */
class OjtOverviewController extends Controller
{
    public function show(Request $request, string $section, OjtOverview $overview, CompanyVisibleProfile $companyProfile)
    {
        abort_unless(array_key_exists($section, OjtOverview::SECTIONS), 404);

        $user = Auth::user();
        $isSupervisor = $user->isRole(UserRole::Supervisor);
        $employer = $isSupervisor ? $user->supervisor?->employer : $user->employer;
        abort_unless($employer, 403);

        $placements = $employer->placements()
            ->when($isSupervisor, fn ($query) => $query->where('supervisor_user_id', $user->id))
            ->whereIn('status', [Placement::STATUS_ACTIVE, Placement::STATUS_COMPLETED])
            ->with(['student.user', 'internship', 'supervisorUser:id,name,is_active', 'attendanceLogs', 'journalEntries', 'appraisals', 'evaluation'])
            ->withApprovedHours()
            ->withPendingHours()
            ->orderByRaw('status = ? desc', [Placement::STATUS_ACTIVE])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $rows = $placements->getCollection()->map(function (Placement $placement) use ($overview, $section, $companyProfile) {
            $placement->setAttribute('company_profile', $placement->student ? $companyProfile->summary($placement->student) : null);

            return ['placement' => $placement, ...$overview->row($placement, $section)];
        });

        [$title, $icon, $tab, $intro] = OjtOverview::SECTIONS[$section];

        return view('employer.ojt-overview.show', [
            'section' => $section,
            'title' => $title,
            'icon' => $icon,
            'tab' => $tab,
            'intro' => $intro,
            'placements' => $placements,
            'rows' => $rows,
            'isHr' => ! $isSupervisor,
            'prefix' => $user->role->ojtRoutePrefix(),
        ]);
    }
}
