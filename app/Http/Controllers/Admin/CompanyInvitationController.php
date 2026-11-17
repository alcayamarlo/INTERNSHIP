<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompanyInvitationStatus;
use App\Http\Controllers\Controller;
use App\Models\CompanyInvitation;
use App\Models\User;
use App\Services\CompanyInvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Every company invitation, who sent it, and its status. An administrator can change one invitation's expiry. */
class CompanyInvitationController extends Controller
{
    public function __construct(private CompanyInvitationService $invitations) {}

    public function updateExpiry(Request $request, CompanyInvitation $companyInvitation)
    {
        $this->authorize('changeExpiry', $companyInvitation);

        $validated = $request->validate([
            'expires_at' => ['required', 'date_format:Y-m-d\TH:i'],
        ], [
            'expires_at.required' => 'Choose the new expiry date and time.',
            'expires_at.date_format' => 'Choose a valid date and time.',
        ]);

        // The admin types Philippine time; the database keeps the app's own time zone.
        $expiresAt = Carbon::createFromFormat('Y-m-d\TH:i', $validated['expires_at'], 'Asia/Manila');
        $this->invitations->changeExpiry($companyInvitation, $expiresAt, $request->user());

        return back()->with('success', "Expiry of {$companyInvitation->reference} changed to ".$expiresAt->format('M d, Y h:i A').' (Philippine time).');
    }

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
