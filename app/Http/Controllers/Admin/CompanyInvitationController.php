<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompanyInvitationStatus;
use App\Http\Controllers\Controller;
use App\Models\CompanyInvitation;
use App\Models\User;
use Illuminate\Http\Request;

/** Read-only list of every company invitation, who sent it, and its status. */
class CompanyInvitationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', CompanyInvitation::class);

        $status = CompanyInvitationStatus::tryFrom((string) $request->query('status'));
        $coordinatorId = is_numeric($request->query('coordinator')) ? (int) $request->query('coordinator') : null;
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);

        $invitations = CompanyInvitation::query()
            ->with(['invitedBy:id,name,email', 'employer:id,company_name'])
            ->when($status, fn ($query) => $query->withCurrentStatus($status))
            ->when($coordinatorId, fn ($query) => $query->where('invited_by_user_id', $coordinatorId))
            ->when($search !== '', fn ($query) => $query->where(fn ($match) => $match
                ->where('company_name', 'like', '%'.addcslashes($search, '%_\\').'%')
                ->orWhere('email', 'like', '%'.addcslashes($search, '%_\\').'%')
                ->orWhere('reference', 'like', '%'.addcslashes($search, '%_\\').'%')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.company-invitations.index', [
            'invitations' => $invitations,
            'filters' => ['status' => $status?->value, 'coordinator' => $coordinatorId, 'q' => $search],
            'coordinators' => User::query()
                ->whereIn('id', CompanyInvitation::query()->select('invited_by_user_id'))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
